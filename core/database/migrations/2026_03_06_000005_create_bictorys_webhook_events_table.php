<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('bictorys_webhook_events')) {
            return;
        }

        Schema::create('bictorys_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_uid', 191)->unique();
            $table->string('provider', 32)->default('bictorys')->index();
            $table->string('event_id', 191)->nullable()->index();
            $table->string('gateway_alias', 64)->nullable()->index();
            $table->string('charge_id', 191)->nullable()->index();
            $table->string('payment_reference', 191)->nullable()->index();
            $table->unsignedBigInteger('deposit_id')->nullable()->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('status', 32)->default('queued')->index();
            $table->string('payload_hash', 64)->nullable()->index();
            $table->longText('payload')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bictorys_webhook_events');
    }
};
