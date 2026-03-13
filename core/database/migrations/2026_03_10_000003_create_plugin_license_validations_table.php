<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plugin_license_validations')) {
            return;
        }

        Schema::create('plugin_license_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plugin_license_id')->nullable()->index();
            $table->unsignedBigInteger('merchant_id')->nullable()->index();
            $table->string('action', 30)->index(); // generate, validate, revoke, regenerate, details
            $table->string('result', 20)->index(); // success, failed
            $table->string('message', 255)->nullable();
            $table->string('request_email', 191)->nullable()->index();
            $table->string('request_domain', 255)->nullable();
            $table->string('normalized_domain', 191)->nullable()->index();
            $table->string('plugin_version', 50)->nullable();
            $table->string('site_url', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('plugin_license_validations')) {
            Schema::dropIfExists('plugin_license_validations');
        }
    }
};
