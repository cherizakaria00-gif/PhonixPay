<?php

namespace App\Http\Controllers\Gateway\StripePaymentLink;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProcessController extends Controller
{
    protected const ZERO_DECIMAL_CURRENCIES = [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA',
        'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    public static function process($deposit)
    {
        $stripeAccount = selectStripeAccount($deposit);
        $gatewayParams = json_decode($deposit->gatewayCurrency()->gateway_parameter ?? '{}');

        if ($stripeAccount) {
            $deposit->stripe_account_id = $stripeAccount->id;
            $deposit->save();

            \Stripe\Stripe::setApiKey($stripeAccount->secret_key);
        } else {
            // Fallback to legacy gateway settings if no Stripe account found
            if (empty($gatewayParams->secret_key)) {
                return json_encode([
                    'error' => true,
                    'message' => 'Stripe credentials not configured',
                ]);
            }
            \Stripe\Stripe::setApiKey($gatewayParams->secret_key);
        }

        $amount = self::toStripeAmount($deposit->final_amount, $deposit->method_currency);
        $currency = strtolower($deposit->method_currency);
        $successRedirectUrl = route('payment.redirect.success', $deposit->id);

        try {
            $product = \Stripe\Product::create([
                'name' => gs('site_name') . ' Deposit',
                'description' => 'Deposit ' . $deposit->trx,
                'metadata' => [
                    'deposit_id' => $deposit->id,
                    'deposit_trx' => $deposit->trx,
                ],
            ]);

            $price = \Stripe\Price::create([
                'unit_amount' => $amount,
                'currency' => $currency,
                'product' => $product->id,
                'metadata' => [
                    'deposit_id' => $deposit->id,
                    'deposit_trx' => $deposit->trx,
                ],
            ]);

            $paymentLink = \Stripe\PaymentLink::create([
                'after_completion' => [
                    'type' => 'redirect',
                    'redirect' => [
                        'url' => $successRedirectUrl,
                    ],
                ],
                'line_items' => [[
                    'price' => $price->id,
                    'quantity' => 1,
                ]],
                'payment_intent_data' => [
                    'metadata' => [
                        'deposit_id' => $deposit->id,
                        'deposit_trx' => $deposit->trx,
                    ],
                ],
                'metadata' => array_filter([
                    'deposit_id' => $deposit->id,
                    'deposit_trx' => $deposit->trx,
                    'gateway' => 'StripePaymentLink',
                    'stripe_account_id' => $stripeAccount ? $stripeAccount->id : null,
                ], function ($value) {
                    return $value !== null && $value !== '';
                }),
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }

        $deposit->btc_wallet = $paymentLink->id;
        $deposit->save();

        return json_encode([
            'redirect' => true,
            'redirect_url' => $paymentLink->url,
        ]);
    }

    public function ipn(Request $request)
    {
        $gatewayCurrency = GatewayCurrency::where('gateway_alias', 'StripePaymentLink')
            ->orderBy('id', 'desc')
            ->first();

        if (!$gatewayCurrency) {
            http_response_code(400);
            exit();
        }

        $gatewayParameter = json_decode($gatewayCurrency->gateway_parameter);
        \Stripe\Stripe::setApiKey($gatewayParameter->secret_key);

        $endpointSecret = $gatewayParameter->end_point ?? null;
        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

        if (!$endpointSecret || !$sigHeader) {
            http_response_code(200);
            return;
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            exit();
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $paymentLinkId = $session->payment_link ?? null;

            if ($paymentLinkId) {
                $deposit = Deposit::where('btc_wallet', $paymentLinkId)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($deposit && $deposit->status == Status::PAYMENT_INITIATE) {
                    $this->storeStripeChargeId($deposit, $session, $gatewayParameter->secret_key ?? null);
                    PaymentController::userDataUpdate($deposit);
                }
            }
        }

        http_response_code(200);
    }

    protected static function toStripeAmount($amount, $currency)
    {
        $currency = strtoupper($currency);
        $multiplier = in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true) ? 1 : 100;
        return (int) round($amount * $multiplier);
    }

    private function storeStripeChargeId(Deposit $deposit, $session, ?string $fallbackSecretKey = null): void
    {
        if (Schema::hasColumn('deposits', 'stripe_session_id')) {
            $deposit->stripe_session_id = $session->id;
        }

        $secretKey = $deposit->stripeAccount->secret_key ?? $fallbackSecretKey;
        if ($secretKey && !empty($session->payment_intent)) {
            try {
                \Stripe\Stripe::setApiKey($secretKey);
                $intent = \Stripe\PaymentIntent::retrieve($session->payment_intent, []);
                if (!empty($intent->latest_charge)) {
                    if (Schema::hasColumn('deposits', 'stripe_charge_id')) {
                        $deposit->stripe_charge_id = $intent->latest_charge;
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors and continue, refund will fall back if possible
            }
        }

        $deposit->save();
    }
}
