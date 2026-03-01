<?php

namespace Tests\Feature\Rewards;

use App\Constants\Status;
use App\Models\Deposit;
use App\Models\Referral;
use App\Models\RewardLedger;
use App\Models\RewardLevel;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RewardProgramTest extends TestCase
{
    private RewardService $service;
    private int $userSequence = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase();
        $this->seedRewardLevels();

        $this->service = app(RewardService::class);
    }

    public function test_referral_qualification_triggers_bonus_only_once(): void
    {
        $referrer = $this->createUser();
        $referred = $this->createUser();

        $code = $this->service->ensureReferralCodeForUser($referrer);
        $this->service->registerReferral($referred, $code->code, ['ip' => '127.0.0.1']);

        $deposit = $this->createDeposit($referred->id, 100.00, Status::PAYMENT_SUCCESS);

        $this->service->handleSuccessfulDeposit($deposit);
        $this->service->handleSuccessfulDeposit($deposit->fresh());

        $referral = Referral::where('referred_user_id', $referred->id)->firstOrFail();
        $rewardStatus = $this->service->ensureUserRewardStatus($referrer->id)->fresh();

        $this->assertSame(Referral::STATUS_QUALIFIED, $referral->status);
        $this->assertSame(1, (int) $rewardStatus->qualified_referrals_count);

        $bonusEntries = RewardLedger::query()
            ->where('user_id', $referrer->id)
            ->where('type', RewardLedger::TYPE_REFERRAL_BONUS)
            ->where('source_id', $referral->id)
            ->get();

        $this->assertCount(1, $bonusEntries);
        $this->assertSame(500, (int) $bonusEntries->first()->amount_cents);
    }

    public function test_level_upgrades_are_sequential(): void
    {
        $merchant = $this->createUser();
        $status = $this->service->ensureUserRewardStatus($merchant->id);

        $status->qualified_referrals_count = 20;
        $status->current_level = 0;
        $status->save();

        $status = $this->service->recomputeAndApplyBenefits($merchant->id);

        $this->assertSame(2, (int) $status->current_level);
        $this->assertNotNull($status->level1_achieved_at);
        $this->assertNotNull($status->level2_achieved_at);
        $this->assertNull($status->level3_achieved_at);
    }

    public function test_revenue_share_only_after_level_three(): void
    {
        $referrer = $this->createUser();
        $referred = $this->createUser();

        $referral = Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referred->id,
            'status' => Referral::STATUS_QUALIFIED,
            'registered_at' => now(),
            'qualified_at' => now(),
        ]);

        $status = $this->service->ensureUserRewardStatus($referrer->id);
        $status->current_level = 2;
        $status->qualified_referrals_count = 20;
        $status->revenue_share_bps = 0;
        $status->save();

        $beforeLevelThreeDeposit = $this->createDeposit($referred->id, 100.00, Status::PAYMENT_SUCCESS);
        $this->service->handleSuccessfulDeposit($beforeLevelThreeDeposit);

        $this->assertSame(0, RewardLedger::query()
            ->where('type', RewardLedger::TYPE_REVENUE_SHARE)
            ->where('source_id', $beforeLevelThreeDeposit->id)
            ->count());

        $status->current_level = 3;
        $status->revenue_share_bps = 50;
        $status->level3_achieved_at = now();
        $status->save();

        $afterLevelThreeDeposit = $this->createDeposit($referred->id, 100.00, Status::PAYMENT_SUCCESS);
        $this->service->handleSuccessfulDeposit($afterLevelThreeDeposit);

        $entry = RewardLedger::query()
            ->where('type', RewardLedger::TYPE_REVENUE_SHARE)
            ->where('source_id', $afterLevelThreeDeposit->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame(50, (int) $entry->amount_cents);
        $this->assertSame($referrer->id, (int) $entry->user_id);

        $this->assertSame($referral->id, (int) Referral::where('referred_user_id', $referred->id)->value('id'));
    }

    public function test_refund_reversals_are_correct_and_idempotent(): void
    {
        $referrer = $this->createUser();
        $referred = $this->createUser();

        $deposit = $this->createDeposit($referred->id, 80.00, Status::PAYMENT_SUCCESS);

        $referral = Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referred->id,
            'status' => Referral::STATUS_QUALIFIED,
            'registered_at' => now(),
            'qualified_at' => now(),
            'first_successful_deposit_id' => $deposit->id,
        ]);

        $status = $this->service->ensureUserRewardStatus($referrer->id);
        $status->current_level = 1;
        $status->qualified_referrals_count = 1;
        $status->save();

        RewardLedger::create([
            'user_id' => $referrer->id,
            'type' => RewardLedger::TYPE_REFERRAL_BONUS,
            'amount_cents' => 500,
            'currency' => 'USD',
            'source_type' => 'referral',
            'source_id' => $referral->id,
            'idempotency_key' => 'referral_bonus:' . $referral->id . ':' . $deposit->id,
            'description' => 'Initial bonus',
            'created_at' => now(),
        ]);

        $revenue = RewardLedger::create([
            'user_id' => $referrer->id,
            'type' => RewardLedger::TYPE_REVENUE_SHARE,
            'amount_cents' => 40,
            'currency' => 'USD',
            'source_type' => 'transaction',
            'source_id' => $deposit->id,
            'idempotency_key' => 'revenue_share:' . $deposit->id . ':' . $referrer->id,
            'description' => 'Revenue share',
            'created_at' => now(),
        ]);

        $deposit->status = Status::PAYMENT_REFUNDED;
        $deposit->save();

        $this->service->handleRefundedDeposit($deposit->fresh());
        $this->service->handleRefundedDeposit($deposit->fresh());

        $this->assertSame(Referral::STATUS_REVOKED, $referral->fresh()->status);
        $this->assertSame(0, (int) $status->fresh()->qualified_referrals_count);

        $bonusReversalKey = 'referral_bonus_reversal:' . $referral->id . ':' . $deposit->id;
        $this->assertSame(1, RewardLedger::query()->where('idempotency_key', $bonusReversalKey)->count());
        $this->assertSame(-500, (int) RewardLedger::query()->where('idempotency_key', $bonusReversalKey)->value('amount_cents'));

        $revenueReversalKey = 'revenue_share_reversal:' . $revenue->id;
        $this->assertSame(1, RewardLedger::query()->where('idempotency_key', $revenueReversalKey)->count());
        $this->assertSame(-40, (int) RewardLedger::query()->where('idempotency_key', $revenueReversalKey)->value('amount_cents'));
    }

    private function prepareDatabase(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'rewards_ledger',
            'rewards_wallets',
            'referrals',
            'referral_codes',
            'user_reward_status',
            'reward_levels',
            'deposits',
            'withdraw_settings',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->unsignedInteger('ref_by')->default(0);
            $table->decimal('balance', 28, 8)->default(0);
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->dateTime('discount_active_until')->nullable();
            $table->boolean('priority_support_enabled')->default(false);
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('withdraw_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('withdraw_method_id')->default(0);
            $table->text('user_data')->nullable();
            $table->timestamps();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 28, 8)->default(0);
            $table->unsignedBigInteger('gross_amount_cents')->nullable();
            $table->unsignedBigInteger('referrer_user_id')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->dateTime('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code', 32)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_user_id');
            $table->unsignedBigInteger('referred_user_id')->unique();
            $table->unsignedBigInteger('referral_code_id')->nullable();
            $table->unsignedBigInteger('first_successful_deposit_id')->nullable();
            $table->string('status', 20)->default('registered');
            $table->dateTime('registered_at');
            $table->dateTime('qualified_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('rewards_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('currency', 10)->default('USD');
            $table->timestamps();
        });

        Schema::create('rewards_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 40);
            $table->integer('amount_cents');
            $table->string('currency', 10)->default('USD');
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('reward_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level_number')->unique();
            $table->string('name', 80);
            $table->unsignedInteger('required_qualified_referrals');
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

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
        });
    }

    private function seedRewardLevels(): void
    {
        RewardLevel::create([
            'level_number' => 1,
            'name' => 'Level 1',
            'required_qualified_referrals' => 10,
            'benefits' => ['discount_percent' => 50, 'discount_duration_months' => 3],
            'is_active' => true,
        ]);

        RewardLevel::create([
            'level_number' => 2,
            'name' => 'Level 2',
            'required_qualified_referrals' => 20,
            'benefits' => ['badge' => true, 'priority_support' => true],
            'is_active' => true,
        ]);

        RewardLevel::create([
            'level_number' => 3,
            'name' => 'Level 3',
            'required_qualified_referrals' => 50,
            'benefits' => ['revenue_share_bps' => 50],
            'is_active' => true,
        ]);
    }

    private function createUser(): User
    {
        $number = $this->userSequence++;

        $user = new User();
        $user->firstname = 'User' . $number;
        $user->lastname = 'Test';
        $user->username = 'user' . $number;
        $user->email = 'user' . $number . '@example.test';
        $user->password = bcrypt('password');
        $user->balance = 1000;
        $user->save();

        return $user->fresh();
    }

    private function createDeposit(int $userId, float $amount, int $status): Deposit
    {
        $deposit = new Deposit();
        $deposit->user_id = $userId;
        $deposit->amount = $amount;
        $deposit->status = $status;
        $deposit->gross_amount_cents = (int) round($amount * 100);
        $deposit->save();

        return $deposit->fresh();
    }
}
