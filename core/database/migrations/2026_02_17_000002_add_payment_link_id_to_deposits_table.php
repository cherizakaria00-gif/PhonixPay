<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deposits') && !Schema::hasColumn('deposits', 'payment_link_id')) {
            Schema::table('deposits', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_link_id')->nullable()->after('stripe_session_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deposits') && Schema::hasColumn('deposits', 'payment_link_id')) {
            Schema::table('deposits', function (Blueprint $table) {
                $table->dropColumn('payment_link_id');
            });
        }
    }
};
