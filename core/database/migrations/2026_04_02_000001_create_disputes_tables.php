<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('disputes')) {
            Schema::create('disputes', function (Blueprint $table) {
                $table->id();
                $table->string('dispute_id', 40)->unique();
                $table->unsignedBigInteger('deposit_id')->index();
                $table->unsignedBigInteger('merchant_id')->index();
                $table->string('transaction_id', 80)->index();
                $table->string('provider', 64)->nullable()->index();
                $table->string('customer_email')->nullable()->index();
                $table->decimal('amount', 28, 8)->default(0);
                $table->string('currency', 16)->default('USD');
                $table->decimal('dispute_fee', 28, 8)->default(29);
                $table->timestamp('dispute_fee_charged_at')->nullable();
                $table->decimal('refunded_amount', 28, 8)->default(0);
                $table->timestamp('transaction_amount_debited_at')->nullable();
                $table->string('reason', 255)->nullable();
                $table->string('provider_email', 255)->nullable();
                $table->timestamp('provider_email_sent_at')->nullable();
                $table->string('provider_reference', 120)->nullable();
                $table->string('status', 40)->index();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('resolution_deadline')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('refunded_at')->nullable();
                $table->timestamp('expired_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->text('merchant_notes')->nullable();
                $table->string('created_by_type', 20)->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('deposit_id')->references('id')->on('deposits')->onDelete('cascade');
                $table->foreign('merchant_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('dispute_logs')) {
            Schema::create('dispute_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dispute_id')->index();
                $table->string('action', 80);
                $table->string('old_status', 40)->nullable();
                $table->string('new_status', 40)->nullable();
                $table->string('actor_type', 20)->index();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->text('note')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();

                $table->foreign('dispute_id')->references('id')->on('disputes')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_logs');
        Schema::dropIfExists('disputes');
    }
};
