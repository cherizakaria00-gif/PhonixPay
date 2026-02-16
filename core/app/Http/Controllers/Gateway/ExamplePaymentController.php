<?php

/**
 * STRIPE ACCOUNT SWITCHING - COMPLETE EXAMPLE
 * ============================================
 * 
 * This file demonstrates how to use the multi-account Stripe system
 * in your payment processing workflow.
 */

namespace App\Http\Controllers\Gateway\Example;

use App\Constants\Status;
use App\Helpers\StripeAccountHelper;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\Stripe;

class ExamplePaymentController
{
    /**
     * EXAMPLE 1: Basic Payment Processing with Account Selection
     */
    public static function processPaymentBasicExample($deposit)
    {
        // Step 1: Select the appropriate Stripe account for this deposit
        $stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);

        if (!$stripeAccount) {
            Log::error('No Stripe account available', ['deposit_id' => $deposit->id]);
            return ['error' => 'Payment gateway unavailable'];
        }

        // Step 2: Set Stripe API key from selected account
        Stripe::setApiKey($stripeAccount->secret_key);

        // Step 3: Save the account ID to deposit for later reference
        $deposit->stripe_account_id = $stripeAccount->id;
        $deposit->save();

        // Step 4: Process payment
        try {
            $charge = \Stripe\Charge::create([
                'amount' => round($deposit->final_amount, 2) * 100,
                'currency' => strtolower($deposit->method_currency),
                'source' => 'tok_visa', // In real scenario, get from request
                'description' => "Deposit {$deposit->trx}",
                'metadata' => [
                    'deposit_id' => $deposit->id,
                    'trx' => $deposit->trx,
                    'stripe_account_id' => $stripeAccount->id,
                ],
            ]);

            // Step 5: Update deposit status
            PaymentController::userDataUpdate($deposit);

            return ['success' => true, 'charge_id' => $charge->id];

        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'deposit_id' => $deposit->id,
                'account_id' => $stripeAccount->id,
                'error' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * EXAMPLE 2: Checkout Session with Account Selection
     */
    public static function createCheckoutSessionExample($deposit)
    {
        // Select appropriate account
        $stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);

        if (!$stripeAccount) {
            return ['error' => 'Payment gateway unavailable'];
        }

        Stripe::setApiKey($stripeAccount->secret_key);
        $deposit->stripe_account_id = $stripeAccount->id;
        $deposit->save();

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'unit_amount' => round($deposit->final_amount, 2) * 100,
                        'currency' => strtolower($deposit->method_currency),
                        'product_data' => [
                            'name' => 'Deposit',
                            'description' => "Deposit {$deposit->trx}",
                        ]
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success', $deposit->id),
                'cancel_url' => route('payment.cancel', $deposit->id),
                'metadata' => [
                    'deposit_id' => $deposit->id,
                    'trx' => $deposit->trx,
                    'account_id' => $stripeAccount->id,
                ],
            ]);

            return ['success' => true, 'session' => $session];

        } catch (\Exception $e) {
            Log::error('Checkout session creation error', [
                'deposit_id' => $deposit->id,
                'error' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * EXAMPLE 3: Refund Processing with Correct Account
     */
    public static function refundPaymentExample(Deposit $deposit, float $amount = null)
    {
        // Important: Use the same account that processed the original charge
        if (!$deposit->stripe_account_id) {
            Log::error('No Stripe account associated with deposit', ['deposit_id' => $deposit->id]);
            return ['error' => 'Cannot refund: account information missing'];
        }

        $stripeAccount = StripeAccountHelper::getAccountById($deposit->stripe_account_id);

        if (!$stripeAccount) {
            Log::error('Stripe account not found', ['account_id' => $deposit->stripe_account_id]);
            return ['error' => 'Stripe account not found'];
        }

        Stripe::setApiKey($stripeAccount->secret_key);

        try {
            $refund = \Stripe\Refund::create([
                'charge' => $deposit->stripe_charge_id,
                'amount' => $amount ? round($amount, 2) * 100 : null,
                'metadata' => [
                    'deposit_id' => $deposit->id,
                    'original_amount' => $deposit->final_amount,
                ],
            ]);

            $deposit->status = Status::PAYMENT_REFUNDED;
            $deposit->save();

            Log::info('Payment refunded', [
                'deposit_id' => $deposit->id,
                'refund_id' => $refund->id,
                'amount' => $amount ?? $deposit->final_amount,
            ]);

            return ['success' => true, 'refund_id' => $refund->id];

        } catch (\Exception $e) {
            Log::error('Refund processing error', [
                'deposit_id' => $deposit->id,
                'error' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * EXAMPLE 4: Webhook Handler with Multi-Account Support
     */
    public function handleWebhookExample(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        try {
            // Try all active accounts to verify signature
            $accounts = StripeAccountHelper::getActiveAccounts();
            $event = null;

            foreach ($accounts as $account) {
                try {
                    Stripe::setApiKey($account->secret_key);
                    $event = \Stripe\Webhook::constructEvent(
                        $payload,
                        $sig_header,
                        $account->webhook_secret ?? env('STRIPE_WEBHOOK_SECRET')
                    );
                    break; // Found the correct account
                } catch (\Exception $e) {
                    continue; // Try next account
                }
            }

            if (!$event) {
                Log::warning('Webhook signature verification failed');
                return response()->json(['error' => 'Signature verification failed'], 400);
            }

            // Handle event
            switch ($event->type) {
                case 'charge.succeeded':
                    return $this->handleChargeSucceeded($event->data->object);

                case 'charge.failed':
                    return $this->handleChargeFailed($event->data->object);

                default:
                    Log::info('Unhandled webhook event', ['type' => $event->type]);
                    return response()->json(['status' => 'received'], 200);
            }

        } catch (\Exception $e) {
            Log::error('Webhook processing error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function handleChargeSucceeded($charge)
    {
        $depositId = $charge->metadata['deposit_id'] ?? null;

        if (!$depositId) {
            return response()->json(['status' => 'received'], 200);
        }

        $deposit = Deposit::find($depositId);

        if ($deposit && $deposit->status != Status::PAYMENT_SUCCESS) {
            $deposit->status = Status::PAYMENT_SUCCESS;
            if (Schema::hasColumn('deposits', 'stripe_charge_id')) {
                $deposit->stripe_charge_id = $charge->id;
            }
            $deposit->save();

            PaymentController::userDataUpdate($deposit);
        }

        return response()->json(['status' => 'processed'], 200);
    }

    private function handleChargeFailed($charge)
    {
        $depositId = $charge->metadata['deposit_id'] ?? null;

        if (!$depositId) {
            return response()->json(['status' => 'received'], 200);
        }

        $deposit = Deposit::find($depositId);

        if ($deposit) {
            $deposit->status = Status::PAYMENT_FAILED;
            $deposit->save();

            Log::warning('Charge failed', [
                'deposit_id' => $depositId,
                'failure_reason' => $charge->failure_reason,
            ]);
        }

        return response()->json(['status' => 'processed'], 200);
    }

    /**
     * EXAMPLE 5: List All Available Accounts (for admin display)
     */
    public static function listAvailableAccountsExample()
    {
        $accounts = StripeAccountHelper::getActiveAccounts();

        $data = $accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'name' => $account->name,
                'min_amount' => $account->min_amount,
                'max_amount' => $account->max_amount ?: 'Unlimited',
                'status' => $account->is_active ? 'Active' : 'Inactive',
            ];
        });

        return $data;
    }

    /**
     * EXAMPLE 6: Validate Account Before Using
     */
    public static function validateAccountExample($accountId)
    {
        $account = StripeAccountHelper::getAccountById($accountId);

        if (!$account) {
            return ['valid' => false, 'message' => 'Account not found'];
        }

        if (!$account->is_active) {
            return ['valid' => false, 'message' => 'Account is inactive'];
        }

        if (!StripeAccountHelper::validateCredentials($account)) {
            return ['valid' => false, 'message' => 'Invalid credentials'];
        }

        return ['valid' => true, 'message' => 'Account is valid', 'account' => $account];
    }
}
