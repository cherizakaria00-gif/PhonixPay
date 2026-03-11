<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_integrations')) {
            Schema::create('ai_integrations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('merchant_id')->unique();
                $table->string('selected_option', 20)->nullable()->index(); // api_keys, payment_link, plugin_sdk
                $table->string('status', 30)->default('not_configured')->index(); // not_configured, draft, connected, needs_attention
                $table->string('public_key_reference', 191)->nullable();
                $table->string('secret_key_reference', 191)->nullable();
                $table->string('success_url', 255)->nullable();
                $table->string('cancel_url', 255)->nullable();
                $table->unsignedBigInteger('payment_link_id')->nullable()->index();
                $table->string('payment_link_url', 255)->nullable();
                $table->string('merchant_email', 191)->nullable();
                $table->string('website_url', 255)->nullable();
                $table->string('normalized_domain', 191)->nullable()->index();
                $table->string('license_key', 191)->nullable();
                $table->json('option_payload')->nullable();
                $table->timestamp('setup_completed_at')->nullable();
                $table->timestamp('last_configured_at')->nullable();
                $table->timestamps();

                $table->index(['merchant_id', 'selected_option'], 'ai_integrations_merchant_option_idx');
            });
        }

        if (!Schema::hasTable('ai_integration_events')) {
            Schema::create('ai_integration_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ai_integration_id')->nullable()->index();
                $table->unsignedBigInteger('merchant_id')->nullable()->index();
                $table->string('action', 50)->index();
                $table->string('result', 20)->index(); // success, failed
                $table->string('message', 255)->nullable();
                $table->string('integration_type', 30)->nullable()->index();
                $table->string('payment_reference', 191)->nullable()->index();
                $table->string('provider_reference', 191)->nullable()->index();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->json('context')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('deposits')) {
            Schema::table('deposits', function (Blueprint $table) {
                if (!Schema::hasColumn('deposits', 'integration_source_type')) {
                    $table->string('integration_source_type', 30)->nullable()->index()->after('payment_link_id');
                }
                if (!Schema::hasColumn('deposits', 'ai_integration_id')) {
                    $table->unsignedBigInteger('ai_integration_id')->nullable()->index()->after('integration_source_type');
                }
                if (!Schema::hasColumn('deposits', 'provider_reference')) {
                    $table->string('provider_reference', 191)->nullable()->index()->after('trx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deposits')) {
            Schema::table('deposits', function (Blueprint $table) {
                if (Schema::hasColumn('deposits', 'provider_reference')) {
                    $table->dropColumn('provider_reference');
                }
                if (Schema::hasColumn('deposits', 'ai_integration_id')) {
                    $table->dropColumn('ai_integration_id');
                }
                if (Schema::hasColumn('deposits', 'integration_source_type')) {
                    $table->dropColumn('integration_source_type');
                }
            });
        }

        if (Schema::hasTable('ai_integration_events')) {
            Schema::dropIfExists('ai_integration_events');
        }

        if (Schema::hasTable('ai_integrations')) {
            Schema::dropIfExists('ai_integrations');
        }
    }
};
