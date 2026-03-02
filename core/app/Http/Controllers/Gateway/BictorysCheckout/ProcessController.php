<?php

namespace App\Http\Controllers\Gateway\BictorysCheckout;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Lib\CurlRequest;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends Controller
{
    private const DEFAULT_EUR_XOF_RATE = 655.957;

    public static function process($deposit)
    {
        $gatewayParams = json_decode($deposit->gatewayCurrency()->gateway_parameter ?? '{}');
        $apiKey = $gatewayParams->api_key ?? null;
        $merchantReference = $gatewayParams->merchant_reference ?? null;
        $baseUrl = trim($gatewayParams->api_base_url ?? 'https://api.test.bictorys.com');

        if (!$apiKey || !$merchantReference) {
            return json_encode([
                'error' => true,
                'message' => 'Bictorys credentials not configured',
            ]);
        }

        $baseUrl = rtrim($baseUrl, '/');
        $successUrl = route('payment.redirect.success', $deposit->id);
        $errorUrl = route('payment.redirect.cancel', $deposit->id);

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
        $conversion = self::resolveSettlementAmount(
            $originalAmount,
            $originalCurrency,
            $gatewayParams
        );

        if (!empty($conversion['error'])) {
            return json_encode([
                'error' => true,
                'message' => $conversion['error'],
            ]);
        }

        $chargeAmount = $conversion['amount'];
        $chargeCurrency = $conversion['currency'];
        $conversionRate = $conversion['rate'];

        $payload = [
            'amount' => $chargeAmount,
            'currency' => $chargeCurrency,
            'paymentReference' => $deposit->trx,
            'merchantReference' => $merchantReference,
            'successRedirectUrl' => $successUrl,
            'errorRedirectUrl' => $errorUrl,
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

        if ($customer) {
            $fullName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $payload['customerObject'] = array_filter([
                'name' => $fullName ?: null,
                'phone' => $customer->mobile ?? null,
                'email' => $customer->email ?? null,
            ]);
        }

        Log::info('Bictorys checkout request', [
            'deposit_id' => $deposit->id,
            'endpoint' => $baseUrl . '/pay/v1/charges',
            'payload' => $payload,
        ]);

        $responseRaw = CurlRequest::curlPostContent(
            $baseUrl . '/pay/v1/charges',
            json_encode($payload),
            [
                'Content-Type: application/json',
                "X-Api-Key: {$apiKey}",
            ]
        );

        $response = json_decode($responseRaw, true);

        Log::info('Bictorys checkout response', [
            'deposit_id' => $deposit->id,
            'raw' => $responseRaw,
            'decoded' => $response,
        ]);

        if (!$response || !is_array($response)) {
            Log::error('Bictorys checkout API error: empty response', [
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
            Log::error('Bictorys checkout API error', [
                'deposit_id' => $deposit->id,
                'response' => $response,
            ]);
            $message = $response['message']
                ?? $response['details']
                ?? $response['error']
                ?? $response['title']
                ?? 'Invalid API response';
            return json_encode([
                'error' => true,
                'message' => $message,
            ]);
        }

        $redirectUrl = self::applyPaymentCategory($redirectUrl, $chargeCurrency);

        $reference = self::extractReference($response);
        if ($reference) {
            $deposit->btc_wallet = $reference;
            $deposit->save();
        }

        Log::info('Bictorys checkout redirect resolved', [
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

    protected static function applyPaymentCategory(string $url, ?string $currency): string
    {
        return self::appendQueryParam($url, 'payment_category', 'card');
    }

    protected static function resolveSettlementAmount(float $originalAmount, string $originalCurrency, object $gatewayParams): array
    {
        $currency = strtoupper(trim((string) $originalCurrency));
        $amount = (float) $originalAmount;
        $rate = null;

        if ($amount <= 0) {
            return [
                'error' => 'Invalid payment amount',
            ];
        }

        if ($currency === 'USD') {
            $rate = (float) ($gatewayParams->usd_xof_rate ?? 0);
            if ($rate <= 0) {
                return [
                    'error' => 'Bictorys USD to XOF rate not configured',
                ];
            }

            return [
                'amount' => (float) max(1, round($amount * $rate, 0)),
                'currency' => 'XOF',
                'rate' => $rate,
            ];
        }

        if ($currency === 'EUR') {
            $rate = (float) ($gatewayParams->eur_xof_rate ?? self::DEFAULT_EUR_XOF_RATE);
            if ($rate <= 0) {
                $rate = self::DEFAULT_EUR_XOF_RATE;
            }

            return [
                'amount' => (float) max(1, round($amount * $rate, 0)),
                'currency' => 'XOF',
                'rate' => $rate,
            ];
        }

        return [
            'amount' => $amount,
            'currency' => $currency,
            'rate' => null,
        ];
    }

    protected static function appendQueryParam(string $url, string $key, string $value): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $query[$key] = $value;

        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        $base = $scheme && $host ? ($scheme . '://' . $host . $port . $path) : $path;
        $queryString = http_build_query($query);

        return $base . ($queryString ? '?' . $queryString : '') . $fragment;
    }
}
