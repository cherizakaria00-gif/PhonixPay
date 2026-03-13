<?php

namespace App\Console\Commands;

use App\Constants\Status;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillDepositChargesCommand extends Command
{
    protected $signature = 'charges:backfill-deposits
        {--dry-run : Preview changes without writing to database}
        {--limit=0 : Maximum deposits to scan (0 = all)}';

    protected $description = 'Backfill successful deposit charge/payment_charge from historical transaction rows.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));

        $depositsQuery = Deposit::query()
            ->where('status', Status::PAYMENT_SUCCESS)
            ->where(function ($query) {
                $query->whereNull('charge')
                    ->orWhere('charge', '<=', 0)
                    ->orWhereNull('payment_charge')
                    ->orWhere('payment_charge', '<=', 0);
            })
            ->orderBy('id');

        if ($limit > 0) {
            $depositsQuery->limit($limit);
        }

        $deposits = $depositsQuery->get(['id', 'user_id', 'trx', 'charge', 'payment_charge']);

        if ($deposits->isEmpty()) {
            $this->info('No deposits need charge backfill.');
            return self::SUCCESS;
        }

        $userIds = $deposits->pluck('user_id')->unique()->values()->all();
        $trxRefs = $deposits->pluck('trx')->filter()->unique()->values()->all();

        $chargeRows = Transaction::query()
            ->whereIn('remark', ['gateway_charge', 'payment_charge'])
            ->whereIn('user_id', $userIds)
            ->whereIn('trx', $trxRefs)
            ->select([
                'user_id',
                'trx',
                DB::raw("SUM(CASE WHEN remark = 'gateway_charge' THEN amount ELSE 0 END) as gateway_charge_total"),
                DB::raw("SUM(CASE WHEN remark = 'payment_charge' THEN amount ELSE 0 END) as payment_charge_total"),
            ])
            ->groupBy('user_id', 'trx')
            ->get()
            ->keyBy(function ($row) {
                return $row->user_id . '|' . $row->trx;
            });

        $scanned = 0;
        $matched = 0;
        $updated = 0;

        foreach ($deposits as $deposit) {
            $scanned++;
            $row = $chargeRows->get($deposit->user_id . '|' . $deposit->trx);

            if (!$row) {
                continue;
            }

            $matched++;

            $currentGatewayCharge = (float) ($deposit->charge ?? 0);
            $currentPaymentCharge = (float) ($deposit->payment_charge ?? 0);
            $historicalGatewayCharge = max(0, (float) ($row->gateway_charge_total ?? 0));
            $historicalPaymentCharge = max(0, (float) ($row->payment_charge_total ?? 0));

            $newGatewayCharge = $currentGatewayCharge > 0 ? $currentGatewayCharge : $historicalGatewayCharge;
            $newPaymentCharge = $currentPaymentCharge > 0 ? $currentPaymentCharge : $historicalPaymentCharge;

            $gatewayChanged = abs($newGatewayCharge - $currentGatewayCharge) > 0.0000001;
            $paymentChanged = abs($newPaymentCharge - $currentPaymentCharge) > 0.0000001;

            if (!$gatewayChanged && !$paymentChanged) {
                continue;
            }

            $updated++;

            if (!$dryRun) {
                $deposit->charge = $newGatewayCharge;
                $deposit->payment_charge = $newPaymentCharge;
                $deposit->save();
            }
        }

        $this->info('Deposit charge backfill completed.');
        $this->line('Scanned: ' . $scanned);
        $this->line('Matched with historical charge rows: ' . $matched);
        $this->line('Updated: ' . $updated);

        if ($dryRun) {
            $this->warn('Dry-run mode: no database changes were made.');
        }

        return self::SUCCESS;
    }
}

