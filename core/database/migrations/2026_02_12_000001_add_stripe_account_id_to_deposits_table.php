<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deposits') && !Schema::hasColumn('deposits', 'stripe_account_id')) {
            Schema::table('deposits', function (Blueprint $table) {
                $table->unsignedBigInteger('stripe_account_id')->nullable()->after('btc_wallet');
                $table->index('stripe_account_id');
                $table->foreign('stripe_account_id')
                    ->references('id')
                    ->on('stripe_accounts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deposits') && Schema::hasColumn('deposits', 'stripe_account_id')) {
            Schema::table('deposits', function (Blueprint $table) {
                $table->dropForeign(['stripe_account_id']);
                $table->dropIndex(['stripe_account_id']);
                $table->dropColumn('stripe_account_id');
            });
        }
    }
};
