<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('withdrawals') && !Schema::hasColumn('withdrawals', 'payout_date')) {
            Schema::table('withdrawals', function (Blueprint $table) {
                $table->date('payout_date')->nullable()->after('after_charge');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('withdrawals') && Schema::hasColumn('withdrawals', 'payout_date')) {
            Schema::table('withdrawals', function (Blueprint $table) {
                $table->dropColumn('payout_date');
            });
        }
    }
};
