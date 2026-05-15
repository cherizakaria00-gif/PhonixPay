<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'setup_fee_amount_usdt')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` ALTER `setup_fee_amount_usdt` SET DEFAULT 1500.00");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "users" ALTER COLUMN "setup_fee_amount_usdt" SET DEFAULT 1500.00');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'setup_fee_amount_usdt')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` ALTER `setup_fee_amount_usdt` SET DEFAULT 1000.00");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "users" ALTER COLUMN "setup_fee_amount_usdt" SET DEFAULT 1000.00');
        }
    }
};
