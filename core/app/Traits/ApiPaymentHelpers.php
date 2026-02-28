<?php

namespace App\Traits;

use App\Constants\Status;
use App\Models\ApiPayment;
use App\Models\GatewayCurrency;
use App\Services\PlanService;
use App\Lib\CurlRequest;
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


