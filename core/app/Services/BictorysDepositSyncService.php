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
    private const DEFAULT_LOOKBACK_HOURS = 120;
    private const DEFAULT_MAX_PENDING_PER_GATEWAY = 250;
    private const DEFAULT_MAX_CHARGE_LOOKUPS = 60;

    public function syncPendingDeposits(array $options = []): array
    {
        $result = [
            'checked' => 0,
            'synced_success' => 0,
            'synced_rejected' => 0,
            'gateways' => 0,
            'hydrated_tokens' => 0,
            'manual_success' => 0,
            'manual_rejected' => 0,
        ];

        $lookbackHours = $this->readIntOption($options, 'lookback_hours', self::DEFAULT_LOOKBACK_HOURS);
        $maxPending = $this->readIntOption($options, 'max_pending_per_gateway', self::DEFAULT_MAX_PENDING_PER_GATEWAY);
        $maxChargeLookups = $this->readIntOption($options, 'max_charge_lookups', self::DEFAULT_MAX_CHARGE_LOOKUPS);
        $replayLogs = (bool) ($options['replay_logs'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);

        if ($replayLogs) {
            $hydration = $this->hydratePendingTokensFromLogs($dryRun);
            $result['hydrated_tokens'] += $hydration['hydrated_tokens'];
            $result['manual_success'] += $hydration['manual_success'];
            $result['manual_rejected'] += $hydration['manual_rejected'];
        }

        $gateways = Gateway::query()
            ->whereIn('alias', ['BictorysCheckout', 'BictorysDirect'])
            ->where('status', Status::ENABLE)
            ->get();

        foreach ($gateways as $gateway) {
            $gatewayStats = $this->syncGatewayPendingDeposits(
                $gateway,
                $lookbackHours,
                $maxPending,
                $maxChargeLookups,
                $dryRun
            );
            $result['checked'] += $gatewayStats['checked'];
            $result['synced_success'] += $gatewayStats['synced_success'];
            $result['synced_rejected'] += $gatewayStats['synced_rejected'];
            if ($gatewayStats['checked'] > 0) {
                $result['gateways']++;
            }
        }

        $manualRefsResult = $this->reconcileManualReferences(
            $this->normalizeReferenceList($options['success_refs'] ?? []),
            $this->normalizeReferenceList($options['reject_refs'] ?? []),
            $dryRun
        );
        $result['manual_success'] += $manualRefsResult['manual_success'];
        $result['manual_rejected'] += $manualRefsResult['manual_rejected'];

        return $result;
    }

    private function syncGatewayPendingDeposits(
        Gateway $gateway,
        int $lookbackHours,
        int $maxPendingPerGateway,
        int $maxChargeLookups,
        bool $dryRun
    ): array
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

        $pendingDepositsQuery = Deposit::query()
            ->where('method_code', $gateway->code)
            ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING]);

        if ($lookbackHours > 0) {
            $pendingDepositsQuery->where('created_at', '>=', now()->subHours($lookbackHours));
        }

        $pendingDeposits = $pendingDepositsQuery
            ->orderByDesc('id')
            ->limit(max(1, $maxPendingPerGateway))
            ->get();

        if ($pendingDeposits->isEmpty()) {
            return $stats;
        }

        $stats['checked'] = $pendingDeposits->count();

        $depositIndex = $this->buildPendingDepositIndex($pendingDeposits);
        $decisions = [];

        $transactions = $this->fetchTransactionsSnapshot($baseUrl, $apiKey);
        foreach ($transactions as $transactionPayload) {
            $newStatus = $this->classifyPayloadStatus($transactionPayload);
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
            ->take(max(1, $maxChargeLookups));

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

            $newStatus = $this->classifyPayloadStatus($chargeSnapshot);
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
                $this->applyStatusDecision($deposit, Status::PAYMENT_SUCCESS, $dryRun);
                $stats['synced_success']++;
                continue;
            }

            if ($decision['status'] === Status::PAYMENT_REJECT) {
                $this->applyStatusDecision($deposit, Status::PAYMENT_REJECT, $dryRun);
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

    private function readIntOption(array $options, string $key, int $default): int
    {
        $value = $options[$key] ?? $default;
        $value = (int) $value;
        return $value > 0 ? $value : $default;
    }

    private function normalizeReferenceList($value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (!is_array($value)) {
            return [];
        }

        $refs = [];
        foreach ($value as $item) {
            $normalized = $this->normalizeReference($item);
            if ($normalized !== null) {
                $refs[] = $normalized;
            }
        }

        return array_values(array_unique($refs));
    }

    private function reconcileManualReferences(array $successRefs, array $rejectRefs, bool $dryRun): array
    {
        $result = [
            'manual_success' => 0,
            'manual_rejected' => 0,
        ];

        $byStatus = [
            Status::PAYMENT_SUCCESS => $successRefs,
            Status::PAYMENT_REJECT => $rejectRefs,
        ];

        foreach ($byStatus as $targetStatus => $refs) {
            if (empty($refs)) {
                continue;
            }

            $deposits = Deposit::query()
                ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])
                ->where(function ($query) use ($refs) {
                    $query->whereIn('trx', $refs)->orWhereIn('btc_wallet', $refs);
                })
                ->get();

            foreach ($deposits as $deposit) {
                $this->applyStatusDecision($deposit, $targetStatus, $dryRun);
                if ($targetStatus === Status::PAYMENT_SUCCESS) {
                    $result['manual_success']++;
                } elseif ($targetStatus === Status::PAYMENT_REJECT) {
                    $result['manual_rejected']++;
                }
            }
        }

        return $result;
    }

    private function applyStatusDecision(Deposit $deposit, int $status, bool $dryRun): void
    {
        if (!in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)) {
            return;
        }

        if ($status === Status::PAYMENT_SUCCESS) {
            if (!$dryRun) {
                PaymentController::userDataUpdate($deposit);
            }

            return;
        }

        if ($status !== Status::PAYMENT_REJECT) {
            return;
        }

        if (!$dryRun) {
            $deposit->status = Status::PAYMENT_REJECT;
            $deposit->save();

            if ($deposit->apiPayment) {
                $deposit->apiPayment->status = Status::PAYMENT_REJECT;
                $deposit->apiPayment->save();
            }
        }
    }

    private function hydratePendingTokensFromLogs(bool $dryRun): array
    {
        $result = [
            'hydrated_tokens' => 0,
            'manual_success' => 0,
            'manual_rejected' => 0,
        ];

        $logPath = storage_path('logs/laravel.log');
        if (!is_file($logPath) || !is_readable($logPath)) {
            return $result;
        }

        $tokenMapByDepositId = [];
        $successDepositIds = [];
        $rejectDepositIds = [];

        $handle = fopen($logPath, 'r');
        if (!$handle) {
            return $result;
        }

        while (($line = fgets($handle)) !== false) {
            if (str_contains($line, 'Bictorys') && (str_contains($line, 'direct response') || str_contains($line, 'checkout response'))) {
                if (preg_match('/"deposit_id":(\d+)/', $line, $idMatch)) {
                    $depositId = (int) $idMatch[1];
                    $chargeId = null;
                    $opToken = null;

                    if (preg_match('/"chargeId":"([^"]+)"/', $line, $chargeMatch)) {
                        $chargeId = trim((string) $chargeMatch[1]);
                    }

                    if (preg_match('/"opToken":"([^"]+)"/', $line, $tokenMatch)) {
                        $opToken = trim((string) $tokenMatch[1]);
                    }

                    if ($chargeId !== null || $opToken !== null) {
                        $tokenMapByDepositId[$depositId] = [
                            'charge_id' => $chargeId,
                            'op_token' => $opToken,
                        ];
                    }
                }
            }

            if (str_contains($line, 'IPN marked deposit successful') && preg_match('/"deposit_id":(\d+)/', $line, $idMatch)) {
                $successDepositIds[(int) $idMatch[1]] = true;
            }

            if (str_contains($line, 'IPN marked deposit rejected') && preg_match('/"deposit_id":(\d+)/', $line, $idMatch)) {
                $rejectDepositIds[(int) $idMatch[1]] = true;
            }
        }

        fclose($handle);

        if (!empty($tokenMapByDepositId)) {
            $deposits = Deposit::query()
                ->whereIn('id', array_keys($tokenMapByDepositId))
                ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])
                ->get();

            foreach ($deposits as $deposit) {
                $payload = $tokenMapByDepositId[$deposit->id] ?? null;
                if (!$payload) {
                    continue;
                }

                $detail = $this->normalizeDetailPayload($deposit->detail);
                $changed = false;

                $existingToken = data_get($detail, 'bictorys.op_token');
                $existingCharge = data_get($detail, 'bictorys.charge_id');

                if (!$existingToken && !empty($payload['op_token'])) {
                    data_set($detail, 'bictorys.op_token', $payload['op_token']);
                    $changed = true;
                }

                if (!$existingCharge && !empty($payload['charge_id'])) {
                    data_set($detail, 'bictorys.charge_id', $payload['charge_id']);
                    $changed = true;
                }

                if (empty($deposit->btc_wallet) && !empty($payload['charge_id'])) {
                    $deposit->btc_wallet = $payload['charge_id'];
                    $changed = true;
                }

                if ($changed) {
                    if (!$dryRun) {
                        $deposit->detail = $detail;
                        $deposit->save();
                    }
                    $result['hydrated_tokens']++;
                }
            }
        }

        $successIds = array_keys($successDepositIds);
        if (!empty($successIds)) {
            $deposits = Deposit::query()
                ->whereIn('id', $successIds)
                ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])
                ->get();

            foreach ($deposits as $deposit) {
                $this->applyStatusDecision($deposit, Status::PAYMENT_SUCCESS, $dryRun);
                $result['manual_success']++;
            }
        }

        $rejectIds = array_keys($rejectDepositIds);
        if (!empty($rejectIds)) {
            $deposits = Deposit::query()
                ->whereIn('id', $rejectIds)
                ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])
                ->get();

            foreach ($deposits as $deposit) {
                $this->applyStatusDecision($deposit, Status::PAYMENT_REJECT, $dryRun);
                $result['manual_rejected']++;
            }
        }

        return $result;
    }

    private function normalizeDetailPayload($detail): array
    {
        if (is_array($detail)) {
            return $detail;
        }

        if (is_object($detail)) {
            return (array) $detail;
        }

        $decoded = json_decode((string) $detail, true);
        return is_array($decoded) ? $decoded : [];
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

    private function extractStatusCandidates(array $payload): array
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
            'title',
            'details',
            'error',
            'message',
            'reason',
            'description',
            'data.title',
            'data.details',
            'data.error',
            'data.message',
            'data.reason',
            'data.description',
        ];

        $candidates = [];
        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (!is_scalar($value)) {
                continue;
            }

            $status = $this->normalizeStatus((string) $value);
            if ($status !== '') {
                $candidates[] = $status;
            }
        }

        return array_values(array_unique($candidates));
    }

    private function classifyPayloadStatus(array $payload): ?int
    {
        $statuses = $this->extractStatusCandidates($payload);
        foreach ($statuses as $status) {
            if ($this->isFailureStatus($status)) {
                return Status::PAYMENT_REJECT;
            }
        }

        foreach ($statuses as $status) {
            if ($this->isSuccessStatus($status)) {
                return Status::PAYMENT_SUCCESS;
            }
        }

        return null;
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
