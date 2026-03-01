<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createReferralCodesTable();
        $this->createReferralsTable();
        $this->createRewardsWalletsTable();
        $this->createRewardsLedgerTable();
        $this->createRewardLevelsTable();
        $this->createUserRewardStatusTable();
        $this->addRewardColumnsToUsersTable();
        $this->addRewardColumnsToDepositsTable();
    }

    public function down(): void
    {
        $this->dropRewardColumnsFromDepositsTable();
        $this->dropRewardColumnsFromUsersTable();

        Schema::dropIfExists('user_reward_status');
        Schema::dropIfExists('reward_levels');
        Schema::dropIfExists('rewards_ledger');
        Schema::dropIfExists('rewards_wallets');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_codes');
    }

    private function createReferralCodesTable(): void
    {
        if (Schema::hasTable('referral_codes')) {
            return;
        }

        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('code', 32)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    private function createReferralsTable(): void
    {
        if (Schema::hasTable('referrals')) {
            return;
        }

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_user_id')->index();
            $table->unsignedBigInteger('referred_user_id')->unique();
            $table->unsignedBigInteger('referral_code_id')->nullable()->index();
            $table->unsignedBigInteger('first_successful_deposit_id')->nullable()->index();
            $table->string('status', 20)->default('registered');
            $table->dateTime('registered_at');
            $table->dateTime('qualified_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['referrer_user_id', 'status']);
            $table->index(['referred_user_id', 'status']);

            $table->foreign('referrer_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('referred_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('referral_code_id')
                ->references('id')
                ->on('referral_codes')
                ->nullOnDelete();
        });
    }

    private function createRewardsWalletsTable(): void
    {
        if (Schema::hasTable('rewards_wallets')) {
            return;
        }

        Schema::create('rewards_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('currency', 10)->default('USD');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    private function createRewardsLedgerTable(): void
    {
        if (Schema::hasTable('rewards_ledger')) {
            return;
        }

        Schema::create('rewards_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type', 40);
            $table->integer('amount_cents');
            $table->string('currency', 10)->default('USD');
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['source_type', 'source_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    private function createRewardLevelsTable(): void
    {
        if (Schema::hasTable('reward_levels')) {
            return;
        }

        Schema::create('reward_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level_number')->unique();
            $table->string('name', 80);
            $table->unsignedInteger('required_qualified_referrals');
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function createUserRewardStatusTable(): void
    {
        if (Schema::hasTable('user_reward_status')) {
            return;
        }

        Schema::create('user_reward_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedTinyInteger('current_level')->default(0);
            $table->unsignedInteger('qualified_referrals_count')->default(0);
            $table->dateTime('level1_achieved_at')->nullable();
            $table->dateTime('level2_achieved_at')->nullable();
            $table->dateTime('level3_achieved_at')->nullable();
            $table->dateTime('discount_active_until')->nullable();
            $table->unsignedInteger('revenue_share_bps')->default(0);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    private function addRewardColumnsToUsersTable(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'discount_percent')) {
                $table->unsignedTinyInteger('discount_percent')->nullable();
            }

            if (!Schema::hasColumn('users', 'discount_active_until')) {
                $table->dateTime('discount_active_until')->nullable();
            }

            if (!Schema::hasColumn('users', 'priority_support_enabled')) {
                $table->boolean('priority_support_enabled')->default(false);
            }
        });
    }

    private function addRewardColumnsToDepositsTable(): void
    {
        if (!Schema::hasTable('deposits')) {
            return;
        }

        Schema::table('deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('deposits', 'gross_amount_cents')) {
                $table->unsignedBigInteger('gross_amount_cents')->nullable();
            }

            if (!Schema::hasColumn('deposits', 'refunded_at')) {
                $table->dateTime('refunded_at')->nullable();
            }

            if (!Schema::hasColumn('deposits', 'referrer_user_id')) {
                $table->unsignedBigInteger('referrer_user_id')->nullable()->index();
            }
        });

        if (Schema::hasColumn('deposits', 'referrer_user_id')) {
            try {
                Schema::table('deposits', function (Blueprint $table) {
                    $table->foreign('referrer_user_id')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                });
            } catch (\Throwable $exception) {
                // Ignore when the foreign key already exists.
            }
        }
    }

    private function dropRewardColumnsFromUsersTable(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'priority_support_enabled')) {
                $table->dropColumn('priority_support_enabled');
            }

            if (Schema::hasColumn('users', 'discount_active_until')) {
                $table->dropColumn('discount_active_until');
            }

            if (Schema::hasColumn('users', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
        });
    }

    private function dropRewardColumnsFromDepositsTable(): void
    {
        if (!Schema::hasTable('deposits')) {
            return;
        }

        if (Schema::hasColumn('deposits', 'referrer_user_id')) {
            try {
                Schema::table('deposits', function (Blueprint $table) {
                    $table->dropForeign(['referrer_user_id']);
                });
            } catch (\Throwable $exception) {
                // Ignore when foreign key does not exist.
            }
        }

        Schema::table('deposits', function (Blueprint $table) {
            if (Schema::hasColumn('deposits', 'referrer_user_id')) {
                $table->dropColumn('referrer_user_id');
            }

            if (Schema::hasColumn('deposits', 'refunded_at')) {
                $table->dropColumn('refunded_at');
            }

            if (Schema::hasColumn('deposits', 'gross_amount_cents')) {
                $table->dropColumn('gross_amount_cents');
            }
        });
    }
};
