<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->unsignedInteger('price_monthly_cents')->default(0);
                $table->string('currency', 10)->default('USD');
                $table->unsignedInteger('tx_limit_monthly')->nullable();
                $table->decimal('fee_percent', 5, 2)->default(0);
                $table->decimal('fee_fixed', 10, 2)->default(0);
                $table->string('payout_frequency', 40)->default('weekly_7d');
                $table->unsignedInteger('payout_delay_days')->nullable();
                $table->json('support_channels')->nullable();
                $table->json('notification_channels')->nullable();
                $table->json('features')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('plan_change_requests')) {
            Schema::create('plan_change_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('from_plan_id')->nullable()->index();
                $table->unsignedBigInteger('to_plan_id')->index();
                $table->string('status', 30)->default('pending');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payouts')) {
            Schema::create('payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->decimal('amount_total', 28, 8)->default(0);
                $table->string('status', 30)->default('pending');
                $table->dateTime('scheduled_for');
                $table->dateTime('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'plan_id')) {
                    $table->unsignedBigInteger('plan_id')->nullable()->index();
                }
                if (!Schema::hasColumn('users', 'plan_status')) {
                    $table->string('plan_status', 30)->default('active');
                }
                if (!Schema::hasColumn('users', 'plan_started_at')) {
                    $table->dateTime('plan_started_at')->nullable();
                }
                if (!Schema::hasColumn('users', 'plan_renews_at')) {
                    $table->dateTime('plan_renews_at')->nullable();
                }
                if (!Schema::hasColumn('users', 'plan_custom_overrides')) {
                    $table->json('plan_custom_overrides')->nullable();
                }
                if (!Schema::hasColumn('users', 'monthly_tx_count')) {
                    $table->unsignedInteger('monthly_tx_count')->default(0);
                }
                if (!Schema::hasColumn('users', 'monthly_tx_count_reset_at')) {
                    $table->dateTime('monthly_tx_count_reset_at')->nullable();
                }
                if (!Schema::hasColumn('users', 'stripe_customer_id')) {
                    $table->string('stripe_customer_id')->nullable();
                }
                if (!Schema::hasColumn('users', 'stripe_subscription_id')) {
                    $table->string('stripe_subscription_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('deposits')) {
            Schema::table('deposits', function (Blueprint $table) {
                if (!Schema::hasColumn('deposits', 'fee_amount')) {
                    $table->decimal('fee_amount', 28, 8)->default(0);
                }
                if (!Schema::hasColumn('deposits', 'net_amount')) {
                    $table->decimal('net_amount', 28, 8)->default(0);
                }
                if (!Schema::hasColumn('deposits', 'payout_eligible_at')) {
                    $table->dateTime('payout_eligible_at')->nullable();
                }
                if (!Schema::hasColumn('deposits', 'payout_id')) {
                    $table->unsignedBigInteger('payout_id')->nullable()->index();
                }
            });
        }

        $this->seedDefaultPlans();
        $this->assignStarterPlanToExistingUsers();
    }

    public function down(): void
    {
        if (Schema::hasTable('deposits')) {
            Schema::table('deposits', function (Blueprint $table) {
                if (Schema::hasColumn('deposits', 'payout_id')) {
                    $table->dropColumn('payout_id');
                }
                if (Schema::hasColumn('deposits', 'payout_eligible_at')) {
                    $table->dropColumn('payout_eligible_at');
                }
                if (Schema::hasColumn('deposits', 'net_amount')) {
                    $table->dropColumn('net_amount');
                }
                if (Schema::hasColumn('deposits', 'fee_amount')) {
                    $table->dropColumn('fee_amount');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'stripe_subscription_id')) {
                    $table->dropColumn('stripe_subscription_id');
                }
                if (Schema::hasColumn('users', 'stripe_customer_id')) {
                    $table->dropColumn('stripe_customer_id');
                }
                if (Schema::hasColumn('users', 'monthly_tx_count_reset_at')) {
                    $table->dropColumn('monthly_tx_count_reset_at');
                }
                if (Schema::hasColumn('users', 'monthly_tx_count')) {
                    $table->dropColumn('monthly_tx_count');
                }
                if (Schema::hasColumn('users', 'plan_custom_overrides')) {
                    $table->dropColumn('plan_custom_overrides');
                }
                if (Schema::hasColumn('users', 'plan_renews_at')) {
                    $table->dropColumn('plan_renews_at');
                }
                if (Schema::hasColumn('users', 'plan_started_at')) {
                    $table->dropColumn('plan_started_at');
                }
                if (Schema::hasColumn('users', 'plan_status')) {
                    $table->dropColumn('plan_status');
                }
                if (Schema::hasColumn('users', 'plan_id')) {
                    $table->dropColumn('plan_id');
                }
            });
        }

        Schema::dropIfExists('payouts');
        Schema::dropIfExists('plan_change_requests');
        Schema::dropIfExists('plans');
    }

    private function seedDefaultPlans(): void
    {
        if (!Schema::hasTable('plans')) {
            return;
        }

        $now = Carbon::now();

        $defaults = [
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
                'support_channels' => json_encode(['email']),
                'notification_channels' => json_encode(['push']),
                'features' => json_encode(['payment_links' => false]),
                'is_active' => 1,
                'is_default' => 1,
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
                'support_channels' => json_encode(['email']),
                'notification_channels' => json_encode(['push', 'sms']),
                'features' => json_encode(['payment_links' => true]),
                'is_active' => 1,
                'is_default' => 1,
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
                'support_channels' => json_encode(['email', 'whatsapp']),
                'notification_channels' => json_encode(['push', 'sms']),
                'features' => json_encode(['payment_links' => true]),
                'is_active' => 1,
                'is_default' => 1,
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
                'support_channels' => json_encode(['email', 'whatsapp']),
                'notification_channels' => json_encode(['push', 'sms']),
                'features' => json_encode(['payment_links' => true]),
                'is_active' => 1,
                'is_default' => 1,
                'sort_order' => 4,
            ],
        ];

        foreach ($defaults as $plan) {
            $existing = DB::table('plans')->where('slug', $plan['slug'])->first();

            if ($existing) {
                DB::table('plans')
                    ->where('id', $existing->id)
                    ->update(array_merge($plan, [
                        'updated_at' => $now,
                    ]));
                continue;
            }

            DB::table('plans')->insert(array_merge($plan, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function assignStarterPlanToExistingUsers(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('plans') || !Schema::hasColumn('users', 'plan_id')) {
            return;
        }

        $starterPlan = DB::table('plans')->where('slug', 'starter')->first();
        if (!$starterPlan) {
            return;
        }

        DB::table('users')
            ->whereNull('plan_id')
            ->update([
                'plan_id' => $starterPlan->id,
                'plan_status' => 'active',
                'plan_started_at' => now(),
                'monthly_tx_count' => 0,
                'monthly_tx_count_reset_at' => now()->startOfMonth(),
                'updated_at' => now(),
            ]);
    }
};
