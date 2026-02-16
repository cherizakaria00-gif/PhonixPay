<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add webhook fields to stripe_accounts
 * Run with: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stripe_accounts') && !Schema::hasColumn('stripe_accounts', 'webhook_secret')) {
            Schema::table('stripe_accounts', function (Blueprint $table) {
                $table->string('webhook_secret')->nullable()->after('secret_key');
                $table->string('webhook_id')->nullable()->after('webhook_secret');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stripe_accounts')) {
            Schema::table('stripe_accounts', function (Blueprint $table) {
                if (Schema::hasColumn('stripe_accounts', 'webhook_secret')) {
                    $table->dropColumn('webhook_secret');
                }
                if (Schema::hasColumn('stripe_accounts', 'webhook_id')) {
                    $table->dropColumn('webhook_id');
                }
            });
        }
    }
};
