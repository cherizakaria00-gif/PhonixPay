<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\Plan;
use App\Models\Payout;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
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
