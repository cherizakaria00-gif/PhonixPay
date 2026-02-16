<?php

namespace App\Http\Controllers\Gateway\StripeV3;

use App\Constants\Status;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Http\Controllers\Gateway\PaymentController;
use App\Helpers\StripeAccountHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class ProcessController extends Controller
{

    public static function process($deposit)
    {
        // Select the appropriate Stripe account for this deposit
        $stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);

        // Get gateway currency for fallback (legacy support)
        $StripeAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);

        if ($stripeAccount) {
            // Override with selected account credentials
            $StripeAcc->secret_key = $stripeAccount->secret_key;
            $StripeAcc->publishable_key = $stripeAccount->publishable_key;

            // Save the selected account ID to the deposit
            $deposit->stripe_account_id = $stripeAccount->id;
            $deposit->save();

            \Stripe\Stripe::setApiKey($stripeAccount->secret_key);
        } else {
            // Fallback to legacy gateway settings if no Stripe account found
            if (empty($StripeAcc->secret_key)) {
                $send['error'] = true;
                $send['message'] = 'No Stripe credentials available. Please configure Stripe accounts.';
                return json_encode($send);
            }
            \Stripe\Stripe::setApiKey($StripeAcc->secret_key);
        }

        $alias = $deposit->gateway->alias;

        try {
            $metadata = [
                'deposit_id' => $deposit->id,
                'trx' => $deposit->trx,
            ];
            if ($stripeAccount) {
                $metadata['stripe_account_id'] = $stripeAccount->id;
            }

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'unit_amount' => round($deposit->final_amount, 2) * 100,
                        'currency' => strtolower($deposit->method_currency),
                        'product_data' => [
                            'name' => gs('site_name'),
                            'description' => 'Deposit with Stripe',
                            'images' => [siteLogo()],
                        ]
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'cancel_url' => route('home') . $deposit->failed_url,
                'success_url' => route('home') . $deposit->success_url,
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe checkout session creation error', [
                'deposit_id' => $deposit->id,
                'error' => $e->getMessage(),
            ]);
            $send['error'] = true;
            $send['message'] = $e->getMessage();
            return json_encode($send);
        }

        $send['view'] = 'payment.' . $alias;
        $send['session'] = $session;
        $send['StripeJSAcc'] = $StripeAcc;
        $deposit->btc_wallet = json_decode(json_encode($session))->id;
        if (Schema::hasColumn('deposits', 'stripe_session_id')) {
            $deposit->stripe_session_id = $deposit->btc_wallet;
        }
        $deposit->save();

        return json_encode($send);
    }


    public function ipn(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            // Try to get the session ID from the payload to find the deposit
            $json_data = json_decode($payload, true);
            $sessionId = $json_data['data']['object']['id'] ?? null;
            $deposit = null;

            if ($sessionId) {
                $deposit = Deposit::where('btc_wallet', $sessionId)->orderBy('id', 'DESC')->first();
            }

            // Try to verify with the correct account if we found the deposit
            if ($deposit && $deposit->stripe_account_id) {
                $stripeAccount = StripeAccountHelper::getAccountById($deposit->stripe_account_id);
                if ($stripeAccount) {
                    \Stripe\Stripe::setApiKey($stripeAccount->secret_key);
                    $event = $this->verifyWebhookSignature($payload, $sig_header, $stripeAccount);
                }
            }

            // Fallback: try all accounts if no specific account found
            if (!isset($event)) {
                $event = $this->verifyWebhookWithAnyAccount($payload, $sig_header);
            }

            if (!$event) {
                Log::warning('Webhook signature verification failed', [
                    'session_id' => $sessionId ?? 'unknown',
                ]);
                http_response_code(400);
                exit();
            }

            // Handle the checkout.session.completed event
            if ($event->type == 'checkout.session.completed') {
                $session = $event->data->object;
                $deposit = Deposit::where('btc_wallet', $session->id)->orderBy('id', 'DESC')->first();

                if ($deposit && $deposit->status == Status::PAYMENT_INITIATE) {
                    $this->storeStripeChargeId($deposit, $session);
                    PaymentController::userDataUpdate($deposit);
                    Log::info('Deposit updated via StripeV3 webhook', [
                        'deposit_id' => $deposit->id,
                        'session_id' => $session->id,
                    ]);
                }
            }

            http_response_code(200);

        } catch (\Exception $e) {
            Log::error('Stripe V3 webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            http_response_code(400);
        }
    }

    /**
     * Verify webhook signature using a specific Stripe account
     */
    private function verifyWebhookSignature(string $payload, string $sig_header, $stripeAccount)
    {
        try {
            // Get endpoint secret from webhook_secret or fallback to legacy method
            $endpoint_secret = $stripeAccount->webhook_secret ?? '';

            // If not found in StripeAccount, try legacy GatewayCurrency
            if (!$endpoint_secret && $stripeAccount instanceof GatewayCurrency) {
                $gateway_parameter = json_decode($stripeAccount->gateway_parameter ?? '{}');
                $endpoint_secret = $gateway_parameter->end_point ?? '';
            }

            if (!$endpoint_secret) {
                return null;
            }

            return \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Try to verify webhook with any active account
     */
    private function verifyWebhookWithAnyAccount(string $payload, string $sig_header)
    {
        $accounts = StripeAccountHelper::getActiveAccounts();

        foreach ($accounts as $account) {
            $event = $this->verifyWebhookSignature($payload, $sig_header, $account);
            if ($event) {
                return $event;
            }
        }

        // Fallback to legacy method (get from GatewayCurrency)
        $StripeAcc = GatewayCurrency::where('gateway_alias', 'StripeV3')->orderBy('id', 'desc')->first();
        if (!$StripeAcc) {
            return null;
        }

        $gateway_parameter = json_decode($StripeAcc->gateway_parameter ?? '{}');
        if (!isset($gateway_parameter->end_point)) {
            return null;
        }

        try {
            return \Stripe\Webhook::constructEvent($payload, $sig_header, $gateway_parameter->end_point);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return null;
        }
    }

    private function storeStripeChargeId(Deposit $deposit, $session): void
    {
        if (Schema::hasColumn('deposits', 'stripe_session_id')) {
            $deposit->stripe_session_id = $session->id;
        }

        $secretKey = $deposit->stripeAccount->secret_key ?? null;
        if (!$secretKey) {
            $gatewayParams = json_decode($deposit->gatewayCurrency()->gateway_parameter ?? '{}');
            $secretKey = $gatewayParams->secret_key ?? null;
        }

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
                Log::warning('Unable to resolve Stripe charge id', [
                    'deposit_id' => $deposit->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $deposit->save();
    }
}
