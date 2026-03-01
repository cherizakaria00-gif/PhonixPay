<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\NotificationLog;
use App\Models\Plan;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class PlanService
{
    public function getEffectivePlan(User $user): array
    {
        $plan = $user->plan;

        if (!$plan && Schema::hasTable('plans')) {
            $plan = Plan::starter();
        }

        if (!$plan) {
            return $this->defaultStarterPlan();
        }

        $effective = [
            'id' => $plan->id,
            'slug' => $plan->slug,
            'name' => $plan->name,
            'price_monthly_cents' => (int) $plan->price_monthly_cents,
            'currency' => $plan->currency,
            'tx_limit_monthly' => $plan->tx_limit_monthly,
            'fee_percent' => (float) $plan->fee_percent,
            'fee_fixed' => (float) $plan->fee_fixed,
            'payout_frequency' => $plan->payout_frequency,
            'payout_delay_days' => $plan->payout_delay_days,
            'support_channels' => $plan->support_channels ?? ['email'],
            'notification_channels' => $plan->notification_channels ?? ['push'],
            'features' => $plan->features ?? [],
        ];

        $overrides = $user->plan_custom_overrides;
        if (is_array($overrides) && !empty($overrides)) {
            $effective = $this->applyOverrides($effective, $overrides);
        }

        return $effective;
    }

    public function canProcessTransaction(User $user): array
    {
        $this->resetMonthlyUsageIfNeeded($user);

        $plan = $this->getEffectivePlan($user);
        $used = $this->getMonthlyUsage($user);
        $limit = $plan['tx_limit_monthly'];

        if ($limit !== null && $used >= (int) $limit) {
            $upgradeUrl = null;
            if (function_exists('app')) {
                try {
                    if (app()->bound('router') && Route::has('user.plan.billing')) {
                        $upgradeUrl = route('user.plan.billing');
                    }
                } catch (\Throwable $exception) {
                    $upgradeUrl = null;
                }
            }
            $message = 'You reached your monthly transaction limit for the current plan. Please upgrade your plan to continue.';
            if ($upgradeUrl) {
                $message .= ' Upgrade here: ' . $upgradeUrl;
            }

            return [
                'allowed' => false,
                'used' => $used,
                'limit' => (int) $limit,
                'upgrade_url' => $upgradeUrl,
                'message' => $message,
            ];
        }

        return [
            'allowed' => true,
            'used' => $used,
            'limit' => $limit,
            'upgrade_url' => null,
            'message' => null,
        ];
    }

    public function calculateFees(User $user, float $amount): array
    {
        $plan = $this->getEffectivePlan($user);

        $fee = round(($amount * ((float) $plan['fee_percent'] / 100)) + (float) $plan['fee_fixed'], 8);
        $net = round(max(0, $amount - $fee), 8);

        return [
            'fee_percent' => (float) $plan['fee_percent'],
            'fee_fixed' => (float) $plan['fee_fixed'],
            'fee_amount' => $fee,
            'net_amount' => $net,
        ];
    }

    public function calculateSubscriptionCharge(User $user, int $basePriceCents): array
    {
        $basePriceCents = max(0, $basePriceCents);

        $discountPercent = 0;

        if (Schema::hasTable('user_reward_status')) {
            $discountPercent = app(RewardService::class)->currentDiscountPercent($user);
        }

        if (
            Schema::hasColumn('users', 'discount_percent')
            && Schema::hasColumn('users', 'discount_active_until')
            && $user->discount_active_until
            && Carbon::parse($user->discount_active_until)->isFuture()
        ) {
            $discountPercent = max($discountPercent, (int) $user->discount_percent);
        }

        $discountPercent = max(0, min(100, $discountPercent));
        $discountAmountCents = (int) floor(($basePriceCents * $discountPercent) / 100);
        $finalPriceCents = max(0, $basePriceCents - $discountAmountCents);

        return [
            'base_cents' => $basePriceCents,
            'discount_percent' => $discountPercent,
            'discount_amount_cents' => $discountAmountCents,
            'final_cents' => $finalPriceCents,
        ];
    }

    public function computePayoutEligibleAt(User $user, $transactionDate = null): Carbon
    {
        $plan = $this->getEffectivePlan($user);
        $base = $transactionDate ? Carbon::parse($transactionDate) : Carbon::now();
        $delayDays = (int) ($plan['payout_delay_days'] ?? 0);

        return $base->copy()->addDays(max(0, $delayDays));
    }

    public function getNotificationChannels(User $user): array
    {
        $plan = $this->getEffectivePlan($user);
        $channels = $plan['notification_channels'] ?? ['push'];

        $allowed = ['email', 'sms', 'push'];
        $channels = array_values(array_intersect($allowed, (array) $channels));

        return $channels ?: ['push'];
    }

    public function isFeatureEnabled(User $user, string $feature): bool
    {
        $plan = $this->getEffectivePlan($user);

        return (bool) Arr::get($plan, 'features.' . $feature, false);
    }

    public function resetMonthlyUsageIfNeeded(User $user): void
    {
        $now = Carbon::now()->utc();
        $resetAt = $user->monthly_tx_count_reset_at ? Carbon::parse($user->monthly_tx_count_reset_at)->utc() : null;

        if (!$resetAt || $resetAt->format('Y-m') !== $now->format('Y-m')) {
            $user->monthly_tx_count = 0;
            $user->monthly_tx_count_reset_at = $now->copy()->startOfMonth();
            $user->save();
        }
    }

    public function incrementMonthlyUsage(User $user): void
    {
        $this->resetMonthlyUsageIfNeeded($user);
        $user->monthly_tx_count = (int) $user->monthly_tx_count + 1;
        $user->save();
    }

    public function getMonthlyUsage(User $user): int
    {
        $this->resetMonthlyUsageIfNeeded($user);

        if ((int) $user->monthly_tx_count > 0) {
            return (int) $user->monthly_tx_count;
        }

        $monthStart = Carbon::now()->utc()->startOfMonth();
        $monthEnd = Carbon::now()->utc()->endOfMonth();
        $count = Deposit::where('user_id', $user->id)
            ->successful()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        $user->monthly_tx_count = $count;
        $user->save();

        return (int) $count;
    }

    public function usageSummary(User $user): array
    {
        $plan = $this->getEffectivePlan($user);
        $used = $this->getMonthlyUsage($user);
        $limit = $plan['tx_limit_monthly'];
        $percent = $limit ? min(100, (int) round(($used / max(1, $limit)) * 100)) : 0;

        return [
            'used' => $used,
            'limit' => $limit,
            'percent' => $percent,
            'unlimited' => $limit === null,
            'payout_frequency_label' => $this->payoutFrequencyLabel($plan['payout_frequency'] ?? 'weekly_7d'),
        ];
    }

    public function assignPlan(User $user, Plan $plan, bool $pending = false): void
    {
        $user->plan_id = $plan->id;
        $user->plan_status = $pending ? 'pending' : 'active';

        if (!$user->monthly_tx_count_reset_at) {
            $user->monthly_tx_count_reset_at = Carbon::now()->utc()->startOfMonth();
        }

        if (!$pending) {
            $user->plan_started_at = Carbon::now();
            $user->plan_renews_at = $plan->price_monthly_cents > 0 ? Carbon::now()->addMonth() : null;
        }

        $user->save();
    }

    public function processMonthlyRenewals(?Carbon $now = null): void
    {
        if (!Schema::hasTable('plans') || !Schema::hasColumn('users', 'plan_renews_at')) {
            return;
        }

        $now = ($now ?: Carbon::now())->utc();

        User::query()
            ->whereNotNull('plan_id')
            ->whereNotNull('plan_renews_at')
            ->where(function ($query) {
                $query->whereNull('plan_status')->orWhere('plan_status', 'active');
            })
            ->where('plan_renews_at', '<=', $now)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($now) {
                foreach ($users as $dueUser) {
                    DB::transaction(function () use ($dueUser, $now) {
                        $merchant = User::lockForUpdate()->with('plan')->find($dueUser->id);
                        if (!$merchant || !$merchant->plan_id) {
                            return;
                        }

                        if (!$merchant->plan_renews_at || Carbon::parse($merchant->plan_renews_at)->utc()->gt($now)) {
                            return;
                        }

                        $plan = $merchant->plan;
                        if (!$plan || !$plan->is_active) {
                            return;
                        }

                        $charge = $this->calculateSubscriptionCharge($merchant, (int) $plan->price_monthly_cents);
                        $priceAmount = round((int) $charge['final_cents'] / 100, 2);

                        if ($priceAmount <= 0) {
                            if ((int) $plan->price_monthly_cents <= 0) {
                                $merchant->plan_status = 'active';
                                $merchant->plan_started_at = $now;
                                $merchant->plan_renews_at = null;
                                $merchant->save();
                            } else {
                                $this->assignPlan($merchant, $plan, false);
                            }
                            return;
                        }

                        if ((float) $merchant->balance < $priceAmount) {
                            $starterPlan = Plan::starter();

                            if ($starterPlan && (int) $starterPlan->id !== (int) $merchant->plan_id) {
                                $this->assignPlan($merchant, $starterPlan, false);
                                $merchant->plan_custom_overrides = null;
                                $merchant->save();
                            } else {
                                $merchant->plan_status = 'canceled';
                                $merchant->plan_renews_at = null;
                                $merchant->save();
                            }

                            return;
                        }

                        $merchant->balance = round((float) $merchant->balance - $priceAmount, 8);
                        $merchant->save();

                        $transaction = new Transaction();
                        $transaction->user_id = $merchant->id;
                        $transaction->amount = $priceAmount;
                        $transaction->post_balance = $merchant->balance;
                        $transaction->charge = 0;
                        $transaction->trx_type = '-';
                        $transaction->details = $charge['discount_percent'] > 0
                            ? 'Monthly subscription renewal for ' . $plan->name . ' plan with ' . $charge['discount_percent'] . '% rewards discount'
                            : 'Monthly subscription renewal for ' . $plan->name . ' plan';
                        $transaction->trx = getTrx();
                        $transaction->remark = 'plan_renewal';
                        $transaction->save();

                        $this->assignPlan($merchant, $plan, false);
                    });
                }
            });
    }

    public function sendUpcomingRenewalNotifications(?Carbon $now = null): void
    {
        if (!Schema::hasTable('plans') || !Schema::hasColumn('users', 'plan_renews_at')) {
            return;
        }

        $now = ($now ?: Carbon::now())->utc();
        $maxDate = $now->copy()->addDays(3);

        User::query()
            ->with('plan')
            ->whereNotNull('plan_id')
            ->whereNotNull('plan_renews_at')
            ->where(function ($query) {
                $query->whereNull('plan_status')->orWhere('plan_status', 'active');
            })
            ->whereBetween('plan_renews_at', [$now, $maxDate])
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($now) {
                foreach ($users as $user) {
                    if (!$user->plan || (int) $user->plan->price_monthly_cents <= 0) {
                        continue;
                    }

                    $renewAt = Carbon::parse($user->plan_renews_at)->utc();
                    $hoursLeft = max(0, $now->diffInHours($renewAt, false));
                    $daysLeft = max(0, (int) ceil($hoursLeft / 24));
                    $subject = 'Plan renewal reminder [' . $renewAt->toDateString() . ']';

                    $alreadySent = NotificationLog::query()
                        ->where('user_id', $user->id)
                        ->where('notification_type', 'email')
                        ->where('subject', $subject)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $daysText = $daysLeft <= 1 ? 'in less than 24 hours' : 'in ' . $daysLeft . ' days';
                    $renewDateText = $renewAt->format('M d, Y H:i') . ' UTC';
                    $amount = number_format(((int) $user->plan->price_monthly_cents / 100), 2);

                    notify($user, 'DEFAULT', [
                        'subject' => $subject,
                        'message' => 'Your ' . $user->plan->name . ' plan will renew ' . $daysText . ' (' . $renewDateText . '). Subscription amount: $' . $amount . '. Please keep enough balance to avoid downgrade to Starter.',
                    ], ['email', 'push']);
                }
            });
    }

    public function sendPlanUpgradeNotification(User $user, string $toPlanName, ?string $fromPlanName = null, ?Carbon $renewsAt = null): void
    {
        $subject = 'Plan upgraded successfully';
        $message = 'Your subscription is now active on the ' . $toPlanName . ' plan.';

        if ($fromPlanName && strtolower($fromPlanName) !== strtolower($toPlanName)) {
            $message .= ' Previous plan: ' . $fromPlanName . '.';
        }

        if ($renewsAt) {
            $message .= ' Next renewal: ' . $renewsAt->utc()->format('M d, Y H:i') . ' UTC.';
        }

        notify($user, 'DEFAULT', [
            'subject' => $subject,
            'message' => $message,
        ], ['email']);
    }

    public function isPayoutRunDue(User $user, ?Carbon $now = null): bool
    {
        $now = $now ?: Carbon::now();
        $plan = $this->getEffectivePlan($user);
        $frequency = $plan['payout_frequency'] ?? 'weekly_7d';

        $lastPayout = Payout::where('user_id', $user->id)
            ->latest('scheduled_for')
            ->first();

        if ($frequency === 'twice_weekly') {
            $isRunDay = in_array($now->dayOfWeekIso, [2, 5], true);
            if (!$isRunDay) {
                return false;
            }

            if (!$lastPayout) {
                return true;
            }

            return !$lastPayout->scheduled_for->isSameDay($now);
        }

        if ($frequency === 'every_2_days') {
            if (!$lastPayout) {
                return true;
            }

            return $lastPayout->scheduled_for->diffInDays($now) >= 2;
        }

        if (!$lastPayout) {
            return true;
        }

        return $lastPayout->scheduled_for->diffInDays($now) >= 7;
    }

    public function payoutFrequencyLabel(string $frequency): string
    {
        return match ($frequency) {
            'twice_weekly' => '2x per week (Tue/Fri)',
            'every_2_days' => 'Every 2 days',
            default => 'Every 7 days',
        };
    }

    private function applyOverrides(array $basePlan, array $overrides): array
    {
        $map = [
            'fee_percent' => 'fee_percent',
            'fee_fixed' => 'fee_fixed',
            'tx_limit' => 'tx_limit_monthly',
            'tx_limit_monthly' => 'tx_limit_monthly',
            'payout_frequency' => 'payout_frequency',
            'payout_delay_days' => 'payout_delay_days',
            'notification_channels' => 'notification_channels',
            'support_channels' => 'support_channels',
            'features' => 'features',
        ];

        foreach ($map as $source => $target) {
            if (array_key_exists($source, $overrides) && $overrides[$source] !== null && $overrides[$source] !== '') {
                $value = $overrides[$source];

                if (in_array($source, ['tx_limit', 'tx_limit_monthly'], true) && $value === 'unlimited') {
                    $value = null;
                }

                $basePlan[$target] = $value;
            }
        }

        return $basePlan;
    }

    private function defaultStarterPlan(): array
    {
        return [
            'id' => null,
            'slug' => 'starter',
            'name' => 'Starter',
            'price_monthly_cents' => 0,
            'currency' => 'USD',
            'tx_limit_monthly' => 20,
            'fee_percent' => 10.0,
            'fee_fixed' => 0.5,
            'payout_frequency' => 'weekly_7d',
            'payout_delay_days' => 7,
            'support_channels' => ['email'],
            'notification_channels' => ['push'],
            'features' => ['payment_links' => false],
        ];
    }
}
