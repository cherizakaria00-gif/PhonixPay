<?php

namespace App\Console\Commands;

use App\Constants\Status;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecalculateMerchantDepositFeesCommand extends Command
{
    protected $signature = 'charges:recalculate-merchant-fees
        {--dry-run : Preview changes without writing to database}
        {--user-id= : Process deposits for one merchant only}
        {--from-id=0 : Minimum deposit id (inclusive)}
        {--to-id=0 : Maximum deposit id (inclusive)}
        {--limit=0 : Maximum deposits to scan (0 = all)}
        {--chunk=200 : Chunk size}
        {--statuses=successful : Comma separated statuses (successful,initiated,pending,rejected,canceled,refunded,all)}
        {--compensate : Create balance adjustment transactions for successful deposits}
        {--allow-debit : With --compensate, allow debits when recalculated fee is higher than stored fee}';

    protected $description = 'Recalculate historical merchant payment fees using current merchant fee settings.';

    private const EPSILON = 0.0000001;

    public function handle(PlanService $planService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $fromId = max(0, (int) $this->option('from-id'));
        $toId = max(0, (int) $this->option('to-id'));
        $userId = $this->option('user-id') !== null ? (int) $this->option('user-id') : null;
        $compensate = (bool) $this->option('compensate');
        $allowDebit = (bool) $this->option('allow-debit');

        $statusValues = $this->resolveStatuses((string) $this->option('statuses'));
        if ($statusValues === null) {
            $this->error('Invalid --statuses value.');
            return self::FAILURE;
        }

        $hasFeeAmountColumn = Schema::hasColumn('deposits', 'fee_amount');
        $hasNetAmountColumn = Schema::hasColumn('deposits', 'net_amount');

        $query = Deposit::query()
            ->select([
                'id',
                'user_id',
                'trx',
                'status',
                'amount',
                'charge',
                'payment_charge',
                'rate',
                'gateway_amount',
                'final_amount',
                'detail',
            ])
            ->orderBy('id');

        if ($statusValues !== []) {
            $query->whereIn('status', $statusValues);
        }

        if ($userId && $userId > 0) {
            $query->where('user_id', $userId);
        }

        if ($fromId > 0) {
            $query->where('id', '>=', $fromId);
        }

        if ($toId > 0) {
            $query->where('id', '<=', $toId);
        }

        $summary = [
            'scanned' => 0,
            'matched_users' => 0,
            'changed' => 0,
            'updated' => 0,
            'compensated' => 0,
            'skipped_missing_user' => 0,
            'skipped_debit_disabled' => 0,
            'skipped_insufficient_balance' => 0,
            'total_credit' => 0.0,
            'total_debit' => 0.0,
        ];

        $processed = 0;

        $query->chunkById($chunkSize, function ($deposits) use (
            &$summary,
            &$processed,
            $limit,
            $planService,
            $dryRun,
            $compensate,
            $allowDebit,
            $hasFeeAmountColumn,
            $hasNetAmountColumn
        ) {
            if ($deposits->isEmpty()) {
                return true;
            }

            $users = User::query()
                ->whereIn('id', $deposits->pluck('user_id')->unique()->all())
                ->get()
                ->keyBy('id');

            foreach ($deposits as $deposit) {
                if ($limit > 0 && $processed >= $limit) {
                    return false;
                }

                $processed++;
                $summary['scanned']++;

                /** @var User|null $user */
                $user = $users->get((int) $deposit->user_id);
                if (!$user) {
                    $summary['skipped_missing_user']++;
                    continue;
                }

                $summary['matched_users']++;

                $oldGatewayCharge = $this->toMoney((float) ($deposit->charge ?? 0));
                $oldPaymentCharge = $this->toMoney((float) ($deposit->payment_charge ?? 0));
                $expectedFeeData = $planService->calculateFees($user, (float) ($deposit->amount ?? 0));
                $newPaymentCharge = $this->toMoney((float) ($expectedFeeData['fee_amount'] ?? 0));

                if (!$this->hasDifference($oldPaymentCharge, $newPaymentCharge)) {
                    continue;
                }

                $summary['changed']++;

                $newTotalCharge = $this->toMoney($oldGatewayCharge + $newPaymentCharge);
                $newNetAmount = $this->toMoney(max(0, (float) $deposit->amount - $newTotalCharge));
                $newFinalAmount = $this->recalculateFinalGatewayAmount(
                    (float) ($deposit->gateway_amount ?? 0),
                    (float) ($deposit->amount ?? 0),
                    (float) ($deposit->rate ?? 0),
                    $newTotalCharge
                );

                $credit = $this->toMoney(max(0, $oldPaymentCharge - $newPaymentCharge));
                $debit = $this->toMoney(max(0, $newPaymentCharge - $oldPaymentCharge));

                if ($dryRun) {
                    $summary['updated']++;
                    if ($compensate && (int) $deposit->status === Status::PAYMENT_SUCCESS) {
                        if ($credit > 0) {
                            $summary['compensated']++;
                            $summary['total_credit'] = $this->toMoney($summary['total_credit'] + $credit);
                        } elseif ($debit > 0) {
                            if ($allowDebit) {
                                $summary['compensated']++;
                                $summary['total_debit'] = $this->toMoney($summary['total_debit'] + $debit);
                            } else {
                                $summary['skipped_debit_disabled']++;
                            }
                        }
                    }
                    continue;
                }

                DB::transaction(function () use (
                    $deposit,
                    $newPaymentCharge,
                    $newNetAmount,
                    $newFinalAmount,
                    $oldPaymentCharge,
                    $credit,
                    $debit,
                    $compensate,
                    $allowDebit,
                    $hasFeeAmountColumn,
                    $hasNetAmountColumn,
                    &$summary
                ) {
                    /** @var Deposit|null $lockedDeposit */
                    $lockedDeposit = Deposit::query()->lockForUpdate()->find($deposit->id);
                    if (!$lockedDeposit) {
                        return;
                    }

                    $lockedDeposit->payment_charge = $newPaymentCharge;
                    if ($hasFeeAmountColumn) {
                        $lockedDeposit->fee_amount = $newPaymentCharge;
                    }
                    if ($hasNetAmountColumn) {
                        $lockedDeposit->net_amount = $newNetAmount;
                    }
                    $lockedDeposit->final_amount = $newFinalAmount;

                    $detail = json_decode(json_encode($lockedDeposit->detail), true) ?: [];
                    $detail['fee_backfill'] = [
                        'applied_at' => now()->toDateTimeString(),
                        'old_payment_charge' => $oldPaymentCharge,
                        'new_payment_charge' => $newPaymentCharge,
                        'source' => 'charges:recalculate-merchant-fees',
                    ];
                    $lockedDeposit->detail = $detail;
                    $lockedDeposit->save();

                    $summary['updated']++;

                    if (!$compensate || (int) $lockedDeposit->status !== Status::PAYMENT_SUCCESS) {
                        return;
                    }

                    if ($credit <= 0 && $debit <= 0) {
                        return;
                    }

                    if ($debit > 0 && !$allowDebit) {
                        $summary['skipped_debit_disabled']++;
                        return;
                    }

                    /** @var User|null $lockedUser */
                    $lockedUser = User::query()->lockForUpdate()->find($lockedDeposit->user_id);
                    if (!$lockedUser) {
                        return;
                    }

                    $alreadyAdjusted = Transaction::query()
                        ->where('user_id', $lockedUser->id)
                        ->where('trx', $lockedDeposit->trx)
                        ->where('remark', 'fee_backfill_adjustment')
                        ->exists();

                    if ($alreadyAdjusted) {
                        return;
                    }

                    if ($credit > 0) {
                        $lockedUser->balance = $this->toMoney((float) $lockedUser->balance + $credit);
                        $lockedUser->save();

                        $trx = new Transaction();
                        $trx->user_id = $lockedUser->id;
                        $trx->amount = $credit;
                        $trx->post_balance = $lockedUser->balance;
                        $trx->charge = 0;
                        $trx->trx_type = '+';
                        $trx->details = 'Fee backfill credit for payment ' . $lockedDeposit->trx;
                        $trx->trx = $lockedDeposit->trx;
                        $trx->remark = 'fee_backfill_adjustment';
                        $trx->save();

                        $summary['compensated']++;
                        $summary['total_credit'] = $this->toMoney($summary['total_credit'] + $credit);
                        return;
                    }

                    if ($debit > 0) {
                        if ((float) $lockedUser->balance < $debit) {
                            $summary['skipped_insufficient_balance']++;
                            return;
                        }

                        $lockedUser->balance = $this->toMoney((float) $lockedUser->balance - $debit);
                        $lockedUser->save();

                        $trx = new Transaction();
                        $trx->user_id = $lockedUser->id;
                        $trx->amount = $debit;
                        $trx->post_balance = $lockedUser->balance;
                        $trx->charge = 0;
                        $trx->trx_type = '-';
                        $trx->details = 'Fee backfill debit for payment ' . $lockedDeposit->trx;
                        $trx->trx = $lockedDeposit->trx;
                        $trx->remark = 'fee_backfill_adjustment';
                        $trx->save();

                        $summary['compensated']++;
                        $summary['total_debit'] = $this->toMoney($summary['total_debit'] + $debit);
                    }
                });
            }

            return true;
        });

        $this->info('Merchant fee backfill completed.');
        $this->line('Scanned: ' . $summary['scanned']);
        $this->line('Matched merchants: ' . $summary['matched_users']);
        $this->line('Detected fee changes: ' . $summary['changed']);
        $this->line('Updated deposits: ' . $summary['updated']);
        $this->line('Compensation transactions: ' . $summary['compensated']);
        $this->line('Total credit: ' . $this->toMoney($summary['total_credit']));
        $this->line('Total debit: ' . $this->toMoney($summary['total_debit']));
        $this->line('Skipped (missing user): ' . $summary['skipped_missing_user']);
        $this->line('Skipped (debit disabled): ' . $summary['skipped_debit_disabled']);
        $this->line('Skipped (insufficient balance for debit): ' . $summary['skipped_insufficient_balance']);

        if ($dryRun) {
            $this->warn('Dry-run mode: no database changes were made.');
        }

        return self::SUCCESS;
    }

    private function resolveStatuses(string $rawStatuses): ?array
    {
        $normalized = collect(explode(',', strtolower($rawStatuses)))
            ->map(fn($value) => trim($value))
            ->filter()
            ->values();

        if ($normalized->isEmpty()) {
            return [Status::PAYMENT_SUCCESS];
        }

        if ($normalized->contains('all')) {
            return [];
        }

        $map = [
            'initiated' => Status::PAYMENT_INITIATE,
            'successful' => Status::PAYMENT_SUCCESS,
            'pending' => Status::PAYMENT_PENDING,
            'rejected' => Status::PAYMENT_REJECT,
            'canceled' => Status::PAYMENT_CANCEL,
            'cancelled' => Status::PAYMENT_CANCEL,
            'refunded' => Status::PAYMENT_REFUNDED,
        ];

        $statuses = [];
        foreach ($normalized as $statusName) {
            if (!array_key_exists($statusName, $map)) {
                return null;
            }
            $statuses[] = $map[$statusName];
        }

        return array_values(array_unique($statuses));
    }

    private function recalculateFinalGatewayAmount(
        float $gatewayAmount,
        float $amountBase,
        float $rate,
        float $newTotalChargeBase
    ): float {
        $effectiveRate = $rate > 0 ? $rate : 1.0;
        $grossGateway = $gatewayAmount > 0
            ? $gatewayAmount
            : $this->toMoney($amountBase * $effectiveRate);

        $newTotalChargeGateway = $this->toMoney($newTotalChargeBase * $effectiveRate);

        return $this->toMoney(max(0, $grossGateway - $newTotalChargeGateway));
    }

    private function hasDifference(float $left, float $right): bool
    {
        return abs($left - $right) > self::EPSILON;
    }

    private function toMoney(float $value): float
    {
        return round($value, 8);
    }
}
