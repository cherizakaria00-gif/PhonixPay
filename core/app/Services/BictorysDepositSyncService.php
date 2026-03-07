<?php

namespace App\Services;

use App\Constants\Status;
use App\Http\Controllers\Gateway\PaymentController;
use App\Lib\CurlRequest;
use App\Models\Deposit;
use App\Models\Gateway;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BictorysDepositSyncService
{
    private const DEFAULT_LOOKBACK_HOURS = 120;
    private const DEFAULT_MAX_PENDING_PER_GATEWAY = 250;
    private const DEFAULT_MAX_CHARGE_LOOKUPS = 0;
    private const DEFAULT_CHARGE_WEBHOOK_GRACE_SECONDS = 180;
    private const DEFAULT_CHARGE_MAX_ATTEMPTS = 3;
    private const DEFAULT_CHARGE_BACKOFF_SECONDS = 1;
    private const DEFAULT_CHARGE_MAX_BACKOFF_SECONDS = 5;
    private const DEFAULT_CHARGE_THROTTLE_SECONDS = 30;
    private const DEFAULT_CHARGE_SNAPSHOT_CACHE_SECONDS = 30;
    private const DEFAULT_CHARGE_HTTP_RETRIES = 3;
    private const SYNC_LOCK_KEY = 'flujipay_bictorys_pending_sync_lock';
    private const SYNC_LOCK_TTL_SECONDS = 120;

    private static bool $syncExecutedInRequest = false;

    public function syncPendingDeposits(array $options = []): array
    {
        $normalizedSuccessRefs = $this->normalizeReferenceList($options['success_refs'] ?? []);
        $normalizedRejectRefs = $this->normalizeReferenceList($options['reject_refs'] ?? []);
        $forceSync = (bool) ($options['force'] ?? false) || !empty($normalizedSuccessRefs) || !empty($normalizedRejectRefs);

        $result = [
            'checked' => 0,
            'synced_success' => 0,
            'synced_rejected' => 0,
            'gateways' => 0,
            'hydrated_tokens' => 0,
            'manual_success' => 0,
            'manual_rejected' => 0,
            'skipped' => false,
            'skip_reason' => null,
        ];

        if (self::$syncExecutedInRequest && !$forceSync) {
            $result['skipped'] = true;
            $result['skip_reason'] = 'already_executed_in_request';
            return $result;
        }

        if (!$forceSync && !Cache::add(self::SYNC_LOCK_KEY, now()->timestamp, self::SYNC_LOCK_TTL_SECONDS)) {
            $result['skipped'] = true;
            $result['skip_reason'] = 'global_lock_active';
            return $result;
        }

        self::$syncExecutedInRequest = true;

        $lookbackHours = $this->readIntOption($options, 'lookback_hours', self::DEFAULT_LOOKBACK_HOURS);
        $maxPending = $this->readIntOption($options, 'max_pending_per_gateway', self::DEFAULT_MAX_PENDING_PER_GATEWAY);
        $maxChargeLookups = $this->readIntOption($options, 'max_charge_lookups', self::DEFAULT_MAX_CHARGE_LOOKUPS);
        $allowChargeLookup = (bool) ($options['allow_charge_lookup'] ?? false);
        if (!$allowChargeLookup) {
            $maxChargeLookups = 0;
        }
        $lookupPolicy = [
            'webhook_grace_seconds' => $this->readIntOption($options, 'webhook_grace_seconds', self::DEFAULT_CHARGE_WEBHOOK_GRACE_SECONDS),
            'max_attempts' => $this->readIntOption($options, 'max_charge_attempts', self::DEFAULT_CHARGE_MAX_ATTEMPTS),
            'base_backoff_seconds' => $this->readIntOption($options, 'charge_backoff_seconds', self::DEFAULT_CHARGE_BACKOFF_SECONDS),
            'max_backoff_seconds' => $this->readIntOption($options, 'charge_max_backoff_seconds', self::DEFAULT_CHARGE_MAX_BACKOFF_SECONDS),
            'throttle_seconds' => $this->readIntOption($options, 'charge_throttle_seconds', self::DEFAULT_CHARGE_THROTTLE_SECONDS),
            'snapshot_cache_seconds' => $this->readIntOption($options, 'charge_snapshot_cache_seconds', self::DEFAULT_CHARGE_SNAPSHOT_CACHE_SECONDS),
            'http_retries' => $this->readIntOption($options, 'charge_http_retries', self::DEFAULT_CHARGE_HTTP_RETRIES),
        ];
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
                $allowChargeLookup,
                $dryRun,
                $lookupPolicy
            );
            $result['checked'] += $gatewayStats['checked'];
            $result['synced_success'] += $gatewayStats['synced_success'];
            $result['synced_rejected'] += $gatewayStats['synced_rejected'];
            if ($gatewayStats['checked'] > 0) {
                $result['gateways']++;
            }
        }

        $manualRefsResult = $this->reconcileManualReferences(
            $normalizedSuccessRefs,
            $normalizedRejectRefs,
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
        bool $allowChargeLookup,
        bool $dryRun,
        array $lookupPolicy
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

        if ($allowChargeLookup && $maxChargeLookups > 0) {
            // Manual fallback only: disabled in automated cron flow to avoid PSP polling.
            $remaining = $pendingDeposits
                ->filter(fn(Deposit $deposit) => !isset($decisions[$deposit->id]))
                ->take($maxChargeLookups);

            foreach ($remaining as $deposit) {
                $chargeId = trim((string) ($deposit->btc_wallet ?? ''));
                if ($chargeId === '') {
                    continue;
                }

                if (!$this->canLookupChargeStatus($deposit, $chargeId, $lookupPolicy)) {
                    continue;
                }

                $this->recordChargeLookupAttempt($deposit, $lookupPolicy, $dryRun);
                $opToken = $this->extractOpTokenFromDepositDetail($deposit);
                $chargeSnapshot = $this->fetchChargeSnapshot($baseUrl, $apiKey, $chargeId, $opToken, $lookupPolicy);
                if (!$chargeSnapshot) {
                    $this->scheduleNextChargeLookup($deposit, $lookupPolicy, $dryRun);
                    continue;
                }

                $chargeRef = $this->normalizeReference($chargeId);
                $trxRef = $this->normalizeReference($deposit->trx);
                $snapshotRefs = $this->extractReferences($chargeSnapshot);
                $mustMatchRefs = array_values(array_filter([$chargeRef, $trxRef], static fn($value) => $value !== null));
                if (!empty($mustMatchRefs) && empty(array_intersect($mustMatchRefs, $snapshotRefs))) {
                    $this->scheduleNextChargeLookup($deposit, $lookupPolicy, $dryRun);
                    continue;
                }

                $newStatus = $this->classifyPayloadStatus($chargeSnapshot);
                if ($newStatus === null) {
                    $this->scheduleNextChargeLookup($deposit, $lookupPolicy, $dryRun);
                    continue;
                }

                $this->clearChargeLookupState($deposit, $dryRun);
                $this->rememberDecision($decisions, $deposit, $newStatus);
            }
        }

        foreach ($decisions as $decision) {
            /** @var Deposit $deposit */
            $deposit = $decision['deposit'];
            if (!in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)) {
                continue;
            }

            if ($decision['status'] === Status::PAYMENT_SUCCESS) {
                $this->clearChargeLookupState($deposit, $dryRun);
                $this->applyStatusDecision($deposit, Status::PAYMENT_SUCCESS, $dryRun);
                $stats['synced_success']++;
                continue;
            }

            if ($decision['status'] === Status::PAYMENT_REJECT) {
                $this->clearChargeLookupState($deposit, $dryRun);
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
                    $query->whereIn('trx', $refs)
                        ->orWhereIn('btc_wallet', $refs)
                        ->orWhereIn(DB::raw('LOWER(trx)'), $refs)
                        ->orWhereIn(DB::raw('LOWER(btc_wallet)'), $refs);
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

    private function fetchChargeSnapshot(string $baseUrl, string $apiKey, string $chargeId, ?string $opToken, array $lookupPolicy = []): ?array
    {
        $cacheSeconds = max(1, (int) ($lookupPolicy['snapshot_cache_seconds'] ?? self::DEFAULT_CHARGE_SNAPSHOT_CACHE_SECONDS));
        $cacheKey = $this->chargeSnapshotCacheKey($baseUrl, $chargeId, $opToken);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $headers = [
            'Accept: application/json',
            "X-Api-Key: {$apiKey}",
        ];

        if ($opToken) {
            $headers[] = 'Op-Token: ' . $opToken;
        }

        $maxRetries = max(1, min(3, (int) ($lookupPolicy['http_retries'] ?? self::DEFAULT_CHARGE_HTTP_RETRIES)));
        $retryBackoff = [1, 2, 5];
        $attempt = 0;
        while ($attempt < $maxRetries) {
            $raw = CurlRequest::curlContent($baseUrl . '/pay/v1/charges/' . urlencode($chargeId), $headers);
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    Cache::put($cacheKey, $decoded, $cacheSeconds);
                    return $decoded;
                }
            }

            if ($attempt + 1 < $maxRetries) {
                $sleepSeconds = $retryBackoff[$attempt] ?? 5;
                sleep($sleepSeconds);
            }

            $attempt++;
        }

        return null;
    }

    private function canLookupChargeStatus(Deposit $deposit, string $chargeId, array $lookupPolicy): bool
    {
        $now = now();
        $webhookGraceSeconds = max(10, (int) ($lookupPolicy['webhook_grace_seconds'] ?? self::DEFAULT_CHARGE_WEBHOOK_GRACE_SECONDS));
        if ($deposit->created_at && $deposit->created_at->gt($now->copy()->subSeconds($webhookGraceSeconds))) {
            return false;
        }

        $detail = $this->normalizeDetailPayload($deposit->detail);
        $webhookReceivedAt = $this->parseDate(data_get($detail, 'bictorys.webhook_received_at'));
        if ($webhookReceivedAt && $webhookReceivedAt->gt($now->copy()->subSeconds($webhookGraceSeconds))) {
            return false;
        }

        $attempts = (int) data_get($detail, 'bictorys.status_sync.attempts', 0);
        $maxAttempts = max(1, (int) ($lookupPolicy['max_attempts'] ?? self::DEFAULT_CHARGE_MAX_ATTEMPTS));
        if ($attempts >= $maxAttempts) {
            return false;
        }

        $nextCheckAt = $this->parseDate(data_get($detail, 'bictorys.status_sync.next_check_at'));
        if ($nextCheckAt && $nextCheckAt->isFuture()) {
            return false;
        }

        $throttleSeconds = max(1, (int) ($lookupPolicy['throttle_seconds'] ?? self::DEFAULT_CHARGE_THROTTLE_SECONDS));
        return Cache::add($this->chargeLookupThrottleKey($chargeId), $now->timestamp, $throttleSeconds);
    }

    private function recordChargeLookupAttempt(Deposit $deposit, array $lookupPolicy, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $detail = $this->normalizeDetailPayload($deposit->detail);
        $syncState = data_get($detail, 'bictorys.status_sync', []);
        if (!is_array($syncState)) {
            $syncState = [];
        }

        $syncState['attempts'] = max(0, (int) ($syncState['attempts'] ?? 0)) + 1;
        $syncState['last_checked_at'] = now()->toIso8601String();
        data_set($detail, 'bictorys.status_sync', $syncState);

        $deposit->detail = $detail;
        $deposit->save();
    }

    private function scheduleNextChargeLookup(Deposit $deposit, array $lookupPolicy, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $detail = $this->normalizeDetailPayload($deposit->detail);
        $syncState = data_get($detail, 'bictorys.status_sync', []);
        if (!is_array($syncState)) {
            $syncState = [];
        }

        $attempts = max(1, (int) ($syncState['attempts'] ?? 1));
        $baseDelay = max(1, (int) ($lookupPolicy['base_backoff_seconds'] ?? self::DEFAULT_CHARGE_BACKOFF_SECONDS));
        $maxDelay = max($baseDelay, (int) ($lookupPolicy['max_backoff_seconds'] ?? self::DEFAULT_CHARGE_MAX_BACKOFF_SECONDS));
        $preset = [$baseDelay, $baseDelay * 2, $maxDelay];
        $delaySeconds = (int) ($preset[min(count($preset) - 1, $attempts - 1)] ?? $maxDelay);

        $syncState['next_check_at'] = now()->addSeconds($delaySeconds)->toIso8601String();
        data_set($detail, 'bictorys.status_sync', $syncState);

        $deposit->detail = $detail;
        $deposit->save();
    }

    private function clearChargeLookupState(Deposit $deposit, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $detail = $this->normalizeDetailPayload($deposit->detail);
        if (data_get($detail, 'bictorys.status_sync') === null) {
            return;
        }

        data_forget($detail, 'bictorys.status_sync');
        $deposit->detail = $detail;
        $deposit->save();
    }

    private function parseDate($value): ?Carbon
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function chargeLookupThrottleKey(string $chargeId): string
    {
        return 'flujipay_bictorys_charge_lookup_throttle_' . sha1(strtolower(trim($chargeId)));
    }

    private function chargeSnapshotCacheKey(string $baseUrl, string $chargeId, ?string $opToken): string
    {
        return 'flujipay_bictorys_charge_snapshot_' . sha1($baseUrl . '|' . strtolower(trim($chargeId)) . '|' . (string) $opToken);
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
