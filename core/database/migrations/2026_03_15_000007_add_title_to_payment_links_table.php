<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_links') || Schema::hasColumn('payment_links', 'title')) {
            return;
        }

        Schema::table('payment_links', function (Blueprint $table) {
            $table->string('title', 255)->nullable()->after('currency');
        });

        DB::table('payment_links')
            ->whereNull('title')
            ->update([
                'title' => DB::raw('COALESCE(description, "Payment Link")'),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_links') || !Schema::hasColumn('payment_links', 'title')) {
            return;
        }

        Schema::table('payment_links', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
