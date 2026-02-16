<?php

namespace App\Http\Controllers\Gateway;

use App\Constants\Status;
use App\Helpers\StripeAccountHelper;
use App\Models\Deposit;
use App\Models\StripeAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\Stripe;
use Stripe\WebhookEndpoint;

/**
 * Stripe Webhook Controller
 * Handles Stripe webhook events including payment confirmations
 * Validates webhook signatures using the correct Stripe account's webhook secret
 */
class StripeWebhookController extends \App\Http\Controllers\Controller
{
    /**
     * Handle incoming webhook events from Stripe
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        // Get the Stripe account ID from request (if provided)
        // This should be stored in the charge metadata or session
        $stripeAccountId = $request->input('stripe_account_id');

        try {
            // If we have account ID in request, use that account's webhook secret
            if ($stripeAccountId) {
                $stripeAccount = StripeAccountHelper::getAccountById($stripeAccountId);
                if (!$stripeAccount) {
                    Log::warning('Stripe account not found for webhook', [
                        'account_id' => $stripeAccountId,
                    ]);
                    return response()->json(['error' => 'Account not found'], 404);
                }
            } else {
                // Try to verify with all active accounts (for backward compatibility)
                $event = $this->verifyWebhookWithAnyAccount($payload, $sig_header);
                if (!$event) {
                    Log::warning('Webhook signature verification failed for all accounts', [
                        'ip' => $request->ip(),
                    ]);
                    return response()->json(['error' => 'Signature verification failed'], 400);
                }
            }

            // If we have a specific account, verify with that account's webhook secret
            if (isset($stripeAccount)) {
                $event = $this->verifyWebhookSignature($payload, $sig_header, $stripeAccount);
                if (!$event) {
                    Log::warning('Webhook signature verification failed', [
                        'account_id' => $stripeAccount->id,
                    ]);
                    return response()->json(['error' => 'Signature verification failed'], 400);
                }
            }

            // Handle the event based on type
            return $this->processWebhookEvent($event, isset($stripeAccount) ? $stripeAccount : null);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Verify webhook signature using a specific Stripe account
     *
     * @param string $payload
     * @param string $sig_header
     * @param StripeAccount $account
     * @return \Stripe\Event|null
     */
    private function verifyWebhookSignature(string $payload, string $sig_header, StripeAccount $account)
    {
        try {
            // This assumes webhook secret is stored in Stripe account
            // You may need to extend StripeAccount model with webhook_secret field
            $webhookSecret = $account->webhook_secret ?? env('STRIPE_WEBHOOK_SECRET');

            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhookSecret
            );

            return $event;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Webhook signature verification exception', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Try to verify webhook signature with any active account
     * Used as fallback when account ID is not provided
     *
     * @param string $payload
     * @param string $sig_header
     * @return \Stripe\Event|null
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

        return null;
    }

    /**
     * Process different types of Stripe webhook events
     *
     * @param \Stripe\Event $event
     * @param StripeAccount|null $account
     * @return \Illuminate\Http\Response
     */
    private function processWebhookEvent(\Stripe\Event $event, ?StripeAccount $account = null)
    {
        switch ($event->type) {
            case 'charge.succeeded':
                return $this->handleChargeSucceeded($event->data->object, $account);

            case 'charge.failed':
                return $this->handleChargeFailed($event->data->object, $account);

            case 'charge.refunded':
                return $this->handleChargeRefunded($event->data->object, $account);

            case 'charge.dispute.created':
                return $this->handleDisputeCreated($event->data->object, $account);

            case 'checkout.session.completed':
                return $this->handleCheckoutSessionCompleted($event->data->object, $account);

            default:
                Log::info('Unhandled webhook event type', [
                    'type' => $event->type,
                    'event_id' => $event->id,
                ]);
                return response()->json(['status' => 'received'], 200);
        }
    }

