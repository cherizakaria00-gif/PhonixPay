<?php

namespace App\Services;

use App\Models\Deposit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BictorysRefundService
{
    public function refundDeposit(Deposit $deposit, ?string $reason = null): array
    {
        $gateway = $deposit->gateway;
        $alias = strtolower((string) ($gateway->alias ?? ''));
        $name = strtolower((string) ($gateway->name ?? ''));

        if (strpos($alias, 'bictorys') === false && strpos($name, 'bictorys') === false) {
            return ['success' => false, 'message' => 'Gateway is not Bictorys'];
        }

        $gatewayParams = json_decode($deposit->gatewayCurrency()->gateway_parameter ?? '{}');
        $apiKey = trim((string) ($gatewayParams->api_key ?? ''));
        $baseUrl = rtrim(trim((string) ($gatewayParams->api_base_url ?? 'https://api.test.bictorys.com')), '/');

        if ($apiKey === '') {
            return ['success' => false, 'message' => 'Bictorys API key is missing'];
        }

        $references = $this->extractReferences($deposit);
        if (empty($references)) {
            return ['success' => false, 'message' => 'Missing Bictorys reference for refund'];
        }

        $chargeId = $references[0];
        $amount = (float) ($deposit->gateway_amount ?? 0);
        if ($amount <= 0) {
            $amount = (float) (($deposit->final_amount ?? 0) + ($deposit->totalCharge ?? 0));
        }
        if ($amount <= 0) {
            $amount = (float) ($deposit->final_amount ?? 0);
        }

        $payload = [
            'chargeId' => $chargeId,
            'charge_id' => $chargeId,
            'reference' => (string) $deposit->trx,
            'paymentReference' => (string) $deposit->trx,
            'payment_reference' => (string) $deposit->trx,
            'amount' => round($amount, 2),
            'currency' => strtoupper((string) $deposit->method_currency),
            'reason' => $reason ?: 'Refunded by admin',
        ];

        $endpoints = $this->buildEndpoints($baseUrl, $chargeId);
        $lastFailure = null;

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(25)
                    ->acceptJson()
                    ->asJson()
                    ->withHeaders([
                        'X-Api-Key' => $apiKey,
                    ])
                    ->post($endpoint, $payload);

                $decoded = $response->json();
                if (!is_array($decoded)) {
                    $decoded = json_decode((string) $response->body(), true);
                    $decoded = is_array($decoded) ? $decoded : [];
                }

                Log::info('Bictorys refund API response', [
                    'deposit_id' => $deposit->id,
                    'trx' => $deposit->trx,
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'payload' => $payload,
                    'response' => $decoded,
                ]);

                if ($this->isSuccessfulRefundResponse($response->status(), $decoded)) {
                    return [
                        'success' => true,
                        'endpoint' => $endpoint,
                        'response' => $decoded,
                        'refund_reference' => $this->extractRefundReference($decoded),
                    ];
                }

                $lastFailure = [
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'message' => $this->extractErrorMessage($decoded) ?: ('HTTP ' . $response->status()),
                    'response' => $decoded,
                ];
            } catch (\Throwable $exception) {
                Log::warning('Bictorys refund API request failed', [
                    'deposit_id' => $deposit->id,
                    'trx' => $deposit->trx,
                    'endpoint' => $endpoint,
                    'message' => $exception->getMessage(),
                ]);

                $lastFailure = [
                    'endpoint' => $endpoint,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'success' => false,
            'message' => $lastFailure['message'] ?? 'Bictorys refund failed',
            'failure' => $lastFailure,
        ];
    }

    private function buildEndpoints(string $baseUrl, string $chargeId): array
    {
        $encodedChargeId = rawurlencode($chargeId);

        return [
            $baseUrl . '/pay/v1/refunds',
            $baseUrl . '/pay/v1/charges/' . $encodedChargeId . '/refund',
            $baseUrl . '/pay/v1/charges/refund',
            $baseUrl . '/pay/v1/charge/refund',
        ];
    }

    private function extractReferences(Deposit $deposit): array
    {
        $detail = is_array($deposit->detail) ? $deposit->detail : (array) $deposit->detail;

        $raw = [
            data_get($detail, 'bictorys.charge_id'),
            data_get($detail, 'bictorys.chargeId'),
            data_get($detail, 'bictorys.reference'),
            data_get($detail, 'bictorys.payment_reference'),
            data_get($detail, 'bictorys.paymentReference'),
            $deposit->btc_wallet,
            $deposit->trx,
        ];

        $references = [];
        foreach ($raw as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                $references[] = $trimmed;
            }
        }

        return array_values(array_unique($references));
    }

    private function isSuccessfulRefundResponse(int $httpStatus, array $payload): bool
    {
        if ($httpStatus < 200 || $httpStatus >= 300) {
            return false;
        }

        $boolSignals = [
            data_get($payload, 'success'),
            data_get($payload, 'refunded'),
            data_get($payload, 'data.success'),
            data_get($payload, 'data.refunded'),
            data_get($payload, 'result.success'),
        ];

        foreach ($boolSignals as $signal) {
            if ($signal === true || $signal === 1 || $signal === '1') {
                return true;
            }
        }

        $statusValues = [
            data_get($payload, 'status'),
            data_get($payload, 'state'),
            data_get($payload, 'result'),
            data_get($payload, 'data.status'),
            data_get($payload, 'data.state'),
            data_get($payload, 'data.result'),
            data_get($payload, 'message'),
            data_get($payload, 'data.message'),
        ];

        foreach ($statusValues as $status) {
            if (!is_scalar($status)) {
                continue;
            }

            $normalized = strtolower(trim((string) $status));
            if ($normalized === '') {
                continue;
            }

            if (str_contains($normalized, 'refund') && !str_contains($normalized, 'fail') && !str_contains($normalized, 'error')) {
                return true;
            }

            if (in_array($normalized, ['success', 'successful', 'succeeded', 'completed', 'approved', 'accepted', 'ok'], true)) {
                return true;
            }
        }

        return false;
    }

    private function extractRefundReference(array $payload): ?string
    {
        $paths = [
            'refundId',
            'refund_id',
            'id',
            'reference',
            'data.refundId',
            'data.refund_id',
            'data.id',
            'data.reference',
        ];

        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (!is_scalar($value)) {
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }

    private function extractErrorMessage(array $payload): ?string
    {
        $paths = [
            'message',
            'error',
            'errors.0.message',
            'data.message',
            'data.error',
            'data.errors.0.message',
        ];

        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (!is_scalar($value)) {
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }
}
