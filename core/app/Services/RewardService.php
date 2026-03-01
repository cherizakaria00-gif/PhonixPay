<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\Deposit;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\RewardLedger;
use App\Models\RewardLevel;
use App\Models\RewardsWallet;
use App\Models\User;
use App\Models\UserRewardStatus;
use App\Models\WithdrawSetting;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RewardService
{
    public const REFERRAL_BONUS_CENTS = 500;
    public const DEFAULT_CURRENCY = 'USD';

    public function ensureReferralCodeForUser(User $user): ReferralCode
    {
        if (!Schema::hasTable('referral_codes')) {
            return new ReferralCode([
                'user_id' => $user->id,
                'code' => '',
                'is_active' => false,
            ]);
        }

        $existing = ReferralCode::where('user_id', $user->id)->first();
        if ($existing) {
            if (!$existing->is_active) {
                $existing->is_active = true;
                $existing->save();
            }

            return $existing;
        }

        do {
            $candidate = strtoupper(Str::random(8));
        } while (ReferralCode::where('code', $candidate)->exists());

        return ReferralCode::create([
            'user_id' => $user->id,
            'code' => $candidate,
            'is_active' => true,
        ]);
    }

    public function regenerateReferralCode(User $user): ReferralCode
    {
        if (!Schema::hasTable('referral_codes')) {
            return $this->ensureReferralCodeForUser($user);
        }

        $existing = ReferralCode::where('user_id', $user->id)->first();
        if (!$existing) {
            return $this->ensureReferralCodeForUser($user);
        }

        do {
            $candidate = strtoupper(Str::random(8));
        } while (ReferralCode::where('code', $candidate)->exists());

        $existing->code = $candidate;
        $existing->is_active = true;
        $existing->save();

        return $existing;
    }

    public function getReferralLink(User $user): string
    {
        $code = $this->ensureReferralCodeForUser($user);

        $routeName = Route::has('signup') ? 'signup' : 'user.register';

        if (!$code->code) {
            return route($routeName);
        }

        return route($routeName, ['ref' => $code->code]);
    }

    public function registerReferral(User $referredUser, ?string $rawCode, array $metadata = []): ?Referral
    {
        if (!$rawCode || !Schema::hasTable('referrals') || !Schema::hasTable('referral_codes')) {
            return null;
        }

        $code = strtoupper(trim($rawCode));
        if ($code === '') {
            return null;
        }

        return DB::transaction(function () use ($referredUser, $code, $metadata) {
            $alreadyAssigned = Referral::where('referred_user_id', $referredUser->id)->lockForUpdate()->first();
            if ($alreadyAssigned) {
                return $alreadyAssigned;
            }

            $referralCode = ReferralCode::where('code', $code)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$referralCode) {
                return null;
            }

            $referrer = User::lockForUpdate()->find($referralCode->user_id);
            if (!$referrer || $this->isSelfReferral($referrer, $referredUser)) {
                return null;
            }

            $referral = Referral::create([
                'referrer_user_id' => $referrer->id,
                'referred_user_id' => $referredUser->id,
                'referral_code_id' => $referralCode->id,
                'status' => Referral::STATUS_REGISTERED,
                'registered_at' => now(),
                'metadata' => $this->sanitizeMetadata($metadata),
            ]);

            return $referral;
        });
    }

    public function registerReferralFromRequest(User $referredUser, Request $request): ?Referral
    {
        $code = $request->input('referral_code') ?: $request->query('ref') ?: session('reward_referral_code');

        $metadata = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'utm_source' => $request->input('utm_source'),
            'utm_medium' => $request->input('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign'),
        ];

        return $this->registerReferral($referredUser, $code, $metadata);
    }

    public function handleSuccessfulDeposit(Deposit $deposit): void
    {
        if (!Schema::hasTable('referrals') || !Schema::hasTable('rewards_ledger')) {
            return;
        }

        DB::transaction(function () use ($deposit) {
            $lockedDeposit = Deposit::query()->lockForUpdate()->find($deposit->id);
            if (!$lockedDeposit || (int) $lockedDeposit->status !== Status::PAYMENT_SUCCESS) {
                return;
            }

            $referral = Referral::query()
                ->where('referred_user_id', $lockedDeposit->user_id)
                ->lockForUpdate()
                ->first();

            if (!$referral) {
                return;
            }

            $referrerId = (int) $referral->referrer_user_id;

            $changedDeposit = false;
            if ((int) $lockedDeposit->referrer_user_id !== $referrerId && Schema::hasColumn('deposits', 'referrer_user_id')) {
                $lockedDeposit->referrer_user_id = $referrerId;
                $changedDeposit = true;
            }

            if (Schema::hasColumn('deposits', 'gross_amount_cents') && !$lockedDeposit->gross_amount_cents) {
                $lockedDeposit->gross_amount_cents = $this->toCents((float) $lockedDeposit->amount);
                $changedDeposit = true;
            }

            if ($changedDeposit) {
                $lockedDeposit->save();
            }

            $rewardStatus = $this->ensureUserRewardStatus($referrerId);

            if ($referral->status !== Referral::STATUS_QUALIFIED) {
                $referral->status = Referral::STATUS_QUALIFIED;
                $referral->qualified_at = now();
                $referral->revoked_at = null;
                $referral->first_successful_deposit_id = $lockedDeposit->id;
                $referral->save();

                $bonusEntry = $this->addLedgerEntry([
                    'user_id' => $referrerId,
                    'type' => RewardLedger::TYPE_REFERRAL_BONUS,
                    'amount_cents' => self::REFERRAL_BONUS_CENTS,
                    'currency' => self::DEFAULT_CURRENCY,
                    'source_type' => 'referral',
                    'source_id' => $referral->id,
                    'idempotency_key' => 'referral_bonus:' . $referral->id . ':' . $lockedDeposit->id,
                    'description' => 'Referral first-sale bonus for merchant #' . $referral->referred_user_id,
                ]);

                if ($bonusEntry) {
                    $rewardStatus->qualified_referrals_count = (int) $rewardStatus->qualified_referrals_count + 1;
                    $rewardStatus->save();
                }

                $rewardStatus = $this->recomputeAndApplyBenefits($referrerId);
            }

            $revenueShareBps = (int) $rewardStatus->revenue_share_bps;
            if ($revenueShareBps <= 0 || (int) $rewardStatus->current_level < 3) {
                return;
            }

            $grossCents = (int) ($lockedDeposit->gross_amount_cents ?: $this->toCents((float) $lockedDeposit->amount));
            if ($grossCents <= 0) {
                return;
            }

            $shareCents = (int) floor(($grossCents * $revenueShareBps) / 10000);
            if ($shareCents <= 0) {
                return;
            }

            $this->addLedgerEntry([
                'user_id' => $referrerId,
                'type' => RewardLedger::TYPE_REVENUE_SHARE,
                'amount_cents' => $shareCents,
                'currency' => self::DEFAULT_CURRENCY,
                'source_type' => 'transaction',
                'source_id' => $lockedDeposit->id,
                'idempotency_key' => 'revenue_share:' . $lockedDeposit->id . ':' . $referrerId,
                'description' => 'Revenue share from referred merchant transaction #' . $lockedDeposit->id,
            ]);
        });
    }

    public function handleRefundedDeposit(Deposit $deposit): void
    {
        if (!Schema::hasTable('referrals') || !Schema::hasTable('rewards_ledger')) {
            return;
        }

        DB::transaction(function () use ($deposit) {
            $lockedDeposit = Deposit::query()->lockForUpdate()->find($deposit->id);
            if (!$lockedDeposit || (int) $lockedDeposit->status !== Status::PAYMENT_REFUNDED) {
                return;
            }

            if (Schema::hasColumn('deposits', 'refunded_at') && !$lockedDeposit->refunded_at) {
                $lockedDeposit->refunded_at = now();
                $lockedDeposit->save();
            }

            $referral = Referral::query()
                ->where('referred_user_id', $lockedDeposit->user_id)
                ->where('first_successful_deposit_id', $lockedDeposit->id)
                ->lockForUpdate()
                ->first();

            if ($referral && $referral->status === Referral::STATUS_QUALIFIED) {
                $referral->status = Referral::STATUS_REVOKED;
                $referral->revoked_at = now();
                $referral->save();

                $status = $this->ensureUserRewardStatus((int) $referral->referrer_user_id);
                $status->qualified_referrals_count = max(0, (int) $status->qualified_referrals_count - 1);
                $status->save();

                $this->addLedgerEntry([
                    'user_id' => $referral->referrer_user_id,
                    'type' => RewardLedger::TYPE_REVERSAL,
                    'amount_cents' => -self::REFERRAL_BONUS_CENTS,
                    'currency' => self::DEFAULT_CURRENCY,
                    'source_type' => 'referral',
                    'source_id' => $referral->id,
                    'idempotency_key' => 'referral_bonus_reversal:' . $referral->id . ':' . $lockedDeposit->id,
                    'description' => 'Reversal: referral first-sale bonus revoked for merchant #' . $referral->referred_user_id,
                ]);

                $this->recomputeAndApplyBenefits((int) $referral->referrer_user_id);
            }

            $revenueEntries = RewardLedger::query()
                ->where('type', RewardLedger::TYPE_REVENUE_SHARE)
                ->where('source_type', 'transaction')
                ->where('source_id', $lockedDeposit->id)
                ->lockForUpdate()
                ->get();

            foreach ($revenueEntries as $entry) {
                $this->addLedgerEntry([
                    'user_id' => $entry->user_id,
                    'type' => RewardLedger::TYPE_REVERSAL,
                    'amount_cents' => -abs((int) $entry->amount_cents),
                    'currency' => $entry->currency,
                    'source_type' => 'transaction',
                    'source_id' => $entry->source_id,
                    'idempotency_key' => 'revenue_share_reversal:' . $entry->id,
                    'description' => 'Reversal: refunded referred transaction #' . $entry->source_id,
                ]);
            }
        });
    }

    public function revokeReferral(Referral $referral, ?string $reason = null): void
    {
        if (!Schema::hasTable('rewards_ledger')) {
            return;
        }

        DB::transaction(function () use ($referral, $reason) {
            $lockedReferral = Referral::query()->lockForUpdate()->find($referral->id);
            if (!$lockedReferral || $lockedReferral->status === Referral::STATUS_REVOKED) {
                return;
            }

            $wasQualified = $lockedReferral->status === Referral::STATUS_QUALIFIED;
            $lockedReferral->status = Referral::STATUS_REVOKED;
            $lockedReferral->revoked_at = now();

            $metadata = $lockedReferral->metadata ?? [];
            if ($reason) {
                $metadata['admin_revoke_reason'] = $reason;
                $metadata['admin_revoke_at'] = now()->toDateTimeString();
                $lockedReferral->metadata = $metadata;
            }
            $lockedReferral->save();

            if (!$wasQualified) {
                return;
            }

            $status = $this->ensureUserRewardStatus((int) $lockedReferral->referrer_user_id);
            $status->qualified_referrals_count = max(0, (int) $status->qualified_referrals_count - 1);
            $status->save();

            $this->addLedgerEntry([
                'user_id' => $lockedReferral->referrer_user_id,
                'type' => RewardLedger::TYPE_REVERSAL,
                'amount_cents' => -self::REFERRAL_BONUS_CENTS,
                'currency' => self::DEFAULT_CURRENCY,
                'source_type' => 'referral',
                'source_id' => $lockedReferral->id,
                'idempotency_key' => 'admin_referral_reversal:' . $lockedReferral->id . ':' . (int) $lockedReferral->first_successful_deposit_id,
                'description' => 'Admin reversal for referral #' . $lockedReferral->id,
            ]);

            $this->reverseRevenueShareForReferredMerchant((int) $lockedReferral->referred_user_id, (int) $lockedReferral->referrer_user_id);
            $this->recomputeAndApplyBenefits((int) $lockedReferral->referrer_user_id);
        });
    }

    public function ensureUserRewardStatus(int $userId): UserRewardStatus
    {
        if (!Schema::hasTable('user_reward_status')) {
            return new UserRewardStatus([
                'user_id' => $userId,
                'current_level' => 0,
                'qualified_referrals_count' => 0,
                'revenue_share_bps' => 0,
                'discount_active_until' => null,
            ]);
        }

        $status = UserRewardStatus::firstOrCreate(
            ['user_id' => $userId],
            [
                'current_level' => 0,
                'qualified_referrals_count' => 0,
                'revenue_share_bps' => 0,
            ]
        );

        $this->ensureRewardsWallet($userId);

        return $status;
    }

    public function recomputeAndApplyBenefits(int $userId): UserRewardStatus
    {
        if (!Schema::hasTable('user_reward_status') || !Schema::hasTable('reward_levels')) {
            return $this->ensureUserRewardStatus($userId);
        }

        return DB::transaction(function () use ($userId) {
            $status = UserRewardStatus::query()->where('user_id', $userId)->lockForUpdate()->first();
            if (!$status) {
                $status = $this->ensureUserRewardStatus($userId);
                $status = UserRewardStatus::query()->where('user_id', $userId)->lockForUpdate()->firstOrFail();
            }

            $levels = RewardLevel::query()
                ->active()
                ->orderBy('level_number')
                ->get()
                ->keyBy('level_number');

            $qualifiedCount = (int) $status->qualified_referrals_count;
            $calculatedLevel = 0;

            $nextLevel = 1;
            while ($levels->has($nextLevel)) {
                $levelConfig = $levels->get($nextLevel);
                $required = (int) ($levelConfig->required_qualified_referrals ?? PHP_INT_MAX);

                if ($qualifiedCount < $required) {
                    break;
                }

                $calculatedLevel = $nextLevel;
                $nextLevel++;
            }

            $previousLevel = (int) $status->current_level;

            if ($calculatedLevel > $previousLevel) {
                for ($levelNumber = $previousLevel + 1; $levelNumber <= $calculatedLevel; $levelNumber++) {
                    $this->applyLevelUpgrade($status, $levels->get($levelNumber));
                }
            }

            if ($calculatedLevel < $previousLevel) {
                $this->applyLevelDowngrade($status, $calculatedLevel);
            }

            $status->current_level = $calculatedLevel;

            if ($calculatedLevel < 3) {
                $status->revenue_share_bps = 0;
            }

            $status->save();

            $this->syncUserBenefitColumns($status, $levels);

            return $status->fresh();
        });
    }

    public function getSummary(User $user): array
    {
        if (!$this->isSchemaReady()) {
            $status = new UserRewardStatus([
                'user_id' => $user->id,
                'current_level' => 0,
                'qualified_referrals_count' => 0,
                'revenue_share_bps' => 0,
                'discount_active_until' => null,
            ]);

            return [
                'schema_ready' => false,
                'status' => $status,
                'referral_code' => (object) ['code' => ''],
                'referral_link' => $this->getReferralLink($user),
                'current_level' => 0,
                'qualified_referrals_count' => 0,
                'next_level_target' => null,
                'progress_percent' => 0,
                'total_earned_cents' => 0,
                'withdrawable_balance_cents' => 0,
                'revenue_share_active' => false,
                'discount_active' => false,
                'level_benefits' => [],
            ];
        }

        $status = $this->ensureUserRewardStatus($user->id);
        $status = $this->recomputeAndApplyBenefits($user->id);
        $code = $this->ensureReferralCodeForUser($user);

        $levels = RewardLevel::query()->active()->orderBy('level_number')->get();

        $currentLevel = (int) $status->current_level;
        $qualifiedCount = (int) $status->qualified_referrals_count;

        $nextLevel = $levels->firstWhere('level_number', $currentLevel + 1);
        $nextTarget = $nextLevel ? (int) $nextLevel->required_qualified_referrals : null;

        $totalEarnedCents = (int) RewardLedger::query()
            ->where('user_id', $user->id)
            ->sum('amount_cents');

        $levelBenefits = $levels->keyBy('level_number')->map(function (RewardLevel $level) {
            return [
                'name' => $level->name,
                'required_qualified_referrals' => (int) $level->required_qualified_referrals,
                'benefits' => $level->benefits ?? [],
            ];
        })->all();

        return [
            'schema_ready' => true,
            'status' => $status,
            'referral_code' => $code,
            'referral_link' => $this->getReferralLink($user),
            'current_level' => $currentLevel,
            'qualified_referrals_count' => $qualifiedCount,
            'next_level_target' => $nextTarget,
            'progress_percent' => $this->progressPercent($qualifiedCount, $currentLevel, $levels),
            'total_earned_cents' => $totalEarnedCents,
            'withdrawable_balance_cents' => $totalEarnedCents,
            'revenue_share_active' => (int) $status->revenue_share_bps > 0,
            'discount_active' => $status->discount_active_until && $status->discount_active_until->isFuture(),
            'level_benefits' => $levelBenefits,
        ];
    }

    public function ledgerQuery(User $user, ?string $type = null)
    {
        $query = RewardLedger::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id');

        if ($type && in_array($type, [
            RewardLedger::TYPE_REFERRAL_BONUS,
            RewardLedger::TYPE_REVENUE_SHARE,
            RewardLedger::TYPE_DISCOUNT_CREDIT,
            RewardLedger::TYPE_ADJUSTMENT,
            RewardLedger::TYPE_REVERSAL,
        ], true)) {
            $query->where('type', $type);
        }

        return $query;
    }

    public function createAdminAdjustment(User $user, int $amountCents, string $description): ?RewardLedger
    {
        if ($amountCents === 0) {
            return null;
        }

        return $this->addLedgerEntry([
            'user_id' => $user->id,
            'type' => RewardLedger::TYPE_ADJUSTMENT,
            'amount_cents' => $amountCents,
            'currency' => self::DEFAULT_CURRENCY,
            'source_type' => 'admin',
            'source_id' => null,
            'idempotency_key' => null,
            'description' => trim($description) !== '' ? $description : 'Admin adjustment',
        ]);
    }

    public function currentDiscountPercent(User $user): int
    {
        if (!Schema::hasTable('user_reward_status') || !Schema::hasTable('reward_levels')) {
            return 0;
        }

        $status = UserRewardStatus::query()->where('user_id', $user->id)->first();
        if (!$status || !$status->discount_active_until || $status->discount_active_until->isPast()) {
            return 0;
        }

        $levelOne = RewardLevel::query()->active()->where('level_number', 1)->first();
        return max(0, (int) data_get($levelOne?->benefits ?? [], 'discount_percent', 0));
    }

    private function applyLevelUpgrade(UserRewardStatus $status, ?RewardLevel $level): void
    {
        if (!$level) {
            return;
        }

        $benefits = $level->benefits ?? [];
        $levelNumber = (int) $level->level_number;

        if ($levelNumber === 1) {
            if (!$status->level1_achieved_at) {
                $status->level1_achieved_at = now();
            }

            $durationMonths = max(0, (int) data_get($benefits, 'discount_duration_months', 0));
            if ($durationMonths > 0) {
                $status->discount_active_until = now()->addMonths($durationMonths);
            }
        }

        if ($levelNumber === 2 && !$status->level2_achieved_at) {
            $status->level2_achieved_at = now();
        }

        if ($levelNumber === 3) {
            if (!$status->level3_achieved_at) {
                $status->level3_achieved_at = now();
            }

            $status->revenue_share_bps = max(0, (int) data_get($benefits, 'revenue_share_bps', 0));
        }
    }

    private function applyLevelDowngrade(UserRewardStatus $status, int $newLevel): void
    {
        if ($newLevel < 3) {
            $status->revenue_share_bps = 0;
        }

        if ($newLevel < 1 && $status->discount_active_until && $status->discount_active_until->isFuture()) {
            $status->discount_active_until = now();
        }
    }

    private function syncUserBenefitColumns(UserRewardStatus $status, $levels): void
    {
        $user = User::query()->find($status->user_id);
        if (!$user) {
            return;
        }

        $levelOne = $levels->get(1);
        $levelTwo = $levels->get(2);

        $discountPercent = 0;
        if ($status->discount_active_until && $status->discount_active_until->isFuture() && $levelOne) {
            $discountPercent = max(0, (int) data_get($levelOne->benefits ?? [], 'discount_percent', 0));
        }

        $prioritySupport = false;
        if ((int) $status->current_level >= 2 && $levelTwo) {
            $prioritySupport = (bool) data_get($levelTwo->benefits ?? [], 'priority_support', false);
        }

        $user->discount_percent = $discountPercent > 0 ? $discountPercent : null;
        $user->discount_active_until = $status->discount_active_until;
        $user->priority_support_enabled = $prioritySupport;
        $user->save();
    }

    private function reverseRevenueShareForReferredMerchant(int $referredUserId, int $referrerUserId): void
    {
        $depositIds = Deposit::query()
            ->where('user_id', $referredUserId)
            ->pluck('id');

        if ($depositIds->isEmpty()) {
            return;
        }

        $entries = RewardLedger::query()
            ->where('user_id', $referrerUserId)
            ->where('type', RewardLedger::TYPE_REVENUE_SHARE)
            ->where('source_type', 'transaction')
            ->whereIn('source_id', $depositIds)
            ->get();

        foreach ($entries as $entry) {
            $this->addLedgerEntry([
                'user_id' => $entry->user_id,
                'type' => RewardLedger::TYPE_REVERSAL,
                'amount_cents' => -abs((int) $entry->amount_cents),
                'currency' => $entry->currency,
                'source_type' => 'transaction',
                'source_id' => $entry->source_id,
                'idempotency_key' => 'admin_revenue_reversal:' . $entry->id,
                'description' => 'Admin reversal for referred merchant transaction #' . $entry->source_id,
            ]);
        }
    }

    private function addLedgerEntry(array $attributes): ?RewardLedger
    {
        $amountCents = (int) ($attributes['amount_cents'] ?? 0);
        if ($amountCents === 0) {
            return null;
        }

        $this->ensureRewardsWallet((int) $attributes['user_id']);

        try {
            return RewardLedger::create([
                'user_id' => (int) $attributes['user_id'],
                'type' => (string) $attributes['type'],
                'amount_cents' => $amountCents,
                'currency' => (string) ($attributes['currency'] ?? self::DEFAULT_CURRENCY),
                'source_type' => (string) $attributes['source_type'],
                'source_id' => $attributes['source_id'] ?? null,
                'idempotency_key' => $attributes['idempotency_key'] ?? null,
                'description' => $attributes['description'] ?? null,
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if ($this->isDuplicateKeyError($exception)) {
                return null;
            }

            throw $exception;
        }
    }

    private function ensureRewardsWallet(int $userId): void
    {
        if (!Schema::hasTable('rewards_wallets')) {
            return;
        }

        RewardsWallet::firstOrCreate(
            ['user_id' => $userId],
            ['currency' => self::DEFAULT_CURRENCY]
        );
    }

    private function isDuplicateKeyError(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $sqlState === '23000' || $driverCode === 1062;
    }

    private function isSelfReferral(User $referrer, User $referred): bool
    {
        if ((int) $referrer->id === (int) $referred->id) {
            return true;
        }

        if (strcasecmp((string) $referrer->email, (string) $referred->email) === 0) {
            return true;
        }

        foreach (['owner_id', 'business_owner_id', 'company_id'] as $field) {
            if (!Schema::hasColumn('users', $field)) {
                continue;
            }

            $left = $referrer->{$field};
            $right = $referred->{$field};

            if ($left !== null && $left !== '' && $right !== null && $right !== '' && (string) $left === (string) $right) {
                return true;
            }
        }

        foreach (['domain', 'website', 'business_domain'] as $field) {
            if (!Schema::hasColumn('users', $field)) {
                continue;
            }

            $left = strtolower(trim((string) ($referrer->{$field} ?? '')));
            $right = strtolower(trim((string) ($referred->{$field} ?? '')));

            if ($left !== '' && $right !== '' && $left === $right) {
                return true;
            }
        }

        if (Schema::hasTable('withdraw_settings')) {
            $referrerSetting = WithdrawSetting::query()->where('user_id', $referrer->id)->latest('id')->first();
            $referredSetting = WithdrawSetting::query()->where('user_id', $referred->id)->latest('id')->first();

            $referrerPayoutFingerprint = $this->payoutFingerprint($referrerSetting);
            $referredPayoutFingerprint = $this->payoutFingerprint($referredSetting);

            if ($referrerPayoutFingerprint && $referredPayoutFingerprint && $referrerPayoutFingerprint === $referredPayoutFingerprint) {
                return true;
            }
        }

        return false;
    }

    private function payoutFingerprint(?WithdrawSetting $setting): ?string
    {
        if (!$setting) {
            return null;
        }

        $method = (string) ($setting->withdraw_method_id ?? '');
        $data = json_encode($setting->user_data ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (trim($method) === '' || trim((string) $data) === '') {
            return null;
        }

        return hash('sha256', $method . '|' . $data);
    }

    private function sanitizeMetadata(array $metadata): array
    {
        $allowed = ['ip', 'user_agent', 'utm_source', 'utm_medium', 'utm_campaign'];
        $clean = [];

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $metadata)) {
                continue;
            }

            $value = $metadata[$key];
            if ($value === null || $value === '') {
                continue;
            }

            $clean[$key] = is_string($value) ? Str::limit(trim($value), 255, '') : $value;
        }

        return $clean;
    }

    private function progressPercent(int $qualifiedCount, int $currentLevel, $levels): int
    {
        $current = $levels->firstWhere('level_number', $currentLevel);
        $next = $levels->firstWhere('level_number', $currentLevel + 1);

        if (!$next) {
            return 100;
        }

        $currentThreshold = $current ? (int) $current->required_qualified_referrals : 0;
        $nextThreshold = (int) $next->required_qualified_referrals;
        $span = max(1, $nextThreshold - $currentThreshold);
        $progress = max(0, $qualifiedCount - $currentThreshold);

        return (int) min(100, floor(($progress / $span) * 100));
    }

    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public function isSchemaReady(): bool
    {
        return Schema::hasTable('referral_codes')
            && Schema::hasTable('referrals')
            && Schema::hasTable('rewards_wallets')
            && Schema::hasTable('rewards_ledger')
            && Schema::hasTable('reward_levels')
            && Schema::hasTable('user_reward_status');
    }
}
