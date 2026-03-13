<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_links')) {
            return;
        }

        Schema::table('payment_links', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_links', 'is_reusable')) {
                $table->boolean('is_reusable')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_links')) {
            return;
        }

        Schema::table('payment_links', function (Blueprint $table) {
            if (Schema::hasColumn('payment_links', 'is_reusable')) {
                $table->dropColumn('is_reusable');
            }
        });
    }
};
