<?php

namespace App\Http\Controllers\Gateway\BictorysCheckout;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Lib\CurlRequest;
use App\Models\Deposit;
use App\Services\CurrencyConversionService;
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
        $verificationToken = hash_hmac(
            'sha256',
            $deposit->id . '|' . $deposit->trx,
            (string) config('app.key')
        );
        $successUrl = self::appendQueryParam(route('payment.redirect.success', $deposit->id), 'vtoken', $verificationToken);
        $errorUrl = self::appendQueryParam(route('payment.redirect.cancel', $deposit->id), 'vtoken', $verificationToken);
        $callbackUrl = self::appendQueryParam(route('ipn.BictorysCheckout'), 'vtoken', $verificationToken);

        $apiPayment = $deposit->apiPayment;
        $customer = $apiPayment->customer ?? null;

        $grossAmount = (float) ($deposit->gateway_amount ?? 0);
        $reconstructedGross = (float) (($deposit->final_amount ?? 0) + ($deposit->totalCharge ?? 0));
        if ($grossAmount <= 0 && $reconstructedGross > 0) {
            $grossAmount = $reconstructedGross;
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
            'cancelRedirectUrl' => $errorUrl,
            'redirectUrl' => $successUrl,
            'callbackUrl' => $callbackUrl,
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
        $opToken = self::extractOpToken($response);
        $detail = self::normalizeDetailPayload($deposit->detail);
        $detail['bictorys'] = array_filter([
            'charge_id' => $reference,
            'op_token' => $opToken,
            'gateway_alias' => 'BictorysCheckout',
        ], static fn($value) => $value !== null && $value !== '');

        if ($reference) {
            $deposit->btc_wallet = $reference;
        }
        $deposit->detail = $detail;
        $deposit->save();

        Log::info('Bictorys checkout redirect resolved', [
            'deposit_id' => $deposit->id,
            'redirect_url' => $redirectUrl,
            'reference' => $reference,
            'op_token' => $opToken,
            'original_amount' => $originalAmount,
            'gateway_amount' => (float) ($deposit->gateway_amount ?? 0),
            'reconstructed_gross' => $reconstructedGross,
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

        $references = self::extractIpnReferences($payload);
        $status = self::extractIpnStatus($payload);

        Log::info('Bictorys checkout IPN received', [
            'references' => $references,
            'status' => $status,
            'payload' => $payload,
        ]);

        $deposit = self::findPendingDepositByReferences($references);
        $verificationToken = trim((string) ($request->input('vtoken') ?? $request->query('vtoken') ?? ''));

        if (!$deposit && $verificationToken !== '') {
            $deposit = self::findPendingDepositByVerificationToken($verificationToken);
        }

        if (!$deposit) {
            Log::warning('Bictorys checkout IPN deposit not found', [
                'references' => $references,
                'status' => $status,
                'vtoken' => $verificationToken !== '',
            ]);
            return response('OK', 200);
        }

        if (!$deposit->btc_wallet) {
            $chargeReference = self::resolveChargeReference($references, (string) $deposit->trx);
            if ($chargeReference) {
                $deposit->btc_wallet = $chargeReference;
                $deposit->save();
            }
        }

        $successFlag = self::extractSuccessFlag($payload);
        $hasValidToken = self::hasValidVerificationToken($deposit, $verificationToken);
        $isFailureStatus = self::isIpnFailureStatus($status);
        $hasWebhookSignal = !empty($references)
            || $status !== ''
            || $successFlag !== null
            || self::extractEventName($payload) !== '';

        if ($successFlag === false || $isFailureStatus) {
            if (in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)) {
                $deposit->status = Status::PAYMENT_REJECT;
                $deposit->save();
            }
            Log::info('Bictorys checkout IPN marked deposit rejected', [
                'deposit_id' => $deposit->id,
                'trx' => $deposit->trx,
                'status' => $status,
                'success_flag' => $successFlag,
            ]);
            return response('OK', 200);
        }

        $isSuccess = self::isIpnSuccessPayload($payload, $status)
            || ($hasValidToken && $hasWebhookSignal && $successFlag !== false && !$isFailureStatus);

        if ($isSuccess) {
            PaymentController::userDataUpdate($deposit);
            Log::info('Bictorys checkout IPN marked deposit successful', [
                'deposit_id' => $deposit->id,
                'trx' => $deposit->trx,
                'status' => $status,
                'matched_by_token' => $hasValidToken,
            ]);
        } else {
            Log::info('Bictorys checkout IPN ignored (non-success status)', [
                'deposit_id' => $deposit->id,
                'trx' => $deposit->trx,
                'status' => $status,
                'matched_by_token' => $hasValidToken,
            ]);
        }

        return response('OK', 200);
    }

    protected static function findPendingDepositByReferences(array $references): ?Deposit
    {
        $pendingStatuses = [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING];

        $deposit = Deposit::whereIn('status', $pendingStatuses)
            ->whereIn('trx', $references)
            ->orderBy('id', 'desc')
            ->first();

        if ($deposit) {
            return $deposit;
        }

        return Deposit::whereIn('status', $pendingStatuses)
            ->whereIn('btc_wallet', $references)
            ->orderBy('id', 'desc')
            ->first();
    }

    protected static function findPendingDepositByVerificationToken(string $token): ?Deposit
    {
        if ($token === '') {
            return null;
        }

        $pendingStatuses = [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING];
        $candidates = Deposit::query()
            ->whereIn('status', $pendingStatuses)
            ->whereHas('gateway', function ($query) {
                $query->where('alias', 'BictorysCheckout');
            })
            ->latest('id')
            ->take(5000)
            ->get();

        foreach ($candidates as $deposit) {
            $expected = hash_hmac(
                'sha256',
                $deposit->id . '|' . $deposit->trx,
                (string) config('app.key')
            );

            if (hash_equals($expected, $token)) {
                return $deposit;
            }
        }

        return null;
    }

    protected static function extractIpnReferences(array $payload): array
    {
        $paths = [
            'paymentReference',
            'payment_reference',
            'merchantReference',
            'merchant_reference',
            'reference',
            'id',
            'chargeId',
            'charge_id',
            'paymentId',
            'payment_id',
            'transactionId',
            'transaction_id',
            'data.paymentReference',
            'data.payment_reference',
            'data.merchantReference',
            'data.merchant_reference',
            'data.reference',
            'data.id',
            'data.chargeId',
            'data.charge_id',
            'data.paymentId',
            'data.payment_id',
            'data.transactionId',
            'data.transaction_id',
            'payment.reference',
            'payment.id',
            'payment.chargeId',
            'payment.charge_id',
            'payment.paymentReference',
            'payment.payment_reference',
            'payment.merchantReference',
            'payment.merchant_reference',
            'data.data.paymentReference',
            'data.data.payment_reference',
            'data.data.merchantReference',
            'data.data.merchant_reference',
            'data.data.reference',
            'data.data.id',
            'data.data.chargeId',
            'data.data.charge_id',
            'data.data.paymentId',
            'data.data.payment_id',
            'data.data.transactionId',
            'data.data.transaction_id',
        ];

        $references = [];
        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (is_array($value)) {
                foreach ($value as $item) {
                    $normalized = self::normalizeReferenceValue($item);
                    if ($normalized !== null) {
                        $references[] = $normalized;
                    }
                }
                continue;
            }

            $normalized = self::normalizeReferenceValue($value);
            if ($normalized !== null) {
                $references[] = $normalized;
            }
        }

        return array_values(array_unique($references));
    }

    protected static function normalizeReferenceValue($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }

    protected static function resolveChargeReference(array $references, string $trx): ?string
    {
        foreach ($references as $reference) {
            if ($reference !== '' && strcasecmp($reference, $trx) !== 0) {
                return $reference;
            }
        }

        return null;
    }

    protected static function extractIpnStatus(array $payload): string
    {
        $paths = [
            'status',
            'paymentStatus',
            'payment_status',
            'state',
            'paymentState',
            'payment_state',
            'result',
            'data.status',
            'data.paymentStatus',
            'data.payment_status',
            'data.state',
            'data.paymentState',
            'data.payment_state',
            'data.result',
            'payment.status',
            'payment.paymentStatus',
            'payment.payment_status',
            'payment.state',
            'payment.paymentState',
            'payment.payment_state',
            'payment.result',
            'data.data.status',
            'data.data.paymentStatus',
            'data.data.payment_status',
            'data.data.state',
            'data.data.paymentState',
            'data.data.payment_state',
            'data.data.result',
        ];

        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (!is_scalar($value)) {
                continue;
            }

            $status = self::normalizeStatus((string) $value);
            if ($status !== '') {
                return $status;
            }
        }

        return '';
    }

    protected static function extractSuccessFlag(array $payload): ?bool
    {
        $successValues = [
            data_get($payload, 'success'),
            data_get($payload, 'data.success'),
            data_get($payload, 'payment.success'),
            data_get($payload, 'data.data.success'),
        ];

        foreach ($successValues as $value) {
            if (is_bool($value)) {
                return $value;
            }

            if (is_numeric($value)) {
                return (int) $value === 1;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $normalized = strtolower(trim((string) $value));
            if (in_array($normalized, ['1', 'true', 'yes', 'ok'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'failed', 'failure', 'error'], true)) {
                return false;
            }
        }

        return null;
    }

    protected static function isIpnSuccessPayload(array $payload, string $status): bool
    {
        $successFlag = self::extractSuccessFlag($payload);
        if ($successFlag === true) {
            return true;
        }

        if (self::isIpnFailureStatus($status)) {
            return false;
        }

        $successFlags = [
            'success',
            'successful',
            'paid',
            'completed',
            'succeeded',
            'approved',
            'received',
            'captured',
            'settled',
            'done',
        ];

        if (in_array($status, $successFlags, true)) {
            return true;
        }

        if (self::statusContainsAny($status, [
            'success',
            'succeed',
            'paid',
            'complete',
            'approved',
            'receiv',
            'captur',
            'settl',
        ])) {
            return true;
        }

        $event = self::extractEventName($payload);

        $successEvents = [
            'charge.succeeded',
            'charge.paid',
            'charge.completed',
            'charge.received',
            'payment.succeeded',
            'payment.successful',
            'payment.paid',
            'payment.completed',
            'payment.received',
        ];

        return in_array($event, $successEvents, true);
    }

    protected static function extractEventName(array $payload): string
    {
        return strtolower(trim((string) (
            data_get($payload, 'event')
            ?? data_get($payload, 'eventType')
            ?? data_get($payload, 'event_type')
            ?? data_get($payload, 'type')
            ?? data_get($payload, 'data.event')
            ?? data_get($payload, 'data.type')
            ?? data_get($payload, 'data.data.event')
            ?? data_get($payload, 'data.data.type')
            ?? ''
        )));
    }

    protected static function isIpnFailureStatus(string $status): bool
    {
        if (in_array($status, [
            'failed',
            'failure',
            'error',
            'canceled',
            'cancelled',
            'rejected',
            'expired',
            'refunded',
            'chargeback',
            'declined',
            'unpaid',
            'void',
        ], true)) {
            return true;
        }

        return self::statusContainsAny($status, [
            'fail',
            'error',
            'cancel',
            'reject',
            'expire',
            'refund',
            'chargeback',
            'declin',
            'unpaid',
            'not_paid',
        ]);
    }

    protected static function hasValidVerificationToken(Deposit $deposit, string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $deposit->id . '|' . $deposit->trx,
            (string) config('app.key')
        );

        return hash_equals($expected, $token);
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

    protected static function extractOpToken(array $response): ?string
    {
        $candidates = [
            data_get($response, 'opToken'),
            data_get($response, 'op_token'),
            data_get($response, 'token'),
            data_get($response, 'data.opToken'),
            data_get($response, 'data.op_token'),
            data_get($response, 'data.token'),
        ];

        foreach ($candidates as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $token = trim((string) $value);
            if ($token !== '') {
                return $token;
            }
        }

        $urlCandidates = [
            data_get($response, 'link'),
            data_get($response, 'paymentUrl'),
            data_get($response, 'payment_url'),
            data_get($response, 'checkoutUrl'),
            data_get($response, 'checkout_url'),
            data_get($response, 'redirectUrl'),
            data_get($response, 'redirect_url'),
            data_get($response, 'data.link'),
            data_get($response, 'data.paymentUrl'),
            data_get($response, 'data.payment_url'),
            data_get($response, 'data.checkoutUrl'),
            data_get($response, 'data.checkout_url'),
            data_get($response, 'data.redirectUrl'),
            data_get($response, 'data.redirect_url'),
        ];

        foreach ($urlCandidates as $url) {
            if (!is_string($url) || trim($url) === '') {
                continue;
            }

            $query = parse_url($url, PHP_URL_QUERY);
            if (!is_string($query) || $query === '') {
                continue;
            }

            parse_str($query, $params);
            $token = trim((string) ($params['op_token'] ?? $params['opToken'] ?? ''));
            if ($token !== '') {
                return $token;
            }
        }

        return null;
    }

    protected static function normalizeDetailPayload($detail): array
    {
        if (is_array($detail)) {
            return $detail;
        }

        if (is_object($detail)) {
            return (array) $detail;
        }

        return [];
    }

    protected static function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === '') {
            return '';
        }

        $status = str_replace(['-', ' '], '_', $status);
        $status = preg_replace('/[^a-z0-9_]+/', '', $status) ?? '';
        $status = preg_replace('/_+/', '_', $status) ?? '';

        return trim($status, '_');
    }

    protected static function statusContainsAny(string $status, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($status, $needle)) {
                return true;
            }
        }

        return false;
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

        $centralRate = app(CurrencyConversionService::class)->getCrossRate($currency, 'XOF');
        if ($centralRate !== null && $centralRate > 0) {
            return [
                'amount' => (float) max(1, round($amount * $centralRate, 0)),
                'currency' => 'XOF',
                'rate' => $centralRate,
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
