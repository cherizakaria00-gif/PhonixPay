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

        $exists = DB::table('gateways')->where('alias', 'BictorysDirect')->exists();
        if ($exists) {
            return;
        }

        $gatewayParameters = json_encode([
            'api_base_url' => [
                'title' => 'API Base URL',
                'global' => true,
                'value' => 'https://api.test.bictorys.com',
            ],
            'api_key' => [
                'title' => 'API Key',
                'global' => true,
                'value' => 'test_public-4b6d305c-6f09-443c-be4a-2e1b8f0594d0.srftxna0bvgR3BOiw1kmrOjZ1i9OuoNS5omPJp6O2b2YlN9RRRwQ2AwrP7a7OLZT',
            ],
            'merchant_reference' => [
                'title' => 'Merchant Reference',
                'global' => true,
                'value' => '',
            ],
            'usd_xof_rate' => [
                'title' => 'USD to XOF Rate',
                'global' => true,
                'value' => '',
            ],
            'payment_type' => [
                'title' => 'Payment Type',
                'global' => false,
                'value' => '',
            ],
            'country' => [
                'title' => 'Country',
                'global' => false,
                'value' => '',
            ],
        ]);

        $supportedCurrencies = json_encode([
            'XOF' => 'XOF',
            'USD' => 'USD',
            'EUR' => 'EUR',
            'CAD' => 'CAD',
        ]);

        $extra = json_encode([
            'webhook' => [
                'title' => 'Webhook Endpoint',
                'value' => 'ipn.BictorysDirect',
            ],
        ]);

        DB::table('gateways')->insert([
            'form_id' => 0,
            'code' => 128,
            'name' => 'Bictorys Direct',
            'alias' => 'BictorysDirect',
            'image' => '663a35cd25a8d1715090893.png',
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

        DB::table('gateways')->where('alias', 'BictorysDirect')->delete();
    }
};
