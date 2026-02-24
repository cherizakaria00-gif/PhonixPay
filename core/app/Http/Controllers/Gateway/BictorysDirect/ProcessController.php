<?php

namespace App\Http\Controllers\Gateway\BictorysDirect;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Lib\CurlRequest;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends Controller
{
    public static function process($deposit)
    {
        $gatewayParams = json_decode($deposit->gatewayCurrency()->gateway_parameter ?? '{}');
        $apiKey = $gatewayParams->api_key ?? null;
        $merchantReference = $gatewayParams->merchant_reference ?? null;
        $paymentType = $gatewayParams->payment_type ?? 'card';
        $country = $gatewayParams->country ?? null;
        $baseUrl = trim($gatewayParams->api_base_url ?? 'https://api.test.bictorys.com');

        if (!$apiKey || !$merchantReference) {
            return json_encode([
                'error' => true,
                'message' => 'Bictorys credentials not configured',
            ]);
        }

        $baseUrl = rtrim($baseUrl, '/');
        $redirectUrl = route('payment.redirect.success', $deposit->id);

        $apiPayment = $deposit->apiPayment;
        $customer = $apiPayment->customer ?? null;

        $grossAmount = (float) ($deposit->gateway_amount ?? 0);
        $expectedGross = (float) ($deposit->final_amount + ($deposit->totalCharge ?? 0) * ($deposit->rate ?? 1));
        if ($expectedGross > 0) {
            $grossAmount = max($grossAmount, $expectedGross);
        }
        if ($grossAmount <= 0) {
            $grossAmount = (float) ($deposit->final_amount ?? 0);
        }
        $originalAmount = $grossAmount;
        $originalCurrency = strtoupper((string) $deposit->method_currency);
        $chargeAmount = $originalAmount;
        $chargeCurrency = $originalCurrency;
        $conversionRate = null;

        if ($originalCurrency === 'USD') {
            $conversionRate = (float) ($gatewayParams->usd_xof_rate ?? 0);
            if ($conversionRate <= 0) {
                return json_encode([
                    'error' => true,
                    'message' => 'Bictorys USD conversion rate not configured',
                ]);
            }
            $chargeCurrency = 'XOF';
            $chargeAmount = (float) round($originalAmount * $conversionRate, 0);
        }

        $payload = [
            'merchantReference' => $merchantReference,
            'redirectUrl' => $redirectUrl,
            'amount' => $chargeAmount,
            'currency' => $chargeCurrency,
            'paymentReference' => $deposit->trx,
            'allowUpdateCustomer' => false,
            'orderDetails' => [
                [
                    'name' => 'Deposit ' . $deposit->trx,
                    'price' => $chargeAmount,
                    'quantity' => 1,
                    'taxRate' => 0,
                ],
            ],
        ];

        if ($country) {
            $payload['country'] = $country;
        }

        if ($customer) {
            $fullName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $payload['customer'] = array_filter([
                'name' => $fullName ?: null,
                'phone' => $customer->mobile ?? null,
                'email' => $customer->email ?? null,
                'city' => $customer->city ?? null,
                'postal_code' => $customer->zip ?? null,
                'country' => $country ?: ($customer->country ?? null),
                'locale' => $customer->locale ?? null,
            ]);
        }

        $endpoint = $baseUrl . '/pay/v1/charges?payment_type=' . urlencode($paymentType);

        Log::info('Bictorys direct request', [
            'deposit_id' => $deposit->id,
            'endpoint' => $endpoint,
            'payload' => $payload,
        ]);

        $responseRaw = CurlRequest::curlPostContent(
            $endpoint,
            json_encode($payload),
            [
                'Content-Type: application/json',
                "X-Api-Key: {$apiKey}",
            ]
        );

        $response = json_decode($responseRaw, true);

        Log::info('Bictorys direct response', [
            'deposit_id' => $deposit->id,
            'raw' => $responseRaw,
            'decoded' => $response,
        ]);

        if (!$response || !is_array($response)) {
            Log::error('Bictorys direct API error: empty response', [
                'deposit_id' => $deposit->id,
                'raw' => $responseRaw,
            ]);
            return json_encode([
                'error' => true,
                'message' => 'Some problem occurred with Bictorys API.',
            ]);
        }

        $redirectUrl = self::extractRedirectUrl($response);
        if (!$redirectUrl) {
            Log::error('Bictorys direct API error', [
                'deposit_id' => $deposit->id,
                'response' => $response,
            ]);
            $message = $response['message'] ?? $response['error'] ?? 'Invalid API response';
            return json_encode([
                'error' => true,
                'message' => $message,
            ]);
        }

        $reference = self::extractReference($response);
        if ($reference) {
            $deposit->btc_wallet = $reference;
            $deposit->save();
        }

        Log::info('Bictorys direct redirect resolved', [
            'deposit_id' => $deposit->id,
            'redirect_url' => $redirectUrl,
            'reference' => $reference,
            'original_amount' => $originalAmount,
            'gateway_amount' => (float) ($deposit->gateway_amount ?? 0),
            'expected_gross' => $expectedGross,
            'original_currency' => $originalCurrency,
            'charge_amount' => $chargeAmount,
            'charge_currency' => $chargeCurrency,
            'conversion_rate' => $conversionRate,
        ]);

        return json_encode([
            'redirect' => true,
            'redirect_url' => $redirectUrl,
            'conversion' => [
                'original_amount' => $originalAmount,
                'original_currency' => $originalCurrency,
                'converted_amount' => $chargeAmount,
                'converted_currency' => $chargeCurrency,
                'conversion_rate' => $conversionRate,
            ],
        ]);
    }

