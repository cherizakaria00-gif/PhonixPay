<?php

namespace App\Services;

use App\Constants\Status;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\Deposit;
use App\Models\Gateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BictorysDepositSyncService
{
    private const DEFAULT_LOOKBACK_HOURS = 720;
    private const DEFAULT_MAX_PENDING_PER_GATEWAY = 500;
    private const DEFAULT_EXPIRE_AFTER_MINUTES = 180;
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
            'expired_local' => 0,
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

        $dryRun = (bool) ($options['dry_run'] ?? false);
        $replayLogs = (bool) ($options['replay_logs'] ?? false);
        $expirePending = (bool) ($options['expire_pending'] ?? true);
        $lookbackHours = $this->readIntOption($options, 'lookback_hours', self::DEFAULT_LOOKBACK_HOURS);
        $maxPending = $this->readIntOption($options, 'max_pending_per_gateway', self::DEFAULT_MAX_PENDING_PER_GATEWAY);
        $expireAfterMinutes = $this->readIntOption(
            $options,
            'expire_after_minutes',
            (int) config('services.bictorys.pending_expire_minutes', self::DEFAULT_EXPIRE_AFTER_MINUTES)
        );

        if ($replayLogs) {
            $hydration = $this->hydratePendingDecisionsFromLogs($dryRun);
            $result['manual_success'] += $hydration['manual_success'];
            $result['manual_rejected'] += $hydration['manual_rejected'];
        }

        if ($expirePending) {
            $expiration = $this->expireStalePendingDeposits($lookbackHours, $maxPending, $expireAfterMinutes, $dryRun);
            $result['checked'] += $expiration['checked'];
            $result['expired_local'] += $expiration['expired_local'];
            $result['gateways'] = max($result['gateways'], $expiration['gateways']);
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

    private function expireStalePendingDeposits(int $lookbackHours, int $maxPendingPerGateway, int $expireAfterMinutes, bool $dryRun): array
    {
        $stats = [
            'checked' => 0,
            'expired_local' => 0,
            'gateways' => 0,
        ];

        $gatewayCodes = Gateway::query()
            ->whereIn('alias', ['BictorysCheckout', 'BictorysDirect'])
            ->pluck('code')
            ->filter()
            ->values();

        if ($gatewayCodes->isEmpty()) {
            return $stats;
        }

        $cutoff = now()->subMinutes(max(1, $expireAfterMinutes));
        $query = Deposit::query()
            ->whereIn('method_code', $gatewayCodes->all())
            ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id');

        if ($lookbackHours > 0) {
            $query->where('created_at', '>=', now()->subHours($lookbackHours));
        }

        $deposits = $query->limit(max(1, $maxPendingPerGateway * max(1, $gatewayCodes->count())))->get();
        $stats['checked'] = $deposits->count();
        $stats['gateways'] = $deposits->pluck('method_code')->unique()->count();

        foreach ($deposits as $deposit) {
            $this->applyStatusDecision($deposit, Status::PAYMENT_CANCEL, $dryRun);
            $stats['expired_local']++;
        }

        return $stats;
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

            $eligibleStatuses = $targetStatus === Status::PAYMENT_SUCCESS
                ? [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING, Status::PAYMENT_REJECT]
                : [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING];

            $deposits = Deposit::query()
                ->whereIn('status', $eligibleStatuses)
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
                } else {
                    $result['manual_rejected']++;
                }
            }
        }

        return $result;
    }

    private function applyStatusDecision(Deposit $deposit, int $status, bool $dryRun): void
    {
        if ($status === Status::PAYMENT_SUCCESS) {
            if (!$dryRun) {
                PaymentController::userDataUpdate($deposit);
            }
            return;
        }

        if ($status === Status::PAYMENT_REJECT) {
            if (!in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)) {
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
            return;
        }

        if ($status === Status::PAYMENT_CANCEL) {
            if (!in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)) {
                return;
            }

            if (!$dryRun) {
                $deposit->status = Status::PAYMENT_CANCEL;
                $deposit->save();

                if ($deposit->apiPayment) {
                    $deposit->apiPayment->status = Status::PAYMENT_CANCEL;
                    $deposit->apiPayment->save();
                }
            }
        }
    }

    private function hydratePendingDecisionsFromLogs(bool $dryRun): array
    {
        $result = [
            'manual_success' => 0,
            'manual_rejected' => 0,
        ];

        $logPath = storage_path('logs/laravel.log');
        if (!is_file($logPath) || !is_readable($logPath)) {
            return $result;
        }

        $successIds = [];
        $rejectIds = [];

        $handle = fopen($logPath, 'r');
        if (!$handle) {
            return $result;
        }

        while (($line = fgets($handle)) !== false) {
            if (!preg_match('/"deposit_id":(\d+)/', $line, $idMatch)) {
                continue;
            }

            $depositId = (int) $idMatch[1];
            if ($depositId <= 0) {
                continue;
            }

            if (str_contains($line, 'marked deposit successful') || str_contains($line, 'processed_paid')) {
                $successIds[$depositId] = true;
                unset($rejectIds[$depositId]);
                continue;
            }

            if (str_contains($line, 'marked deposit rejected') || str_contains($line, 'processed_rejected')) {
                if (!isset($successIds[$depositId])) {
                    $rejectIds[$depositId] = true;
                }
            }
        }

        fclose($handle);

        if (!empty($successIds)) {
            $deposits = Deposit::query()
                ->whereIn('id', array_keys($successIds))
                ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING, Status::PAYMENT_REJECT])
                ->get();

            foreach ($deposits as $deposit) {
                $this->applyStatusDecision($deposit, Status::PAYMENT_SUCCESS, $dryRun);
                $result['manual_success']++;
            }
        }

        if (!empty($rejectIds)) {
            $deposits = Deposit::query()
                ->whereIn('id', array_keys($rejectIds))
                ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])
                ->get();

            foreach ($deposits as $deposit) {
                $this->applyStatusDecision($deposit, Status::PAYMENT_REJECT, $dryRun);
                $result['manual_rejected']++;
            }
        }

        return $result;
    }

    private function readIntOption(array $options, string $key, int $default): int
    {
        $value = (int) ($options[$key] ?? $default);
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
            if (!is_scalar($item)) {
                continue;
            }

            $normalized = strtolower(trim((string) $item));
            if ($normalized !== '') {
                $refs[] = $normalized;
            }
        }

        return array_values(array_unique($refs));
    }
}
