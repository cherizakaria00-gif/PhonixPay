<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'website_url')) {
                $table->string('website_url', 255)->nullable();
            }

            if (!Schema::hasColumn('users', 'website_domain')) {
                $table->string('website_domain', 191)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'website_domain')) {
                $table->dropColumn('website_domain');
            }

            if (Schema::hasColumn('users', 'website_url')) {
                $table->dropColumn('website_url');
            }
        });
    }
};