    public function ipn(Request $request)
    {
        $payload = $request->all();
        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }

        $reference = data_get($payload, 'paymentReference')
            ?? data_get($payload, 'payment_reference')
            ?? data_get($payload, 'reference');

        if (!$reference) {
            return response('OK', 200);
        }

        $deposit = Deposit::where('trx', $reference)
            ->where('status', Status::PAYMENT_INITIATE)
            ->orderBy('id', 'desc')
            ->first();

        if (!$deposit) {
            return response('OK', 200);
        }

        $status = strtolower((string) (data_get($payload, 'status')
            ?? data_get($payload, 'paymentStatus')
            ?? data_get($payload, 'payment_status')
            ?? ''));

        $successFlags = [
            'success',
            'successful',
            'paid',
            'completed',
        ];

        if (($payload['success'] ?? null) === true || in_array($status, $successFlags, true)) {
            PaymentController::userDataUpdate($deposit);
        }

        return response('OK', 200);
    }

    protected static function extractRedirectUrl(array $response): ?string
    {
        $candidates = [
            data_get($response, 'link'),
            data_get($response, 'paymentUrl'),
            data_get($response, 'payment_url'),
            data_get($response, 'checkoutUrl'),
            data_get($response, 'checkout_url'),
            data_get($response, 'redirectUrl'),
            data_get($response, 'redirect_url'),
            data_get($response, 'url'),
            data_get($response, 'data.link'),
            data_get($response, 'data.paymentUrl'),
            data_get($response, 'data.payment_url'),
            data_get($response, 'data.checkoutUrl'),
            data_get($response, 'data.checkout_url'),
            data_get($response, 'data.redirectUrl'),
            data_get($response, 'data.redirect_url'),
            data_get($response, 'data.url'),
        ];

        foreach ($candidates as $url) {
            if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }

        return null;
    }

    protected static function extractReference(array $response): ?string
    {
        $candidates = [
            data_get($response, 'id'),
            data_get($response, 'chargeId'),
            data_get($response, 'charge_id'),
            data_get($response, 'paymentReference'),
            data_get($response, 'payment_reference'),
            data_get($response, 'data.id'),
            data_get($response, 'data.chargeId'),
            data_get($response, 'data.charge_id'),
            data_get($response, 'data.paymentReference'),
            data_get($response, 'data.payment_reference'),
        ];

        foreach ($candidates as $value) {
            if (!empty($value) && is_string($value)) {
                return $value;
            }
        }

        return null;
    }
}
