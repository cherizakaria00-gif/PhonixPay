<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_links')) {
            Schema::create('payment_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('code', 64)->unique();
                $table->decimal('amount', 28, 8)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->string('description', 255)->nullable();
                $table->string('redirect_url', 255)->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->unsignedTinyInteger('status')->default(0);
                $table->unsignedBigInteger('deposit_id')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payment_links')) {
            Schema::dropIfExists('payment_links');
        }
    }
};
