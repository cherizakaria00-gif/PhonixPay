<?php

namespace App\Traits;

use App\Constants\Status;
use App\Lib\ClientInfo;
use App\Models\ApiPayment;
use App\Models\GatewayCurrency;
use App\Services\PlanService;
use App\Lib\CurlRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

trait ApiPaymentHelpers{

    public function getApiPayment($trx){ 
		
		if(!$trx){
			return [
                'status'=> 'error',
                'message' => 'Missing payment transaction number'
            ]; 
		}
     
		$trx = decrypt($trx); 
        $apiPayment = ApiPayment::where('trx', $trx)->first();

        if(!$apiPayment || $apiPayment->status == Status::PAYMENT_SUCCESS || $apiPayment->status == Status::PAYMENT_CANCEL){
            return [
                'status'=> 'error',
                'message' => 'Invalid transaction request'
            ];
        }

        return $apiPayment;
    }

    public function paymentMethods($currency, $gateway = null){
  
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->where('currency', $currency);
   
		if(gettype($gateway) == 'array'){
            $gatewayCurrency = $gatewayCurrency->whereIn('gateway_alias', $gateway);
        }

        return $gatewayCurrency;
    }

    protected function resolveCheckoutCountryCode(Request $request): ?string
    {
        $headerCandidates = [
            $request->header('CF-IPCountry'),
            $request->server('HTTP_CF_IPCOUNTRY'),
            $request->header('X-AppEngine-Country'),
            $request->header('X-Country-Code'),
        ];

        foreach ($headerCandidates as $candidate) {
            $countryCode = strtoupper(trim((string) $candidate));
            if (preg_match('/^[A-Z]{2}$/', $countryCode) && $countryCode !== 'XX') {
                return $countryCode;
            }
        }

        try {
            $ipInfo = ClientInfo::ipInfo();
            $countryCode = strtoupper(trim((string) data_get($ipInfo, 'code')));
            if (preg_match('/^[A-Z]{2}$/', $countryCode)) {
                return $countryCode;
            }
        } catch (\Throwable $exception) {
            return null;
        }

        return null;
    }

    protected function resolvePreferredCheckoutMethodCode(Collection $gatewayCurrency, ?string $countryCode = null): ?int
    {
        if ($gatewayCurrency->isEmpty()) {
            return null;
        }

        $fiatMethods = $gatewayCurrency->filter(function ($gateway) {
            return (int) data_get($gateway, 'method.crypto', 0) === 0;
        })->values();

        $cryptoMethods = $gatewayCurrency->filter(function ($gateway) {
            return (int) data_get($gateway, 'method.crypto', 0) === 1;
        })->values();

        if ($fiatMethods->isEmpty()) {
            return (int) data_get($gatewayCurrency->first(), 'method_code');
        }

        if ($cryptoMethods->isEmpty()) {
            return (int) data_get($fiatMethods->first(), 'method_code');
        }

        $countryCode = strtoupper((string) $countryCode);
        $euroAreaCountries = $this->euroAreaCountryCodes();

        $eurFiatMethods = $fiatMethods->filter(function ($gateway) {
            $currency = strtoupper((string) data_get($gateway, 'currency'));
            $name = strtoupper((string) data_get($gateway, 'name'));
            return $currency === 'EUR' || str_contains($name, 'EUR');
        })->values();

        if ($countryCode && in_array($countryCode, $euroAreaCountries, true) && $eurFiatMethods->isNotEmpty()) {
            return (int) data_get($eurFiatMethods->first(), 'method_code');
        }

        if ($countryCode && !in_array($countryCode, $euroAreaCountries, true) && $eurFiatMethods->count() === $fiatMethods->count()) {
            return (int) data_get($cryptoMethods->first(), 'method_code');
        }

        return (int) data_get($fiatMethods->first(), 'method_code');
    }

    protected function buildCheckoutAutoSelection(Request $request, Collection $gatewayCurrency): array
    {
        $countryCode = $this->resolveCheckoutCountryCode($request);
        $preferredMethodCode = $this->resolvePreferredCheckoutMethodCode($gatewayCurrency, $countryCode);

        return [
            'ip_country_code' => $countryCode,
            'preferred_method_code' => $preferredMethodCode,
        ];
    }

    protected function euroAreaCountryCodes(): array
    {
        return [
            'AT',
            'BE',
            'CY',
            'DE',
            'EE',
            'ES',
            'FI',
            'FR',
            'GR',
            'HR',
            'IE',
            'IT',
            'LT',
            'LU',
            'LV',
            'MT',
            'NL',
            'PT',
            'SI',
            'SK',
        ];
    }

    public function checkUserPayment($user){
        
        $message = 'Something went wrong with this merchant account';

        if($user->status == Status::USER_BAN){
            return [
                'status'=> 'error',
                'message' => [$message]
            ];
        }
        if($user->ev == Status::UNVERIFIED){
            return [
                'status'=> 'error',
                'message' => [$message]
            ];  
        }
        if($user->sv == Status::UNVERIFIED){
            return [
                'status'=> 'error',
                'message' => [$message]
            ];
        }
        if($user->kv != Status::KYC_VERIFIED){
            return [
                'status'=> 'error',
                'message' => [$message]
            ];
        }

        if (
            class_exists(PlanService::class)
            && Schema::hasTable('plans')
            && Schema::hasColumn('users', 'monthly_tx_count')
            && Schema::hasColumn('users', 'monthly_tx_count_reset_at')
        ) {
            /** @var PlanService $planService */
            $planService = app(PlanService::class);
            $planCheck = $planService->canProcessTransaction($user);

            if (!$planCheck['allowed']) {
                return [
                    'status' => 'error',
                    'message' => [$planCheck['message']],
                ];
            }
        }
    }

    public static function outerIpn($apiPayment){

        $user = $apiPayment->user;
        $customKey = $apiPayment->amount.$apiPayment->identifier;
        $secretKey = $user->secret_api_key;

        if ($apiPayment->type == 'test') {
            $secretKey = $user->test_secret_api_key;
        }

        CurlRequest::curlPostContent($apiPayment->ipn_url, [
            'status'     => 'success',
            'signature' => strtoupper(hash_hmac('sha256', $customKey , $secretKey)),
            'identifier' => $apiPayment->identifier,
            'data' => [
                'payment_trx' =>  $apiPayment->trx,
                'amount'      => $apiPayment->amount,
                'payment_type'   => 'checkout',
                'payment_timestamp' => $apiPayment->created_at,
                'currency' => $apiPayment->currency,
            ],
        ]);

    }
}

