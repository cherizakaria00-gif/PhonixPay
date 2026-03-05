<?php

namespace App\Services;

use App\Constants\Status;
use App\Http\Controllers\Gateway\PaymentController;
use App\Lib\CurlRequest;
use App\Models\Deposit;
use App\Models\Gateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BictorysDepositSyncService
{
    private const LOOKBACK_HOURS = 120;
    private const MAX_PENDING_PER_GATEWAY = 250;
    private const MAX_CHARGE_LOOKUPS = 60;

    public function syncPendingDeposits(): array
    {
        $result = [
            'checked' => 0,
            'synced_success' => 0,
            'synced_rejected' => 0,
            'gateways' => 0,
        ];

        $gateways = Gateway::query()
            ->whereIn('alias', ['BictorysCheckout', 'BictorysDirect'])
            ->where('status', Status::ENABLE)
            ->get();

        foreach ($gateways as $gateway) {
            $gatewayStats = $this->syncGatewayPendingDeposits($gateway);
            $result['checked'] += $gatewayStats['checked'];
            $result['synced_success'] += $gatewayStats['synced_success'];
            $result['synced_rejected'] += $gatewayStats['synced_rejected'];
            if ($gatewayStats['checked'] > 0) {
                $result['gateways']++;
            }
        }

        return $result;
    }

    private function syncGatewayPendingDeposits(Gateway $gateway): array
    {
        $stats = [
            'checked' => 0,
            'synced_success' => 0,
            'synced_rejected' => 0,
        ];

        $params = $this->decodeGatewayParams($gateway);
        $apiKey = trim((string) ($params['api_key'] ?? ''));
        if ($apiKey === '') {
            return $stats;
        }

        $baseUrl = rtrim((string) ($params['api_base_url'] ?? 'https://api.bictorys.com'), '/');
        if ($baseUrl === '') {
            $baseUrl = 'https://api.bictorys.com';
        }

        $pendingDeposits = Deposit::query()
            ->where('method_code', $gateway->code)
            ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])
            ->where('created_at', '>=', now()->subHours(self::LOOKBACK_HOURS))
            ->orderByDesc('id')
            ->limit(self::MAX_PENDING_PER_GATEWAY)
            ->get();

        if ($pendingDeposits->isEmpty()) {
            return $stats;
        }

        $stats['checked'] = $pendingDeposits->count();

        $depositIndex = $this->buildPendingDepositIndex($pendingDeposits);
        $decisions = [];

        $transactions = $this->fetchTransactionsSnapshot($baseUrl, $apiKey);
        foreach ($transactions as $transactionPayload) {
            $newStatus = $this->classifyStatus($this->extractStatus($transactionPayload));
            if ($newStatus === null) {
                continue;
            }

            $references = $this->extractReferences($transactionPayload);
            foreach ($references as $ref) {
                if (!isset($depositIndex[$ref])) {
                    continue;
                }

                foreach ($depositIndex[$ref] as $deposit) {
                    $this->rememberDecision($decisions, $deposit, $newStatus);
                }
            }
        }

        // Charge-level fallback for unresolved pending records.
        $remaining = $pendingDeposits
            ->filter(fn(Deposit $deposit) => !isset($decisions[$deposit->id]))
            ->take(self::MAX_CHARGE_LOOKUPS);

        foreach ($remaining as $deposit) {
            $chargeId = trim((string) ($deposit->btc_wallet ?? ''));
            if ($chargeId === '') {
                continue;
            }

            $opToken = $this->extractOpTokenFromDepositDetail($deposit);
            $chargeSnapshot = $this->fetchChargeSnapshot($baseUrl, $apiKey, $chargeId, $opToken);
            if (!$chargeSnapshot) {
                continue;
            }

            $chargeRef = $this->normalizeReference($chargeId);
            $trxRef = $this->normalizeReference($deposit->trx);
            $snapshotRefs = $this->extractReferences($chargeSnapshot);
            $mustMatchRefs = array_values(array_filter([$chargeRef, $trxRef], static fn($value) => $value !== null));
            if (!empty($mustMatchRefs) && empty(array_intersect($mustMatchRefs, $snapshotRefs))) {
                continue;
            }

            $newStatus = $this->classifyStatus($this->extractStatus($chargeSnapshot));
            if ($newStatus === null) {
                continue;
            }

            $this->rememberDecision($decisions, $deposit, $newStatus);
        }

        foreach ($decisions as $decision) {
            /** @var Deposit $deposit */
            $deposit = $decision['deposit'];
            if (!in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)) {
                continue;
            }

            if ($decision['status'] === Status::PAYMENT_SUCCESS) {
                PaymentController::userDataUpdate($deposit);
                $stats['synced_success']++;
                continue;
            }

            if ($decision['status'] === Status::PAYMENT_REJECT) {
                $deposit->status = Status::PAYMENT_REJECT;
                $deposit->save();
                if ($deposit->apiPayment) {
                    $deposit->apiPayment->status = Status::PAYMENT_REJECT;
                    $deposit->apiPayment->save();
                }
                $stats['synced_rejected']++;
            }
        }

        if ($stats['synced_success'] > 0 || $stats['synced_rejected'] > 0) {
            Log::info('Bictorys pending deposits synchronized', [
                'gateway' => $gateway->alias,
                'checked' => $stats['checked'],
                'synced_success' => $stats['synced_success'],
                'synced_rejected' => $stats['synced_rejected'],
            ]);
        }

        return $stats;
    }

    private function decodeGatewayParams(Gateway $gateway): array
    {
        $params = [];

        $sources = [
            $this->decodeJsonPayload($gateway->gateway_parameters),
        ];

        $gatewayCurrency = $gateway->singleCurrency()->orderByDesc('id')->first();
        if ($gatewayCurrency && !empty($gatewayCurrency->gateway_parameter)) {
            $sources[] = $this->decodeJsonPayload($gatewayCurrency->gateway_parameter);
        }

        foreach ($sources as $source) {
            foreach ($source as $key => $value) {
                $resolved = $this->extractScalarConfigValue($value);
                if ($resolved !== null && $resolved !== '') {
                    $params[$key] = $resolved;
                }
            }
        }

        return $params;
    }

    private function decodeJsonPayload($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_object($raw)) {
            return (array) $raw;
        }

        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function extractScalarConfigValue($value): ?string
    {
        if (is_array($value)) {
            if (array_key_exists('value', $value)) {
                return $this->extractScalarConfigValue($value['value']);
            }

            return null;
        }

        if (is_object($value)) {
            if (isset($value->value)) {
                return $this->extractScalarConfigValue($value->value);
            }

            return null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        return trim((string) $value);
    }

    private function buildPendingDepositIndex(Collection $pendingDeposits): array
    {
        $index = [];

        foreach ($pendingDeposits as $deposit) {
            $refs = [
                $this->normalizeReference($deposit->trx),
                $this->normalizeReference($deposit->btc_wallet),
            ];

            foreach ($refs as $ref) {
                if ($ref === null) {
                    continue;
                }

                $index[$ref] = $index[$ref] ?? [];
                $index[$ref][] = $deposit;
            }
        }

        return $index;
    }

    private function fetchTransactionsSnapshot(string $baseUrl, string $apiKey): array
    {
        $headers = [
            'Accept: application/json',
            "X-Api-Key: {$apiKey}",
        ];

        $endpoints = [
            $baseUrl . '/pay/v1/transactions?limit=250',
            $baseUrl . '/pay/v1/transactions',
        ];

        foreach ($endpoints as $url) {
            $raw = CurlRequest::curlContent($url, $headers);
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            $rows = $this->extractRows($decoded);
            if (!empty($rows)) {
                return $rows;
            }
        }

        return [];
    }

    private function fetchChargeSnapshot(string $baseUrl, string $apiKey, string $chargeId, ?string $opToken): ?array
    {
        $headers = [
            'Accept: application/json',
            "X-Api-Key: {$apiKey}",
        ];

        if ($opToken) {
            $headers[] = 'Op-Token: ' . $opToken;
        }

        $raw = CurlRequest::curlContent($baseUrl . '/pay/v1/charges/' . urlencode($chargeId), $headers);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function extractRows(array $payload): array
    {
        if ($this->isListArray($payload)) {
            return $payload;
        }

        $candidates = [
            data_get($payload, 'data.transactions'),
            data_get($payload, 'data.items'),
            data_get($payload, 'data.results'),
            data_get($payload, 'transactions'),
            data_get($payload, 'items'),
            data_get($payload, 'results'),
            data_get($payload, 'data'),
        ];

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            if ($this->isListArray($candidate)) {
                return $candidate;
            }

            if (!empty($candidate) && $this->isTransactionLikePayload($candidate)) {
                return [$candidate];
            }
        }

        if ($this->isTransactionLikePayload($payload)) {
            return [$payload];
        }

        return [];
    }

    private function isTransactionLikePayload(array $payload): bool
    {
        $keys = array_keys($payload);
        $targetKeys = [
            'status',
            'paymentStatus',
            'payment_status',
            'state',
            'result',
            'paymentReference',
            'payment_reference',
            'chargeId',
            'charge_id',
            'transactionId',
            'transaction_id',
        ];

        foreach ($targetKeys as $targetKey) {
            if (in_array($targetKey, $keys, true)) {
                return true;
            }
        }

        return false;
    }

    private function extractStatus(array $payload): string
    {
        $paths = [
            'status',
            'paymentStatus',
            'payment_status',
            'state',
            'paymentState',
            'payment_state',
            'result',
            'transactionStatus',
            'transaction_status',
            'data.status',
            'data.paymentStatus',
            'data.payment_status',
            'data.state',
            'data.paymentState',
            'data.payment_state',
            'data.result',
            'data.transactionStatus',
            'data.transaction_status',
        ];

        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (!is_scalar($value)) {
                continue;
            }

            $status = $this->normalizeStatus((string) $value);
            if ($status !== '') {
                return $status;
            }
        }

        return '';
    }

    private function extractReferences(array $payload): array
    {
        $paths = [
            'paymentReference',
            'payment_reference',
            'reference',
            'id',
            'chargeId',
            'charge_id',
            'paymentId',
            'payment_id',
            'transactionId',
            'transaction_id',
            'trx',
            'data.paymentReference',
            'data.payment_reference',
            'data.reference',
            'data.id',
            'data.chargeId',
            'data.charge_id',
            'data.paymentId',
            'data.payment_id',
            'data.transactionId',
            'data.transaction_id',
            'data.trx',
        ];

        $references = [];
        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (is_array($value)) {
                foreach ($value as $item) {
                    $normalized = $this->normalizeReference($item);
                    if ($normalized !== null) {
                        $references[] = $normalized;
                    }
                }
                continue;
            }

            $normalized = $this->normalizeReference($value);
            if ($normalized !== null) {
                $references[] = $normalized;
            }
        }

        return array_values(array_unique($references));
    }

    private function classifyStatus(string $status): ?int
    {
        if ($status === '') {
            return null;
        }

        if ($this->isFailureStatus($status)) {
            return Status::PAYMENT_REJECT;
        }

        if ($this->isSuccessStatus($status)) {
            return Status::PAYMENT_SUCCESS;
        }

        return null;
    }

    private function isSuccessStatus(string $status): bool
    {
        $status = $this->normalizeStatus($status);
        if ($status === '') {
            return false;
        }

        if ($this->isFailureStatus($status)) {
            return false;
        }

        $exact = [
            'success',
            'successful',
            'paid',
            'completed',
            'succeeded',
            'approved',
            'received',
            'captured',
            'settled',
            'done',
        ];

        if (in_array($status, $exact, true)) {
            return true;
        }

        return $this->statusContainsAny($status, [
            'success',
            'succeed',
            'paid',
            'complete',
            'approved',
            'receiv',
            'captur',
            'settl',
        ]);
    }

    private function isFailureStatus(string $status): bool
    {
        $status = $this->normalizeStatus($status);
        if ($status === '') {
            return false;
        }

        $exact = [
            'failed',
            'failure',
            'error',
            'canceled',
            'cancelled',
            'rejected',
            'expired',
            'refunded',
            'chargeback',
            'declined',
            'unpaid',
            'void',
        ];

        if (in_array($status, $exact, true)) {
            return true;
        }

        return $this->statusContainsAny($status, [
            'fail',
            'error',
            'cancel',
            'reject',
            'expire',
            'refund',
            'chargeback',
            'declin',
            'unpaid',
            'not_paid',
        ]);
    }

    private function rememberDecision(array &$decisions, Deposit $deposit, int $status): void
    {
        $existing = $decisions[$deposit->id]['status'] ?? null;
        if ($existing === Status::PAYMENT_SUCCESS) {
            return;
        }

        if ($existing === Status::PAYMENT_REJECT && $status !== Status::PAYMENT_SUCCESS) {
            return;
        }

        $decisions[$deposit->id] = [
            'deposit' => $deposit,
            'status' => $status,
        ];
    }

    private function extractOpTokenFromDepositDetail(Deposit $deposit): ?string
    {
        $detail = $deposit->detail;
        if (is_object($detail)) {
            $detail = (array) $detail;
        }

        if (!is_array($detail)) {
            return null;
        }

        $token = data_get($detail, 'bictorys.op_token')
            ?? data_get($detail, 'op_token')
            ?? data_get($detail, 'bictorys.opToken')
            ?? data_get($detail, 'opToken');

        if (!is_scalar($token)) {
            return null;
        }

        $token = trim((string) $token);
        return $token === '' ? null : $token;
    }

    private function normalizeReference($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));
        return $normalized === '' ? null : $normalized;
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === '') {
            return '';
        }

        $status = str_replace(['-', ' '], '_', $status);
        $status = preg_replace('/[^a-z0-9_]+/', '', $status) ?? '';
        $status = preg_replace('/_+/', '_', $status) ?? '';

        return trim($status, '_');
    }

    private function statusContainsAny(string $status, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($status, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isListArray(array $array): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}
