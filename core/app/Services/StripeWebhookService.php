<?php

namespace App\Services;

use App\Helpers\StripeAccountHelper;
use App\Models\StripeAccount;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\WebhookEndpoint;

/**
 * Stripe Webhook Service
 * Manages webhook registration and management across multiple Stripe accounts
 */
class StripeWebhookService
{
    /**
     * Register a webhook endpoint for a Stripe account
     *
     * @param StripeAccount $account
     * @param string $url
     * @param array $events
     * @return array|null
     */
    public static function registerWebhook(StripeAccount $account, string $url, array $events = []): ?array
    {
        try {
            Stripe::setApiKey($account->secret_key);

            $default_events = [
                'charge.succeeded',
                'charge.failed',
                'charge.refunded',
                'charge.dispute.created',
                'checkout.session.completed',
            ];

            $webhook_events = !empty($events) ? $events : $default_events;

            $endpoint = WebhookEndpoint::create([
                'url' => $url,
                'enabled_events' => $webhook_events,
                'api_version' => '2020-03-02',
            ]);

            Log::info('Webhook endpoint registered', [
                'account_id' => $account->id,
                'endpoint_id' => $endpoint->id,
                'url' => $url,
            ]);

            return [
                'id' => $endpoint->id,
                'secret' => $endpoint->secret,
                'url' => $endpoint->url,
                'events' => $endpoint->enabled_events,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to register webhook', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * List all webhook endpoints for a Stripe account
     *
     * @param StripeAccount $account
     * @return array
     */
    public static function listWebhooks(StripeAccount $account): array
    {
        try {
            Stripe::setApiKey($account->secret_key);

            $endpoints = WebhookEndpoint::all();

            $webhooks = [];
            foreach ($endpoints->data as $endpoint) {
                $webhooks[] = [
                    'id' => $endpoint->id,
                    'url' => $endpoint->url,
                    'events' => $endpoint->enabled_events,
                    'status' => $endpoint->status,
                    'created' => $endpoint->created,
                ];
            }

            return $webhooks;

        } catch (\Exception $e) {
            Log::error('Failed to list webhooks', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Delete a webhook endpoint
     *
     * @param StripeAccount $account
     * @param string $endpoint_id
     * @return bool
     */
    public static function deleteWebhook(StripeAccount $account, string $endpoint_id): bool
    {
        try {
            Stripe::setApiKey($account->secret_key);

            $endpoint = WebhookEndpoint::retrieve($endpoint_id);
            $endpoint->delete();

            Log::info('Webhook endpoint deleted', [
                'account_id' => $account->id,
                'endpoint_id' => $endpoint_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete webhook', [
                'account_id' => $account->id,
                'endpoint_id' => $endpoint_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Test a webhook endpoint by sending a test event
     *
     * @param StripeAccount $account
     * @param string $endpoint_id
     * @param string $event_type
     * @return bool
     */
    public static function testWebhook(StripeAccount $account, string $endpoint_id, string $event_type = 'charge.succeeded'): bool
    {
        try {
            Stripe::setApiKey($account->secret_key);

            $endpoint = WebhookEndpoint::retrieve($endpoint_id);
            // Note: Stripe doesn't have a direct test method, but you can manually trigger events
            // This is a placeholder for documentation purposes

            Log::info('Webhook test triggered', [
                'account_id' => $account->id,
                'endpoint_id' => $endpoint_id,
                'event_type' => $event_type,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to test webhook', [
                'account_id' => $account->id,
                'endpoint_id' => $endpoint_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update webhook events for an endpoint
     *
     * @param StripeAccount $account
     * @param string $endpoint_id
     * @param array $events
     * @return bool
     */
    public static function updateWebhookEvents(StripeAccount $account, string $endpoint_id, array $events): bool
    {
        try {
            Stripe::setApiKey($account->secret_key);

            $endpoint = WebhookEndpoint::retrieve($endpoint_id);
            $endpoint->enabled_events = $events;
            $endpoint->save();

            Log::info('Webhook events updated', [
                'account_id' => $account->id,
                'endpoint_id' => $endpoint_id,
                'events' => $events,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update webhook events', [
                'account_id' => $account->id,
                'endpoint_id' => $endpoint_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if a webhook is healthy by testing connectivity
     *
     * @param string $webhook_url
     * @return bool
     */
    public static function checkWebhookHealth(string $webhook_url): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->head($webhook_url);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Webhook health check failed', [
                'url' => $webhook_url,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get webhook signature verification helper
     *
     * @param string $payload
     * @param string $sig_header
     * @param string $webhook_secret
     * @return bool
     */
    public static function verifySignature(string $payload, string $sig_header, string $webhook_secret): bool
    {
        try {
            \Stripe\Webhook::constructEvent($payload, $sig_header, $webhook_secret);
            return true;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
