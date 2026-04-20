<?php

namespace App\Services\Didit;

use App\Models\DiditVerificationSession;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DiditSessionService
{
    public function configStatus(): array
    {
        [$apiKey, $apiKeySource] = $this->resolveValue('api_key');
        [$workflowId, $workflowIdSource] = $this->resolveValue('workflow_id');
        [$webhookSecret, $webhookSecretSource] = $this->resolveValue('webhook_secret');
        [$baseUrl, $baseUrlSource] = $this->resolveValue('base_url', 'https://verification.didit.me');
        [$callbackUrlConfigured, $callbackUrlSource] = $this->resolveValue('callback_url');

        $issues = [];
        $warnings = [];

        if ($apiKey === '') {
            $issues[] = 'Missing API key.';
        }

        if ($workflowId === '') {
            $issues[] = 'Missing workflow ID.';
        } elseif (!$this->looksLikeUuid($workflowId)) {
            $issues[] = 'Workflow ID must be a UUID.';
        }

        if ($webhookSecret === '') {
            $warnings[] = 'Webhook secret is not set. Webhook verification will fail until configured.';
        }

        if ($baseUrl === '') {
            $issues[] = 'Base URL is missing.';
        } elseif (!str_starts_with($baseUrl, 'https://')) {
            $warnings[] = 'Base URL should be https.';
        }

        if ($callbackUrlConfigured !== '' && !str_starts_with($callbackUrlConfigured, 'https://')) {
            $warnings[] = 'Callback URL should be https (Didit usually requires a publicly reachable https callback).';
        }

        $callbackUrlEffective = $this->callbackUrl();
        if ($callbackUrlEffective === '') {
            $warnings[] = 'Callback URL is not configured (or not https). The verification flow may still work, but web callbacks may not.';
        }

        return [
            'configured' => $apiKey !== '' && $workflowId !== '' && $this->looksLikeUuid($workflowId),
            'effective' => [
                'api_key' => $apiKey,
                'workflow_id' => $workflowId,
                'webhook_secret' => $webhookSecret,
                'base_url' => $baseUrl,
                'callback_url_configured' => $callbackUrlConfigured,
                'callback_url_effective' => $callbackUrlEffective,
            ],
            'sources' => [
                'api_key' => $apiKeySource,
                'workflow_id' => $workflowIdSource,
                'webhook_secret' => $webhookSecretSource,
                'base_url' => $baseUrlSource,
                'callback_url' => $callbackUrlSource,
            ],
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '' && $this->workflowId() !== '';
    }

    public function createSessionForUser(User $user): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Identity verification is not configured.');
        }

        $this->validateWorkflowId();

        $callback = $this->callbackUrl();
        $payload = [
            'workflow_id' => $this->workflowId(),
            'vendor_data' => (string) $user->id,
        ];
        if ($callback !== '') {
            $payload['callback'] = $callback;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->apiKey(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post(rtrim($this->baseUrl(), '/') . '/v3/session/', $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Unable to connect to identity verification provider.');
        }

        if (!$response->successful()) {
            $message = $this->extractErrorMessage($response->json(), $response->body());

            Log::warning('didit_session_create_failed', [
                'status' => $response->status(),
                'message' => $message,
                'body' => $response->body(),
            ]);

            throw new RuntimeException("Didit API error ({$response->status()}): {$message}");
        }

        $data = $response->json();
        $sessionId = (string) Arr::get($data, 'session_id', '');

        if ($sessionId === '') {
            throw new RuntimeException('Verification provider returned an invalid session response.');
        }

        $status = $this->normalizeStatus((string) Arr::get($data, 'status', 'Not Started'));

        $session = DiditVerificationSession::query()->updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $user->id,
                'workflow_id' => (string) Arr::get($payload, 'workflow_id'),
                'status' => $status,
                'vendor_data' => (string) Arr::get($payload, 'vendor_data'),
                'verification_url' => (string) Arr::get($data, 'verification_url'),
                'opened_at' => now(),
            ]
        );

        $user->identity_verification_status = $status;
        $user->didit_last_session_id = $session->session_id;
        $user->save();

        return [
            'session_id' => $sessionId,
            'session_token' => (string) Arr::get($data, 'session_token'),
            'verification_url' => (string) Arr::get($data, 'verification_url'),
            'status' => $status,
        ];
    }

    public function verifyWebhookSignature(string $rawBody, string $incomingSignature): bool
    {
        $secret = $this->webhookSecret();

        if ($secret === '') {
            return false;
        }

        $signature = trim($incomingSignature);
        if ($signature === '') {
            return false;
        }

        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        $calculated = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($calculated, $signature);
    }

    public function normalizeStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'approved' => 'approved',
            'declined' => 'declined',
            'in review' => 'in_review',
            'in progress' => 'in_progress',
            'abandoned' => 'abandoned',
            'expired' => 'expired',
            'not started' => 'not_started',
            default => strtolower(trim(str_replace(' ', '_', $status))),
        };
    }

    private function apiKey(): string
    {
        return $this->getValue('api_key');
    }

    private function workflowId(): string
    {
        return $this->getValue('workflow_id');
    }

    private function webhookSecret(): string
    {
        return $this->getValue('webhook_secret');
    }

    private function baseUrl(): string
    {
        return $this->getValue('base_url', 'https://verification.didit.me');
    }

    private function callbackUrl(): string
    {
        $configured = $this->getValue('callback_url');
        if ($configured !== '') {
            return $configured;
        }

        $generated = route('user.identity.callback');

        // Didit callback must be publicly reachable and usually HTTPS.
        if (str_starts_with($generated, 'https://')) {
            return $generated;
        }

        return '';
    }

    private function getValue(string $key, string $default = ''): string
    {
        [$value] = $this->resolveValue($key, $default);
        return $value;
    }

    private function resolveValue(string $key, string $default = ''): array
    {
        $db = gs('didit_config');
        $dbValue = trim((string) data_get($db, $key, ''));
        if ($dbValue !== '') {
            return [$dbValue, 'db'];
        }

        $envValue = trim((string) config('services.didit.' . $key, $default));
        if ($envValue !== '') {
            return [$envValue, 'env'];
        }

        return [trim($default), 'default'];
    }

    private function looksLikeUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
    }

    private function validateWorkflowId(): void
    {
        $workflowId = $this->workflowId();

        if ($workflowId === '') {
            throw new RuntimeException('Didit workflow ID is not configured.');
        }

        if (!$this->looksLikeUuid($workflowId)) {
            throw new RuntimeException('Didit workflow ID is misconfigured. Set DIDIT_WORKFLOW_ID to the workflow UUID from the Didit dashboard.');
        }
    }

    private function extractErrorMessage(array|string|null $json, string $rawBody): string
    {
        if (is_array($json)) {
            $workflowError = trim((string) Arr::get($json, 'workflow_id', ''));
            if ($workflowError !== '') {
                return 'Didit workflow ID is invalid. Update DIDIT_WORKFLOW_ID with the workflow UUID from the Didit dashboard.';
            }

            foreach (['message', 'detail', 'error', 'error_description'] as $key) {
                $value = trim((string) Arr::get($json, $key, ''));
                if ($value !== '') {
                    return $value;
                }
            }

            $errors = Arr::get($json, 'errors');
            if (is_array($errors) && !empty($errors)) {
                return trim((string) json_encode($errors)) ?: 'Verification session creation failed.';
            }
        }

        $body = trim($rawBody);
        if ($body !== '') {
            return mb_substr($body, 0, 400);
        }

        return 'Verification session creation failed.';
    }
}
