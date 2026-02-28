<?php

namespace Tests\Unit\Services;

use App\Models\Plan;
use App\Models\User;
use App\Services\PlanService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PlanServiceTest extends TestCase
{
    private PlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlanService();
    }

    public function test_fee_calculation_for_fixed_plans(): void
    {
        $amount = 100.0;

        $cases = [
            ['slug' => 'starter', 'name' => 'Starter', 'fee_percent' => 10.00, 'fee_fixed' => 0.50, 'expected_fee' => 10.50, 'expected_net' => 89.50],
            ['slug' => 'growth', 'name' => 'Growth', 'fee_percent' => 9.50, 'fee_fixed' => 0.50, 'expected_fee' => 10.00, 'expected_net' => 90.00],
            ['slug' => 'pro', 'name' => 'Pro', 'fee_percent' => 9.00, 'fee_fixed' => 0.40, 'expected_fee' => 9.40, 'expected_net' => 90.60],
            ['slug' => 'business', 'name' => 'Business', 'fee_percent' => 9.00, 'fee_fixed' => 0.30, 'expected_fee' => 9.30, 'expected_net' => 90.70],
        ];

        foreach ($cases as $case) {
            $user = $this->makeUserWithPlan($case);
            $result = $this->service->calculateFees($user, $amount);

            $this->assertSame($case['expected_fee'], $result['fee_amount']);
            $this->assertSame($case['expected_net'], $result['net_amount']);
        }
    }

    public function test_limit_enforcement_blocks_when_monthly_limit_reached(): void
    {
        $user = $this->makeUserWithPlan([
            'slug' => 'starter',
            'name' => 'Starter',
            'tx_limit_monthly' => 20,
            'fee_percent' => 10.00,
            'fee_fixed' => 0.50,
        ]);
        $user->monthly_tx_count = 20;
        $user->monthly_tx_count_reset_at = Carbon::now()->startOfMonth();

        $result = $this->service->canProcessTransaction($user);

        $this->assertFalse($result['allowed']);
        $this->assertSame(20, $result['used']);
        $this->assertSame(20, $result['limit']);
    }

    public function test_compute_payout_eligible_at_for_starter_adds_seven_days(): void
    {
        $user = $this->makeUserWithPlan([
            'slug' => 'starter',
            'name' => 'Starter',
            'payout_delay_days' => 7,
            'fee_percent' => 10.00,
            'fee_fixed' => 0.50,
        ]);

        $baseDate = Carbon::parse('2026-01-10 09:30:00');
        $eligibleAt = $this->service->computePayoutEligibleAt($user, $baseDate);

        $this->assertSame('2026-01-17 09:30:00', $eligibleAt->format('Y-m-d H:i:s'));
    }

    public function test_effective_plan_uses_user_overrides(): void
    {
        $user = $this->makeUserWithPlan([
            'slug' => 'growth',
            'name' => 'Growth',
            'tx_limit_monthly' => 100,
            'fee_percent' => 9.50,
            'fee_fixed' => 0.50,
            'payout_frequency' => 'twice_weekly',
            'notification_channels' => ['push', 'sms'],
        ]);
        $user->plan_custom_overrides = [
            'fee_percent' => 8.75,
            'fee_fixed' => 0.25,
            'tx_limit' => 250,
            'payout_frequency' => 'every_2_days',
            'notification_channels' => ['push'],
        ];

        $effective = $this->service->getEffectivePlan($user);

        $this->assertSame(8.75, $effective['fee_percent']);
        $this->assertSame(0.25, $effective['fee_fixed']);
        $this->assertSame(250, $effective['tx_limit_monthly']);
        $this->assertSame('every_2_days', $effective['payout_frequency']);
        $this->assertSame(['push'], $effective['notification_channels']);
    }

    private function makeUserWithPlan(array $overrides): User
    {
        $defaults = [
            'slug' => 'starter',
            'name' => 'Starter',
            'price_monthly_cents' => 0,
            'currency' => 'USD',
            'tx_limit_monthly' => 20,
            'fee_percent' => 10.00,
            'fee_fixed' => 0.50,
            'payout_frequency' => 'weekly_7d',
            'payout_delay_days' => 7,
            'support_channels' => ['email'],
            'notification_channels' => ['push'],
            'features' => ['payment_links' => false],
        ];

        $plan = new Plan(array_merge($defaults, $overrides));

        $user = new User();
        $user->monthly_tx_count = 0;
        $user->monthly_tx_count_reset_at = Carbon::now()->startOfMonth();
        $user->setRelation('plan', $plan);

        return $user;
    }
}
