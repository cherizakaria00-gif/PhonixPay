<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('virtual_card_settings')) {
            Schema::create('virtual_card_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('enabled')->default(false);
                $table->string('provider', 32)->default('strowallet');
                $table->unsignedInteger('max_cards_per_merchant')->default(3);
                $table->string('default_currency', 16)->default('USD');
                $table->string('base_url')->nullable();
                $table->string('secret_key')->nullable();
                $table->string('public_key')->nullable();
                $table->string('webhook_secret')->nullable();
                $table->json('endpoints')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_card_settings');
    }
};
