<?php

namespace App\Http\Controllers\Gateway\NowPaymentsCheckout;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Lib\CurlRequest;
use App\Models\Deposit;
use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends Controller {
    public static function process($deposit) {
        $nowPaymentsAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);
        $successUrl = self::buildReturnUrl($deposit->success_url);
        $cancelUrl = self::buildReturnUrl($deposit->failed_url);
        $grossAmount = (float) ($deposit->gateway_amount ?? 0);
        $expectedGross = (float) ($deposit->final_amount + ($deposit->totalCharge ?? 0) * ($deposit->rate ?? 1));
        if ($expectedGross > 0) {
            $grossAmount = max($grossAmount, $expectedGross);
        }
        if ($grossAmount <= 0) {
            $grossAmount = (float) ($deposit->final_amount ?? 0);
        }

        $responseRaw       = CurlRequest::curlPostContent('https://api.nowpayments.io/v1/invoice', json_encode([
            'price_amount'     => $grossAmount,
            'price_currency'   => $deposit->method_currency,
            'ipn_callback_url' => route('ipn.NowPaymentsCheckout'),
            'success_url'      => $successUrl,
            'cancel_url'       => $cancelUrl,
            'order_id'         => $deposit->trx,

        ]), [
            "x-api-key: $nowPaymentsAcc->api_key",
            'Content-Type: application/json',
        ]);
        $response = json_decode($responseRaw);

        if (!$response) {
            Log::error('NowPayments checkout API error: empty response', [
                'deposit_id' => $deposit->id,
                'raw' => $responseRaw,
            ]);
            $send['error']   = true;
            $send['message'] = 'Some problem ocurred with api.';
            return json_encode($send);
        }

        if (!@$response->invoice_url) {
            Log::error('NowPayments checkout API error', [
                'deposit_id' => $deposit->id,
                'response' => $response,
            ]);
            $message = $response->message ?? $response->error ?? 'Invalid api key';
            $send['error']   = true;
            $send['message'] = $message;
            return json_encode($send);
        }

        $send['redirect'] = true;
        $send['redirect_url'] = $response->invoice_url;

        return json_encode($send);
    }

    protected static function buildReturnUrl(?string $path): string
    {
        $path = trim((string) $path);
        if ($path && filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        $base = rtrim(route('home'), '/');
        $path = $path ? '/' . ltrim($path, '/') : '';
        return $base . $path;
    }

    public function ipn(Request $request) {
        $signature = trim((string) $request->header('x-nowpayments-sig', ''));
        $rawPayload = (string) $request->getContent();
        $payload = json_decode($rawPayload, true);

        if ($signature === '' || !is_array($payload)) {
            Log::warning('NowPayments checkout IPN ignored: missing signature or invalid JSON', [
                'signature_present' => $signature !== '',
                'raw' => $rawPayload,
            ]);
            return response()->json(['ok' => true]);
        }

        $orderId = trim((string) ($payload['order_id'] ?? ''));
        if ($orderId === '') {
            Log::warning('NowPayments checkout IPN ignored: missing order_id', ['payload' => $payload]);
            return response()->json(['ok' => true]);
        }

        $deposit = Deposit::where('trx', $orderId)->latest('id')->first();
        if (!$deposit) {
            Log::warning('NowPayments checkout IPN ignored: deposit not found', ['order_id' => $orderId]);
            return response()->json(['ok' => true]);
        }

        $secret = $this->resolveSecretKey($deposit, 'NowPaymentsCheckout');
        if ($secret === '') {
            Log::error('NowPayments checkout IPN ignored: secret key missing', ['deposit_id' => $deposit->id]);
            return response()->json(['ok' => true]);
        }

        $computedHmac = hash_hmac('sha512', $this->preparePayloadForSignature($payload), $secret);
        if (!hash_equals($computedHmac, $signature)) {
            Log::warning('NowPayments checkout IPN signature mismatch', [
                'deposit_id' => $deposit->id,
                'order_id' => $orderId,
            ]);
            return response()->json(['ok' => true]);
        }

        $paymentStatus = strtolower(trim((string) ($payload['payment_status'] ?? '')));
        $actuallyPaid = (float) ($payload['actually_paid'] ?? 0);
        $expectedPayAmount = (float) ($payload['pay_amount'] ?? 0);

        if ($this->isSuccessfulStatus($paymentStatus) && ($expectedPayAmount <= 0 || $actuallyPaid + 1e-8 >= $expectedPayAmount)) {
            PaymentController::userDataUpdate((int) $deposit->id);
            Log::info('NowPayments checkout IPN marked deposit as success', [
                'deposit_id' => $deposit->id,
                'trx' => $deposit->trx,
                'payment_status' => $paymentStatus,
                'actually_paid' => $actuallyPaid,
                'pay_amount' => $expectedPayAmount,
            ]);
            return response()->json(['ok' => true]);
        }

        if ($this->isPendingStatus($paymentStatus)) {
            $this->syncLocalStatus($deposit, Status::PAYMENT_PENDING, 'pending');
            return response()->json(['ok' => true]);
        }

        if ($this->isExpiredStatus($paymentStatus)) {
            $this->syncLocalStatus($deposit, Status::PAYMENT_CANCEL, 'expired');
            return response()->json(['ok' => true]);
        }

        if ($this->isFailedStatus($paymentStatus)) {
            $this->syncLocalStatus($deposit, Status::PAYMENT_REJECT, 'failed');
            return response()->json(['ok' => true]);
        }

        Log::info('NowPayments checkout IPN received unhandled status', [
            'deposit_id' => $deposit->id,
            'trx' => $deposit->trx,
            'payment_status' => $paymentStatus,
        ]);
        return response()->json(['ok' => true]);
    }

    private function resolveSecretKey(Deposit $deposit, string $gatewayAlias): string
    {
        $gatewayParams = json_decode((string) optional($deposit->gatewayCurrency())->gateway_parameter);
        $fromGatewayCurrency = $this->extractParamValue($gatewayParams, 'secret_key');
        if ($fromGatewayCurrency !== '') {
            return $fromGatewayCurrency;
        }

        $gateway = Gateway::where('alias', $gatewayAlias)->first();
        if (!$gateway) {
            return '';
        }

        $gatewayAccount = json_decode((string) $gateway->gateway_parameters);
        return $this->extractParamValue($gatewayAccount, 'secret_key');
    }

    private function extractParamValue($params, string $key): string
    {
        if (!is_object($params) || !isset($params->{$key})) {
            return '';
        }

        $value = $params->{$key};
        if (is_object($value) && isset($value->value)) {
            return trim((string) $value->value);
        }

        return trim((string) $value);
    }

    private function preparePayloadForSignature(array $payload): string
    {
        $sorted = $payload;
        $this->recursiveKsort($sorted);

        return (string) json_encode($sorted, JSON_UNESCAPED_SLASHES);
    }

    private function recursiveKsort(array &$data): void
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->recursiveKsort($value);
            }
        }
        ksort($data);
    }

    private function isSuccessfulStatus(string $status): bool
    {
        return in_array($status, ['finished', 'confirmed'], true);
    }

    private function isPendingStatus(string $status): bool
    {
        return in_array($status, ['waiting', 'confirming', 'sending', 'partially_paid'], true);
    }

    private function isFailedStatus(string $status): bool
    {
        return in_array($status, ['failed', 'refunded'], true);
    }

    private function isExpiredStatus(string $status): bool
    {
        return in_array($status, ['expired'], true);
    }

    private function syncLocalStatus(Deposit $deposit, int $targetStatus, string $reason): void
    {
        $current = (int) $deposit->status;
        if (!in_array($current, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)) {
            return;
        }

        if ($current === $targetStatus) {
            return;
        }

        $deposit->status = $targetStatus;
        $deposit->save();

        if ($deposit->apiPayment) {
            $deposit->apiPayment->status = $targetStatus;
            $deposit->apiPayment->save();
        }

        Log::info('NowPayments checkout IPN updated local status', [
            'deposit_id' => $deposit->id,
            'trx' => $deposit->trx,
            'from' => $current,
            'to' => $targetStatus,
            'reason' => $reason,
        ]);
    }
}
