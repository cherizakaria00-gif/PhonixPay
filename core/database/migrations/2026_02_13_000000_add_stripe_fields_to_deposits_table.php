<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deposits')) {
            Schema::table('deposits', function (Blueprint $table) {
                if (!Schema::hasColumn('deposits', 'stripe_charge_id')) {
                    $table->string('stripe_charge_id')->nullable()->after('stripe_account_id');
                }
                if (!Schema::hasColumn('deposits', 'stripe_session_id')) {
                    $table->string('stripe_session_id')->nullable()->after('stripe_charge_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deposits')) {
            Schema::table('deposits', function (Blueprint $table) {
                if (Schema::hasColumn('deposits', 'stripe_session_id')) {
                    $table->dropColumn('stripe_session_id');
                }
                if (Schema::hasColumn('deposits', 'stripe_charge_id')) {
                    $table->dropColumn('stripe_charge_id');
                }
            });
        }
    }
};