    /**
     * Handle charge.succeeded event
     *
     * @param \Stripe\Charge $charge
     * @param StripeAccount|null $account
     * @return \Illuminate\Http\Response
     */
    private function handleChargeSucceeded($charge, ?StripeAccount $account = null)
    {
        try {
            $depositId = $charge->metadata['deposit_id'] ?? null;

            if (!$depositId) {
                Log::warning('Charge succeeded but no deposit_id in metadata', [
                    'charge_id' => $charge->id,
                ]);
                return response()->json(['status' => 'received'], 200);
            }

            $deposit = Deposit::find($depositId);

            if (!$deposit) {
                Log::warning('Deposit not found for charge succeeded event', [
                    'deposit_id' => $depositId,
                    'charge_id' => $charge->id,
                ]);
                return response()->json(['status' => 'received'], 200);
            }

            // Update deposit status if not already successful
            if ($deposit->status != Status::PAYMENT_SUCCESS) {
                $deposit->status = Status::PAYMENT_SUCCESS;
                if (Schema::hasColumn('deposits', 'stripe_charge_id')) {
                    $deposit->stripe_charge_id = $charge->id;
                }
                $deposit->stripe_account_id = $account->id ?? $deposit->stripe_account_id;
                $deposit->save();

                // Update user balance and other data
                PaymentController::userDataUpdate($deposit);

                Log::info('Deposit marked as successful via webhook', [
                    'deposit_id' => $deposit->id,
                    'charge_id' => $charge->id,
                    'account_id' => $account->id ?? 'unknown',
                ]);
            }

            return response()->json(['status' => 'processed'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing charge succeeded event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle charge.failed event
     *
     * @param \Stripe\Charge $charge
     * @param StripeAccount|null $account
     * @return \Illuminate\Http\Response
     */
    private function handleChargeFailed($charge, ?StripeAccount $account = null)
    {
        try {
            $depositId = $charge->metadata['deposit_id'] ?? null;

            if (!$depositId) {
                return response()->json(['status' => 'received'], 200);
            }

            $deposit = Deposit::find($depositId);

            if (!$deposit) {
                return response()->json(['status' => 'received'], 200);
            }

            $deposit->status = Status::PAYMENT_FAILED;
            if (Schema::hasColumn('deposits', 'stripe_charge_id')) {
                $deposit->stripe_charge_id = $charge->id;
            }
            $deposit->stripe_account_id = $account->id ?? $deposit->stripe_account_id;
            $deposit->save();

            Log::info('Deposit marked as failed via webhook', [
                'deposit_id' => $deposit->id,
                'charge_id' => $charge->id,
                'failure_reason' => $charge->failure_reason ?? 'unknown',
            ]);

            return response()->json(['status' => 'processed'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing charge failed event', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle charge.refunded event
     *
     * @param \Stripe\Charge $charge
     * @param StripeAccount|null $account
     * @return \Illuminate\Http\Response
     */
    private function handleChargeRefunded($charge, ?StripeAccount $account = null)
    {
        try {
            $depositId = $charge->metadata['deposit_id'] ?? null;

            if (!$depositId) {
                return response()->json(['status' => 'received'], 200);
            }

            $deposit = Deposit::find($depositId);

            if (!$deposit) {
                return response()->json(['status' => 'received'], 200);
            }

            // Manual refund only: do not update local balances from Stripe webhook
            $deposit->stripe_account_id = $account->id ?? $deposit->stripe_account_id;
            $deposit->save();

            Log::info('Stripe refund received (manual refund required)', [
                'deposit_id' => $deposit->id,
                'charge_id' => $charge->id,
                'refunded_amount' => $charge->refunded,
            ]);

            return response()->json(['status' => 'processed'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing charge refunded event', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle charge.dispute.created event
     *
     * @param \Stripe\Dispute $dispute
     * @param StripeAccount|null $account
     * @return \Illuminate\Http\Response
     */
    private function handleDisputeCreated($dispute, ?StripeAccount $account = null)
    {
        try {
            Log::warning('Stripe dispute created', [
                'dispute_id' => $dispute->id,
                'charge_id' => $dispute->charge,
                'amount' => $dispute->amount,
                'account_id' => $account->id ?? 'unknown',
            ]);

            return response()->json(['status' => 'processed'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing dispute created event', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle checkout.session.completed event (for Stripe Checkout/Payment Link)
     *
     * @param \Stripe\Checkout\Session $session
     * @param StripeAccount|null $account
     * @return \Illuminate\Http\Response
     */
    private function handleCheckoutSessionCompleted($session, ?StripeAccount $account = null)
    {
        try {
            $depositId = $session->metadata['deposit_id'] ?? null;

            if (!$depositId) {
                return response()->json(['status' => 'received'], 200);
            }

            $deposit = Deposit::find($depositId);

            if (!$deposit) {
                return response()->json(['status' => 'received'], 200);
            }

            // Only update if payment intent succeeded
            if ($session->payment_status === 'paid' && $deposit->status != Status::PAYMENT_SUCCESS) {
                $deposit->status = Status::PAYMENT_SUCCESS;
                $deposit->stripe_session_id = $session->id;
                $deposit->stripe_account_id = $account->id ?? $deposit->stripe_account_id;
                $deposit->save();

                PaymentController::userDataUpdate($deposit);

                Log::info('Deposit marked as successful via checkout webhook', [
                    'deposit_id' => $deposit->id,
                    'session_id' => $session->id,
                ]);
            }

            return response()->json(['status' => 'processed'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing checkout session completed event', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
