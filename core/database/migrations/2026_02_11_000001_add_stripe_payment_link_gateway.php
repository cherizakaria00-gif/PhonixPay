<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gateways')) {
            return;
        }

        $exists = DB::table('gateways')->where('alias', 'StripePaymentLink')->exists();
        if ($exists) {
            return;
        }

        $gatewayParameters = json_encode([
            'secret_key' => [
                'title' => 'Secret Key',
                'global' => true,
                'value' => '',
            ],
        ]);

        $supportedCurrencies = json_encode([
            'USD' => 'USD',
            'AUD' => 'AUD',
            'BRL' => 'BRL',
            'CAD' => 'CAD',
            'CHF' => 'CHF',
            'DKK' => 'DKK',
            'EUR' => 'EUR',
            'GBP' => 'GBP',
            'HKD' => 'HKD',
            'INR' => 'INR',
            'JPY' => 'JPY',
            'MXN' => 'MXN',
            'MYR' => 'MYR',
            'NOK' => 'NOK',
            'NZD' => 'NZD',
            'PLN' => 'PLN',
            'SEK' => 'SEK',
            'SGD' => 'SGD',
        ]);

        $extra = json_encode([
            'webhook' => [
                'title' => 'Webhook Endpoint',
                'value' => 'ipn.StripePaymentLink',
            ],
        ]);

        DB::table('gateways')->insert([
            'form_id' => 0,
            'code' => 126,
            'name' => 'Stripe Payment Link',
            'alias' => 'StripePaymentLink',
            'image' => '663a39afb519f1715091887.png',
            'status' => 1,
            'gateway_parameters' => $gatewayParameters,
            'supported_currencies' => $supportedCurrencies,
            'crypto' => 0,
            'extra' => $extra,
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('gateways')) {
            return;
        }

        DB::table('gateways')->where('alias', 'StripePaymentLink')->delete();
    }
};
