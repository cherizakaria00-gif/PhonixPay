<?php

namespace App\Console\Commands;

use App\Services\BictorysDepositSyncService;
use Illuminate\Console\Command;

class RepairBictorysDepositsCommand extends Command
{
    protected $signature = 'bictorys:repair
        {--lookback-hours=120 : Lookback window for pending deposits. Use a large value for historical repair}
        {--max-pending=250 : Maximum pending deposits checked per gateway}
        {--allow-charge-lookup : Enable fallback GET /pay/v1/charges/{id} lookups (manual use only)}
        {--max-charge-lookups=3 : Maximum fallback charge lookups per gateway}
        {--replay-logs : Hydrate missing op_token from laravel.log and replay logged decisions}
        {--success-refs= : Comma separated trx/charge_id references to mark as successful}
        {--reject-refs= : Comma separated trx/charge_id references to mark as rejected}
        {--dry-run : Preview only, no database updates}';

    protected $description = 'Reconcile stuck Bictorys deposits (manual webhook reconciliation, optional charge lookup fallback).';

    public function handle(BictorysDepositSyncService $service): int
    {
        $result = $service->syncPendingDeposits([
            'lookback_hours' => (int) $this->option('lookback-hours'),
            'max_pending_per_gateway' => (int) $this->option('max-pending'),
            'allow_charge_lookup' => (bool) $this->option('allow-charge-lookup'),
            'max_charge_lookups' => (int) $this->option('max-charge-lookups'),
            'replay_logs' => (bool) $this->option('replay-logs'),
            'success_refs' => (string) ($this->option('success-refs') ?? ''),
            'reject_refs' => (string) ($this->option('reject-refs') ?? ''),
            'dry_run' => (bool) $this->option('dry-run'),
        ]);

        $this->info('Bictorys repair completed.');
        $this->line('Checked: ' . (int) ($result['checked'] ?? 0));
        $this->line('Synced success: ' . (int) ($result['synced_success'] ?? 0));
        $this->line('Synced rejected: ' . (int) ($result['synced_rejected'] ?? 0));
        $this->line('Hydrated tokens from logs: ' . (int) ($result['hydrated_tokens'] ?? 0));
        $this->line('Manual success refs applied: ' . (int) ($result['manual_success'] ?? 0));
        $this->line('Manual reject refs applied: ' . (int) ($result['manual_rejected'] ?? 0));
        $this->line('Gateways touched: ' . (int) ($result['gateways'] ?? 0));
        $this->line('Fallback charge lookups enabled: ' . ($this->option('allow-charge-lookup') ? 'yes' : 'no'));

        if ($this->option('dry-run')) {
            $this->warn('Dry-run mode: no database changes were committed.');
        }

        return self::SUCCESS;
    }
}
