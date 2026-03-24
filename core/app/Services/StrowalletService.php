<?php

namespace App\Services;

use App\Models\VirtualCardSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class StrowalletService
{
    private string $baseUrl;
    private string $secretKey;
    private array $endpoints;
    private array $defaultEndpoints;
    private int $timeout;
    private string $publicKey = '';
    private string $developerCode = '';
    private string $mode = 'sandbox';
    private array $legacyEndpointAliases = [
        'withdraw_from_card' => [
            '/withdraw-from-card' => '/card_withdraw',
            'withdraw-from-card' => '/card_withdraw',
        ],
        'card_withdraw_status' => [
            '/get-card-withdraw-status' => '/getcard_withdrawstatus',
            'get-card-withdraw-status' => '/getcard_withdrawstatus',
        ],
    ];

    public function __construct()
    {
        $this->baseUrl = (string) config('strowallet.base_url');
        $this->secretKey = (string) config('strowallet.secret_key');
        $this->defaultEndpoints = (array) config('strowallet.endpoints', []);
        $this->endpoints = $this->defaultEndpoints;
        $this->timeout = (int) config('strowallet.timeout', 30);
        $this->publicKey = (string) config('strowallet.public_key');
        $this->developerCode = (string) config('strowallet.developer_code', '');
        $this->mode = strtolower((string) config('strowallet.mode', 'sandbox'));

        if (Schema::hasTable('virtual_card_settings')) {
            $setting = VirtualCardSetting::query()->find(1);
            if ($setting) {
                $this->baseUrl = (string) ($setting->base_url ?: $this->baseUrl);
                $this->secretKey = (string) ($setting->secret_key ?: $this->secretKey);
                $this->publicKey = (string) ($setting->public_key ?: $this->publicKey);
                $this->mode = strtolower((string) ($setting->mode ?: $this->mode));
                $dbEndpoints = (array) $setting->endpoints;
                $this->endpoints = array_merge($this->endpoints, $dbEndpoints);
            }
        }

        foreach ($this->defaultEndpoints as $key => $defaultValue) {
            $configuredValue = Arr::get($this->endpoints, $key);
            $configuredValue = is_string($configuredValue) ? trim($configuredValue) : $configuredValue;
            $defaultValue = (string) $defaultValue;

            if (!filled($configuredValue) || $configuredValue === $key) {
                $this->endpoints[$key] = $defaultValue;
                continue;
            }

            $value = (string) $configuredValue;
            $value = $this->normalizeLegacyEndpoint($key, $value);
            if (!str_starts_with($value, '/') && !str_contains($value, '://')) {
                $value = '/' . ltrim($value, '/');
            }

            $this->endpoints[$key] = $value;
        }
    }

    public function enabled(): bool
    {
        $dbEnabled = false;
        if (Schema::hasTable('virtual_card_settings')) {
            $dbEnabled = (bool) optional(VirtualCardSetting::query()->find(1))->enabled;
        }

        return ((bool) config('strowallet.enabled') || $dbEnabled) && $this->baseUrl !== '' && $this->secretKey !== '';
    }

    public function createCustomer(array $payload): array
    {
        return $this->post('create_customer', $payload);
    }

    public function createCard(array $payload): array
    {
        return $this->post('create_card', $payload);
    }

    public function getCustomer(array $payload): array
    {
        return $this->post('get_customer', $payload);
    }

    public function updateCustomer(array $payload): array
    {
        return $this->post('update_customer', $payload);
    }

    public function fundCard(array $payload): array
    {
        return $this->post('fund_card', $payload);
    }

    public function cardDetails(array $payload): array
    {
        return $this->post('card_details', $payload);
    }

    public function cardTransactions(array $payload): array
    {
        return $this->post('card_transactions', $payload);
    }

    public function fullCardHistory(array $payload): array
    {
        return $this->post('full_card_history', $payload);
    }

    public function freezeUnfreeze(array $payload): array
    {
        return $this->post('freeze_unfreeze', $payload);
    }

    public function withdrawFromCard(array $payload): array
    {
        return $this->post('withdraw_from_card', $payload);
    }

    public function cardWithdrawStatus(array $payload): array
    {
        return $this->post('card_withdraw_status', $payload);
    }

    public function upgradeCardLimit(array $payload): array
    {
        return $this->post('upgrade_card_limit', $payload);
    }

    public function mastercardDetails(array $payload): array
    {
        return $this->post('mastercard_details', $payload);
    }

    public function walletBalance(?string $currency = null): array
    {
        $currencyCode = strtoupper((string) ($currency ?: config('strowallet.currency', 'USD')));
        $url = 'https://strowallet.com/api/wallet/balance/' . urlencode($currencyCode) . '/';

        $response = Http::acceptJson()
            ->timeout($this->timeout)
            ->get($url, ['public_key' => $this->publicKey]);

        $json = $response->json();
        if (!$response->successful() || !is_array($json)) {
            throw new RuntimeException('Unable to fetch Strowallet balance.');
        }

        return $json;
    }

    private function post(string $endpointKey, array $payload): array
    {
        if (!$this->enabled()) {
            throw new RuntimeException('Strowallet is not configured.');
        }

        $uri = (string) Arr::get($this->endpoints, $endpointKey, '');
        if ($uri === '') {
            throw new RuntimeException('Strowallet endpoint missing: ' . $endpointKey);
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($uri, '/');
        if (!str_contains($url, '?') && !str_ends_with($url, '/')) {
            // Strowallet endpoints are slash-sensitive; avoid provider redirects that can turn POST into GET.
            $url .= '/';
        }

        $payload = $this->withAuthPayload($payload);

        $requestBuilder = Http::acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->withToken($this->secretKey)
            ->withHeaders([
                'x-api-key' => $this->secretKey,
            ]);

        $response = $requestBuilder->post($url, $payload);
        $requestUrl = $url;

        $json = $response->json();

        if (
            !$response->successful()
            && $response->status() === 405
            && $endpointKey === 'withdraw_from_card'
            && !str_contains($url, '/card_withdraw/')
        ) {
            $fallbackUrl = rtrim($this->baseUrl, '/') . '/card_withdraw/';
            $response = $requestBuilder->post($fallbackUrl, $payload);
            $requestUrl = $fallbackUrl;

            $json = $response->json();
        }

        if (
            !$response->successful()
            && $endpointKey === 'withdraw_from_card'
            && $response->status() === 400
            && is_array($json)
            && strtolower((string) ($json['message'] ?? '')) === 'validation error'
        ) {
            // Some Strowallet routes validate only form-encoded payload.
            $response = Http::acceptJson()
                ->asForm()
                ->timeout($this->timeout)
                ->withToken($this->secretKey)
                ->withHeaders([
                    'x-api-key' => $this->secretKey,
                ])
                ->post($requestUrl, $payload);
            $json = $response->json();
        }

        if (!$response->successful()) {
            $this->logFailure($endpointKey, $payload, $response);
            $message = is_array($json) ? ((string) ($json['message'] ?? $json['error'] ?? 'Provider request failed')) : 'Provider request failed';
            throw new RuntimeException($message);
        }

        return is_array($json) ? $json : ['raw' => $response->body()];
    }

    private function logFailure(string $endpointKey, array $payload, Response $response): void
    {
        Log::warning('strowallet_request_failed', [
            'endpoint' => $endpointKey,
            'status' => $response->status(),
            'payload' => $payload,
            'response' => $response->body(),
        ]);
    }

    private function withAuthPayload(array $payload): array
    {
        if (!isset($payload['public_key']) && $this->publicKey !== '') {
            $payload['public_key'] = $this->publicKey;
        }

        if (!isset($payload['secret_key']) && $this->secretKey !== '') {
            $payload['secret_key'] = $this->secretKey;
        }

        if (!isset($payload['developer_code']) && $this->developerCode !== '') {
            $payload['developer_code'] = $this->developerCode;
        }

        if (!isset($payload['mode']) && in_array($this->mode, ['sandbox', 'live'], true)) {
            $payload['mode'] = $this->mode;
        }

        return $payload;
    }

    private function normalizeLegacyEndpoint(string $key, string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return $normalized;
        }

        $aliasMap = $this->legacyEndpointAliases[$key] ?? null;
        if (!$aliasMap) {
            return $normalized;
        }

        return $aliasMap[$normalized] ?? $normalized;
    }
}
