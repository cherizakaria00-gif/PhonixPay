<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        if (Schema::hasColumn('general_settings', 'didit_config')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            $table->text('didit_config')->nullable()->after('firebase_config');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        if (!Schema::hasColumn('general_settings', 'didit_config')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn('didit_config');
        });
    }
};

