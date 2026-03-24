<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('virtual_card_settings')) {
            return;
        }

        Schema::table('virtual_card_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_card_settings', 'mode')) {
                $table->string('mode', 16)->default('sandbox')->after('provider');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'webhook_url')) {
                $table->string('webhook_url')->nullable()->after('base_url');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'card_details_text')) {
                $table->text('card_details_text')->nullable()->after('webhook_secret');
            }

            if (!Schema::hasColumn('virtual_card_settings', 'fixed_charge')) {
                $table->decimal('fixed_charge', 16, 4)->default(0)->after('default_currency');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'percent_charge')) {
                $table->decimal('percent_charge', 8, 4)->default(0)->after('fixed_charge');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'min_amount')) {
                $table->decimal('min_amount', 16, 4)->default(0)->after('percent_charge');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'max_amount')) {
                $table->decimal('max_amount', 16, 4)->default(0)->after('min_amount');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'daily_limit')) {
                $table->decimal('daily_limit', 16, 4)->default(0)->after('max_amount');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'monthly_limit')) {
                $table->decimal('monthly_limit', 16, 4)->default(0)->after('daily_limit');
            }

            if (!Schema::hasColumn('virtual_card_settings', 'reload_fixed_charge')) {
                $table->decimal('reload_fixed_charge', 16, 4)->default(0)->after('monthly_limit');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'reload_percent_charge')) {
                $table->decimal('reload_percent_charge', 8, 4)->default(0)->after('reload_fixed_charge');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'reload_min_amount')) {
                $table->decimal('reload_min_amount', 16, 4)->default(0)->after('reload_percent_charge');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'reload_max_amount')) {
                $table->decimal('reload_max_amount', 16, 4)->default(0)->after('reload_min_amount');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'reload_daily_limit')) {
                $table->decimal('reload_daily_limit', 16, 4)->default(0)->after('reload_max_amount');
            }
            if (!Schema::hasColumn('virtual_card_settings', 'reload_monthly_limit')) {
                $table->decimal('reload_monthly_limit', 16, 4)->default(0)->after('reload_daily_limit');
            }
        });
    }

    public function down(): void
    {
    }
};
