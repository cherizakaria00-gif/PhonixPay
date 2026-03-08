<?php

namespace App\Console\Commands;

use App\Services\BictorysDepositSyncService;
use Illuminate\Console\Command;

class RepairBictorysDepositsCommand extends Command
{
    protected $signature = 'bictorys:repair
        {--lookback-hours=120 : Lookback window for pending deposits. Use a large value for historical repair}
        {--max-pending=250 : Maximum pending deposits checked per gateway}
        {--expire-after-minutes=180 : Expire local pending payments older than this delay}
        {--replay-logs : Replay success/reject decisions found in laravel.log}
        {--success-refs= : Comma separated trx/charge_id references to mark as successful}
        {--reject-refs= : Comma separated trx/charge_id references to mark as rejected}
        {--dry-run : Preview only, no database updates}';

    protected $description = 'Local-only Bictorys reconciliation (webhook-first): manual refs, log replay, and pending expiration.';

    public function handle(BictorysDepositSyncService $service): int
    {
        $result = $service->syncPendingDeposits([
            'lookback_hours' => (int) $this->option('lookback-hours'),
            'max_pending_per_gateway' => (int) $this->option('max-pending'),
            'expire_after_minutes' => (int) $this->option('expire-after-minutes'),
            'expire_pending' => true,
            'replay_logs' => (bool) $this->option('replay-logs'),
            'success_refs' => (string) ($this->option('success-refs') ?? ''),
            'reject_refs' => (string) ($this->option('reject-refs') ?? ''),
            'dry_run' => (bool) $this->option('dry-run'),
        ]);

        $this->info('Bictorys repair completed.');
        $this->line('Checked: ' . (int) ($result['checked'] ?? 0));
        $this->line('Synced success: ' . (int) ($result['synced_success'] ?? 0));
        $this->line('Synced rejected: ' . (int) ($result['synced_rejected'] ?? 0));
        $this->line('Hydrated tokens from logs: 0');
        $this->line('Manual success refs applied: ' . (int) ($result['manual_success'] ?? 0));
        $this->line('Manual reject refs applied: ' . (int) ($result['manual_rejected'] ?? 0));
        $this->line('Expired locally: ' . (int) ($result['expired_local'] ?? 0));
        $this->line('Gateways touched: ' . (int) ($result['gateways'] ?? 0));

        if ($this->option('dry-run')) {
            $this->warn('Dry-run mode: no database changes were committed.');
        }

        return self::SUCCESS;
    }
}
