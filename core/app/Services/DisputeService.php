<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\Dispute;
use App\Models\DisputeLog;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class DisputeService
{
    public function openForDeposit(Deposit $deposit, array $payload, string $actorType = 'admin', ?int $actorId = null): Dispute
    {
        $merchant = $deposit->user;

        if (!$merchant) {
            throw new RuntimeException('Merchant not found for this transaction');
        }

        if ((int) $deposit->status === Status::PAYMENT_REFUNDED) {
            throw new RuntimeException('Dispute cannot be opened on a refunded transaction');
        }

        $successfulStatuses = [Status::PAYMENT_SUCCESS];
        if (!in_array((int) $deposit->status, $successfulStatuses, true)) {
            throw new RuntimeException('Dispute can only be opened on a successful transaction');
        }

        $maxAgeDays = (int) (config('services.dispute.max_age_days') ?? 120);
        if ($maxAgeDays > 0 && $deposit->created_at && $deposit->created_at->lt(now()->subDays($maxAgeDays))) {
            throw new RuntimeException('This transaction is too old to open a dispute');
        }

        $activeExists = Dispute::query()
            ->where('deposit_id', $deposit->id)
            ->whereIn('status', Dispute::ACTIVE_STATUSES)
            ->exists();

        if ($activeExists) {
            throw new RuntimeException('An active dispute already exists for this transaction');
        }

        $gatewayAlias = strtolower((string) optional($deposit->gateway)->alias);
        $gatewayName = strtolower((string) optional($deposit->gateway)->name);
        $provider = $gatewayAlias ?: $gatewayName;

        $customerEmail = (string) data_get($deposit->detail, 'customer.email', '');
        if ($customerEmail === '' && $deposit->relationLoaded('apiPayment')) {
            $customerEmail = (string) data_get($deposit->apiPayment, 'customer.email', '');
        }

        return DB::transaction(function () use ($deposit, $merchant, $payload, $actorType, $actorId, $provider, $customerEmail) {
            $dispute = new Dispute();
            $dispute->dispute_id = $this->generateDisputeId();
            $dispute->deposit_id = $deposit->id;
            $dispute->merchant_id = $merchant->id;
            $dispute->transaction_id = $deposit->trx;
            $dispute->provider = $provider ?: 'unknown';
            $dispute->customer_email = $customerEmail ?: null;
            $dispute->amount = (float) $deposit->amount;
            $dispute->currency = $deposit->method_currency ?: gs('cur_text');
            $dispute->dispute_fee = 29;
            $dispute->reason = trim((string) ($payload['reason'] ?? '')) ?: null;
            $dispute->provider_email = trim((string) ($payload['provider_email'] ?? '')) ?: null;
            $dispute->status = Dispute::STATUS_OPEN;
            $dispute->opened_at = now();
            $dispute->admin_notes = trim((string) ($payload['admin_notes'] ?? '')) ?: null;
            $dispute->merchant_notes = trim((string) ($payload['merchant_notes'] ?? '')) ?: null;
            $dispute->created_by_type = $actorType;
            $dispute->created_by_id = $actorId;
            $dispute->save();

            $this->addLog(
                $dispute,
                action: 'opened',
                oldStatus: null,
                newStatus: Dispute::STATUS_OPEN,
                actorType: $actorType,
                actorId: $actorId,
                note: $dispute->reason
            );

            $this->notifyMerchant(
                $merchant,
                'New dispute opened',
                'A dispute (' . $dispute->dispute_id . ') has been opened on transaction ' . $dispute->transaction_id . '. Current status: Open.'
            );

            $this->notifyAdmin($merchant->id, 'Dispute opened: ' . $dispute->dispute_id, route('admin.disputes.show', $dispute->id));

            return $dispute->fresh(['merchant', 'deposit']);
        });
    }

    public function setPendingResolutionByMerchant(Dispute $dispute, int $merchantId, ?string $note = null): Dispute
    {
        if ((int) $dispute->merchant_id !== $merchantId) {
            throw new RuntimeException('Unauthorized dispute action');
        }

        if (!$dispute->isActive()) {
            throw new RuntimeException('This dispute is already closed');
        }

        $oldStatus = $dispute->status;

        $dispute->status = Dispute::STATUS_PENDING_RESOLUTION;
        $dispute->resolution_deadline = now()->addDay();
        if ($note !== null && trim($note) !== '') {
            $dispute->merchant_notes = trim($note);
        }
        $dispute->save();

        $this->addLog(
            $dispute,
            action: 'merchant_requested_resolution',
            oldStatus: $oldStatus,
            newStatus: $dispute->status,
            actorType: 'merchant',
            actorId: $merchantId,
            note: $note
        );

        $this->notifyAdmin(
            $dispute->merchant_id,
            'Merchant requested dispute resolution: ' . $dispute->dispute_id,
            route('admin.disputes.show', $dispute->id)
        );

        $this->notifyMerchant(
            $dispute->merchant,
            'Resolution window started',
            'Dispute ' . $dispute->dispute_id . ' is now pending resolution. Deadline: ' . optional($dispute->resolution_deadline)->format('Y-m-d H:i') . ' UTC.'
        );

        return $dispute->fresh();
    }

    public function updateStatusByAdmin(Dispute $dispute, string $newStatus, int $adminId, array $options = []): Dispute
    {
        if (!in_array($newStatus, array_merge(Dispute::ACTIVE_STATUSES, Dispute::TERMINAL_STATUSES), true)) {
            throw new RuntimeException('Invalid dispute status');
        }

        $oldStatus = $dispute->status;

        DB::transaction(function () use ($dispute, $newStatus, $adminId, $oldStatus, $options) {
            $dispute->status = $newStatus;

            if (!empty($options['admin_notes'])) {
                $dispute->admin_notes = trim((string) $options['admin_notes']);
            }
            if (!empty($options['merchant_notes'])) {
                $dispute->merchant_notes = trim((string) $options['merchant_notes']);
            }

            if ($newStatus === Dispute::STATUS_PENDING_RESOLUTION && !$dispute->resolution_deadline) {
                $dispute->resolution_deadline = now()->addDay();
            }

            if (in_array($newStatus, Dispute::TERMINAL_STATUSES, true)) {
                $dispute->resolution_deadline = null;
            }

            if ($newStatus === Dispute::STATUS_RESOLVED) {
                $dispute->resolved_at = now();
            }
            if ($newStatus === Dispute::STATUS_REFUNDED) {
                $dispute->refunded_at = now();
                $this->debitTransactionAmount($dispute, 'admin_forced_refund');
            }
            if ($newStatus === Dispute::STATUS_EXPIRED) {
                $dispute->expired_at = now();
                $this->debitTransactionAmount($dispute, 'expired_dispute');
            }
            if ($newStatus === Dispute::STATUS_CLOSED) {
                $dispute->closed_at = now();
            }
            if ($newStatus === Dispute::STATUS_CANCELLED) {
                $dispute->cancelled_at = now();
            }

            $dispute->save();

            $this->addLog(
                $dispute,
                action: 'status_changed',
                oldStatus: $oldStatus,
                newStatus: $newStatus,
                actorType: 'admin',
                actorId: $adminId,
                note: $options['admin_notes'] ?? null
            );

            if (in_array($newStatus, [Dispute::STATUS_RESOLVED, Dispute::STATUS_REFUNDED, Dispute::STATUS_EXPIRED, Dispute::STATUS_CLOSED], true)) {
                $this->chargeDisputeFee($dispute, 'admin', $adminId);
            }
        });

        $dispute->refresh();

        $this->notifyMerchant(
            $dispute->merchant,
            'Dispute status updated',
            'Dispute ' . $dispute->dispute_id . ' status changed to ' . str_replace('_', ' ', $dispute->status) . '.'
        );

        return $dispute;
    }

    public function sendProviderEmail(Dispute $dispute, int $adminId, string $email, string $message, ?string $subject = null): Dispute
    {
        $subject = trim($subject ?: 'Dispute notification - ' . $dispute->dispute_id);

        Mail::raw($message, function ($mail) use ($email, $subject) {
            $mail->to($email)->subject($subject);
        });

        $oldStatus = $dispute->status;
        if ($dispute->isActive()) {
            $dispute->status = Dispute::STATUS_PROVIDER_NOTIFIED;
        }
        $dispute->provider_email = $email;
        $dispute->provider_email_sent_at = now();
        $dispute->save();

        $this->addLog(
            $dispute,
            action: 'provider_notified',
            oldStatus: $oldStatus,
            newStatus: $dispute->status,
            actorType: 'admin',
            actorId: $adminId,
            note: 'Provider email sent to ' . $email
        );

        return $dispute;
    }

    public function processExpiredPendingResolution(): int
    {
        $count = 0;

        Dispute::query()
            ->with(['merchant', 'deposit'])
            ->where('status', Dispute::STATUS_PENDING_RESOLUTION)
            ->whereNotNull('resolution_deadline')
            ->where('resolution_deadline', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($disputes) use (&$count) {
                foreach ($disputes as $dispute) {
                    DB::transaction(function () use ($dispute, &$count) {
                        $oldStatus = $dispute->status;
                        $dispute->status = Dispute::STATUS_REFUNDED;
                        $dispute->expired_at = now();
                        $dispute->refunded_at = now();
                        $dispute->resolution_deadline = null;
                        $dispute->save();

                        $this->debitTransactionAmount($dispute, 'auto_refund_deadline_expired');
                        $this->chargeDisputeFee($dispute, 'system', null);

                        $this->addLog(
                            $dispute,
                            action: 'auto_refunded_on_deadline',
                            oldStatus: $oldStatus,
                            newStatus: $dispute->status,
                            actorType: 'system',
                            actorId: null,
                            note: 'Resolution deadline exceeded'
                        );

                        $this->notifyMerchant(
                            $dispute->merchant,
                            'Dispute auto-refunded',
                            'Dispute ' . $dispute->dispute_id . ' reached the 24h deadline and was automatically refunded.'
                        );

                        $this->notifyAdmin(
                            $dispute->merchant_id,
                            'Dispute auto-refunded: ' . $dispute->dispute_id,
                            route('admin.disputes.show', $dispute->id)
                        );

                        $count++;
                    });
                }
            });

        return $count;
    }

    public function merchantMetrics(User $merchant): array
    {
        $depositsQuery = Deposit::query()->where('user_id', $merchant->id)->successful();
        $totalTransactions = (clone $depositsQuery)->count();
        $totalVolume = (float) (clone $depositsQuery)->sum('amount');

        $disputes = Dispute::query()->where('merchant_id', $merchant->id);
        $totalDisputes = (clone $disputes)->count();
        $openDisputes = (clone $disputes)->whereIn('status', Dispute::ACTIVE_STATUSES)->count();
        $openDisputeAmount = (float) (clone $disputes)->whereIn('status', Dispute::ACTIVE_STATUSES)->sum('amount');
        $totalDisputeFees = (float) (clone $disputes)->whereNotNull('dispute_fee_charged_at')->sum('dispute_fee');
        $refundedAmount = (float) (clone $disputes)->whereNotNull('transaction_amount_debited_at')->sum('refunded_amount');

        $disputeRate = $totalTransactions > 0 ? ($totalDisputes / $totalTransactions) * 100 : 0;
        $disputeVolumeRate = $totalVolume > 0 ? ($openDisputeAmount / $totalVolume) * 100 : 0;
        $disputeExposureBalanceRate = (float) $merchant->balance > 0 ? ($openDisputeAmount / (float) $merchant->balance) * 100 : 0;

        return [
            'total_transactions' => $totalTransactions,
            'total_volume' => $totalVolume,
            'total_disputes' => $totalDisputes,
            'open_disputes' => $openDisputes,
            'open_dispute_amount' => $openDisputeAmount,
            'total_dispute_fees' => $totalDisputeFees,
            'refunded_amount' => $refundedAmount,
            'dispute_rate' => $disputeRate,
            'dispute_volume_rate' => $disputeVolumeRate,
            'dispute_exposure_balance_rate' => $disputeExposureBalanceRate,
            'merchant_risk_level' => $this->merchantRiskLevel($disputeRate, $disputeVolumeRate),
        ];
    }

    public function merchantRiskLevel(float $disputeRate, float $disputeVolumeRate): string
    {
        $score = max($disputeRate, $disputeVolumeRate);

        return match (true) {
            $score >= 5 => 'high',
            $score >= 2 => 'medium',
            default => 'low',
        };
    }

    private function chargeDisputeFee(Dispute $dispute, string $actorType, ?int $actorId): void
    {
        if (!$dispute->canChargeFee()) {
            return;
        }

        $merchant = $dispute->merchant()->lockForUpdate()->first();
        if (!$merchant) {
            throw new RuntimeException('Merchant not found for dispute fee charge');
        }

        $merchant->balance = (float) $merchant->balance - (float) $dispute->dispute_fee;
        $merchant->save();

        $trx = new Transaction();
        $trx->user_id = $merchant->id;
        $trx->amount = (float) $dispute->dispute_fee;
        $trx->post_balance = (float) $merchant->balance;
        $trx->charge = 0;
        $trx->trx_type = '-';
        $trx->details = 'Dispute fee charged for ' . $dispute->dispute_id;
        $trx->trx = getTrx();
        $trx->remark = 'dispute_fee';
        $trx->save();

        $meta = (array) ($dispute->meta ?? []);
        $meta['dispute_fee_transaction_id'] = $trx->id;
        $dispute->meta = $meta;
        $dispute->dispute_fee_charged_at = now();
        $dispute->save();

        $this->addLog(
            $dispute,
            action: 'fee_charged',
            oldStatus: $dispute->status,
            newStatus: $dispute->status,
            actorType: $actorType,
            actorId: $actorId,
            note: 'Charged dispute fee: $' . number_format((float) $dispute->dispute_fee, 2)
        );
    }

    private function debitTransactionAmount(Dispute $dispute, string $reason): void
    {
        if ($dispute->transaction_amount_debited_at !== null) {
            return;
        }

        $merchant = $dispute->merchant()->lockForUpdate()->first();
        if (!$merchant) {
            throw new RuntimeException('Merchant not found for refund debit');
        }

        $merchant->balance = (float) $merchant->balance - (float) $dispute->amount;
        $merchant->save();

        $trx = new Transaction();
        $trx->user_id = $merchant->id;
        $trx->amount = (float) $dispute->amount;
        $trx->post_balance = (float) $merchant->balance;
        $trx->charge = 0;
        $trx->trx_type = '-';
        $trx->details = 'Dispute refund debit for ' . $dispute->dispute_id . ' (' . $reason . ')';
        $trx->trx = getTrx();
        $trx->remark = 'dispute_refund';
        $trx->save();

        $dispute->refunded_amount = (float) $dispute->amount;
        $dispute->transaction_amount_debited_at = now();
        $dispute->save();

        $deposit = $dispute->deposit;
        if ($deposit && (int) $deposit->status !== Status::PAYMENT_REFUNDED) {
            $deposit->status = Status::PAYMENT_REFUNDED;
            if (Schema::hasColumn('deposits', 'refunded_at')) {
                $deposit->refunded_at = now();
            }
            $deposit->admin_feedback = 'Refunded by dispute flow [' . $dispute->dispute_id . ']';
            $deposit->save();
        }

        $this->addLog(
            $dispute,
            action: 'transaction_amount_debited',
            oldStatus: $dispute->status,
            newStatus: $dispute->status,
            actorType: 'system',
            actorId: null,
            note: 'Debited merchant balance by transaction amount: $' . number_format((float) $dispute->amount, 2)
        );
    }

    private function addLog(
        Dispute $dispute,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        string $actorType,
        ?int $actorId,
        ?string $note = null,
        ?array $context = null
    ): void {
        $log = new DisputeLog();
        $log->dispute_id = $dispute->id;
        $log->action = $action;
        $log->old_status = $oldStatus;
        $log->new_status = $newStatus;
        $log->actor_type = $actorType;
        $log->actor_id = $actorId;
        $log->note = $note;
        $log->context = $context;
        $log->save();
    }

    private function generateDisputeId(): string
    {
        do {
            $candidate = 'DSP-' . strtoupper(Str::random(10));
        } while (Dispute::where('dispute_id', $candidate)->exists());

        return $candidate;
    }

    private function notifyMerchant(?User $merchant, string $subject, string $message): void
    {
        if (!$merchant) {
            return;
        }

        notify($merchant, 'DEFAULT', [
            'subject' => $subject,
            'message' => $message,
        ], ['email', 'push']);
    }

    private function notifyAdmin(int $merchantId, string $title, string $url): void
    {
        $notification = new AdminNotification();
        $notification->user_id = $merchantId;
        $notification->title = $title;
        $notification->click_url = $url;
        $notification->save();
    }
}
