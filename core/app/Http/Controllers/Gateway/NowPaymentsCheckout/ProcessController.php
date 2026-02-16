<?php

namespace App\Http\Controllers\Gateway\NowPaymentsCheckout;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Lib\CurlRequest;
use App\Models\Deposit;
use Illuminate\Support\Facades\Log;

class ProcessController extends Controller {
    public static function process($deposit) {
        $nowPaymentsAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);
        $successUrl = self::buildReturnUrl($deposit->success_url);
        $cancelUrl = self::buildReturnUrl($deposit->failed_url);
        $responseRaw       = CurlRequest::curlPostContent('https://api.nowpayments.io/v1/invoice', json_encode([
            'price_amount'     => $deposit->final_amount,
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

    public function ipn() {
        if (isset($_SERVER['HTTP_X_NOWPAYMENTS_SIG']) && !empty($_SERVER['HTTP_X_NOWPAYMENTS_SIG'])) {
            $recivedHmac = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'];
            $requestJson = file_get_contents('php://input');
            $requestData = json_decode($requestJson, true);
            $deposit = Deposit::where('status', 0)->where('trx', $requestData['order_id'])->first();
            if ($deposit) {
                ksort($requestData);
                $sorted_requestJson = json_encode($requestData, JSON_UNESCAPED_SLASHES);
                if ($requestJson !== false && !empty($requestJson)) {
                    $gatewayAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);
                    $hmac       = hash_hmac("sha512", $sorted_requestJson, trim($gatewayAcc->secret_key));
                    if ($hmac == $recivedHmac) {
                        if ($requestData['payment_status']=='confirmed' || $requestData['payment_status']=='finished') {
                            if ($requestData['actually_paid'] == $requestData['pay_amount']) {
                                PaymentController::userDataUpdate($deposit);
                            }
                        }
                    }
                }
            }
        }
    }
}
