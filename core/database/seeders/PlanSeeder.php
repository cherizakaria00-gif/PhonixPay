<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
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
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'growth',
                'name' => 'Growth',
                'price_monthly_cents' => 2900,
                'currency' => 'USD',
                'tx_limit_monthly' => 100,
                'fee_percent' => 9.50,
                'fee_fixed' => 0.50,
                'payout_frequency' => 'twice_weekly',
                'payout_delay_days' => null,
                'support_channels' => ['email'],
                'notification_channels' => ['push', 'sms'],
                'features' => ['payment_links' => true],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'price_monthly_cents' => 5900,
                'currency' => 'USD',
                'tx_limit_monthly' => 1000,
                'fee_percent' => 9.00,
                'fee_fixed' => 0.40,
                'payout_frequency' => 'twice_weekly',
                'payout_delay_days' => null,
                'support_channels' => ['email', 'whatsapp'],
                'notification_channels' => ['push', 'sms'],
                'features' => ['payment_links' => true],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'business',
                'name' => 'Business',
                'price_monthly_cents' => 9900,
                'currency' => 'USD',
                'tx_limit_monthly' => null,
                'fee_percent' => 9.00,
                'fee_fixed' => 0.30,
                'payout_frequency' => 'every_2_days',
                'payout_delay_days' => null,
                'support_channels' => ['email', 'whatsapp'],
                'notification_channels' => ['push', 'sms'],
                'features' => ['payment_links' => true],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $item) {
            Plan::updateOrCreate(['slug' => $item['slug']], $item);
        }

        $starter = Plan::where('slug', 'starter')->first();
        if ($starter) {
            User::whereNull('plan_id')->update([
                'plan_id' => $starter->id,
                'plan_status' => 'active',
                'plan_started_at' => now(),
                'monthly_tx_count' => 0,
                'monthly_tx_count_reset_at' => now()->startOfMonth(),
            ]);
        }
    }
}
