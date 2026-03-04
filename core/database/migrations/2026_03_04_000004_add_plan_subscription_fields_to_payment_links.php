<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_links')) {
            return;
        }

        Schema::table('payment_links', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_links', 'link_type')) {
                $table->string('link_type', 30)->default('standard')->after('status');
                $table->index(['user_id', 'link_type'], 'payment_links_user_link_type_index');
            }

            if (!Schema::hasColumn('payment_links', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->after('user_id');
                $table->index('plan_id');
            }
        });

        if (Schema::hasColumn('payment_links', 'link_type')) {
            DB::table('payment_links')
                ->whereNull('link_type')
                ->update(['link_type' => 'standard']);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_links')) {
            return;
        }

        Schema::table('payment_links', function (Blueprint $table) {
            if (Schema::hasColumn('payment_links', 'plan_id')) {
                $table->dropIndex(['plan_id']);
                $table->dropColumn('plan_id');
            }

            if (Schema::hasColumn('payment_links', 'link_type')) {
                $table->dropIndex('payment_links_user_link_type_index');
                $table->dropColumn('link_type');
            }
        });
    }
};
