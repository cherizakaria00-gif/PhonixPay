<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plugin_licenses')) {
            return;
        }

        Schema::create('plugin_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id')->index();
            $table->string('email', 191);
            $table->string('domain', 255);
            $table->string('normalized_domain', 191)->index();
            $table->string('license_key', 191)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->string('plugin_name', 100)->default('flujipay-woocommerce')->index();
            $table->string('plugin_version_last_seen', 50)->nullable();
            $table->unsignedInteger('activations_count')->default(0);
            $table->timestamp('last_validated_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('pending_domain', 255)->nullable();
            $table->string('pending_normalized_domain', 191)->nullable();
            $table->timestamp('domain_change_requested_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->unsignedBigInteger('regenerated_from_id')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'normalized_domain', 'plugin_name'], 'plugin_licenses_merchant_domain_plugin_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('plugin_licenses')) {
            Schema::dropIfExists('plugin_licenses');
        }
    }
};
