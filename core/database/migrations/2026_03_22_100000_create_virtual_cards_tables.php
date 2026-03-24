<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('virtual_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 32)->default('strowallet');
            $table->string('provider_customer_id')->nullable();
            $table->string('provider_card_id')->nullable()->index();
            $table->string('card_reference')->nullable()->index();
            $table->string('brand', 50)->nullable();
            $table->string('last4', 8)->nullable();
            $table->string('masked_pan')->nullable();
            $table->decimal('balance', 28, 8)->default(0);
            $table->string('currency', 16)->default('USD');
            $table->string('status', 32)->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('virtual_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('virtual_card_id');
            $table->unsignedBigInteger('user_id');
            $table->string('provider_txn_id')->nullable()->index();
            $table->string('type', 32)->index();
            $table->decimal('amount', 28, 8)->default(0);
            $table->decimal('balance_after', 28, 8)->nullable();
            $table->string('currency', 16)->default('USD');
            $table->string('status', 32)->default('posted');
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('transacted_at')->nullable();
            $table->timestamps();

            $table->foreign('virtual_card_id')->references('id')->on('virtual_cards')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['virtual_card_id', 'provider_txn_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'strowallet_customer_id')) {
                $table->string('strowallet_customer_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'strowallet_customer_id')) {
                $table->dropColumn('strowallet_customer_id');
            }
        });

        Schema::dropIfExists('virtual_card_transactions');
        Schema::dropIfExists('virtual_cards');
    }
};
