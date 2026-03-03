<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('currency_conversion_rates')) {
            return;
        }

        Schema::create('currency_conversion_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 10);
            $table->string('quote_currency', 10);
            $table->decimal('rate', 20, 8);
            $table->boolean('is_active')->default(true);
            $table->string('source', 20)->default('manual');
            $table->timestamps();

            $table->unique(['base_currency', 'quote_currency'], 'ccr_base_quote_unique');
            $table->index(['base_currency', 'is_active'], 'ccr_base_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_conversion_rates');
    }
};

