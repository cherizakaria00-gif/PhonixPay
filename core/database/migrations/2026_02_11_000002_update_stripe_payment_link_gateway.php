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

        $gateway = DB::table('gateways')->where('alias', 'StripePaymentLink')->first();
        if (!$gateway) {
            return;
        }

        $parameters = json_decode($gateway->gateway_parameters, true) ?? [];
        if (isset($parameters['end_point'])) {
            unset($parameters['end_point']);
        }

        if (!isset($parameters['secret_key'])) {
            $parameters['secret_key'] = [
                'title' => 'Secret Key',
                'global' => true,
                'value' => '',
            ];
        }

        DB::table('gateways')
            ->where('alias', 'StripePaymentLink')
            ->update([
                'gateway_parameters' => json_encode($parameters),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('gateways')) {
            return;
        }

        $gateway = DB::table('gateways')->where('alias', 'StripePaymentLink')->first();
        if (!$gateway) {
            return;
        }

        $parameters = json_decode($gateway->gateway_parameters, true) ?? [];
        if (!isset($parameters['end_point'])) {
            $parameters['end_point'] = [
                'title' => 'End Point Secret',
                'global' => true,
                'value' => '',
            ];
        }

        DB::table('gateways')
            ->where('alias', 'StripePaymentLink')
            ->update([
                'gateway_parameters' => json_encode($parameters),
                'updated_at' => now(),
            ]);
    }
};
