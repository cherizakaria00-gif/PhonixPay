<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'manual_next_payout_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dateTime('manual_next_payout_at')->nullable()->after('plan_custom_overrides')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'manual_next_payout_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('manual_next_payout_at');
            });
        }
    }
};

