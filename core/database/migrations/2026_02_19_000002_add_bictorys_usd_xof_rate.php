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

        $aliases = ['BictorysCheckout', 'BictorysDirect'];
        foreach ($aliases as $alias) {
            $gateway = DB::table('gateways')->where('alias', $alias)->first();
            if (!$gateway) {
                continue;
            }

            $params = json_decode($gateway->gateway_parameters ?? '{}', true);
            if (!is_array($params)) {
                $params = [];
            }

            if (!isset($params['usd_xof_rate'])) {
                $params['usd_xof_rate'] = [
                    'title' => 'USD to XOF Rate',
                    'global' => true,
                    'value' => '',
                ];

                DB::table('gateways')
                    ->where('id', $gateway->id)
                    ->update(['gateway_parameters' => json_encode($params)]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('gateways')) {
            return;
        }

        $aliases = ['BictorysCheckout', 'BictorysDirect'];
        foreach ($aliases as $alias) {
            $gateway = DB::table('gateways')->where('alias', $alias)->first();
            if (!$gateway) {
                continue;
            }

            $params = json_decode($gateway->gateway_parameters ?? '{}', true);
            if (!is_array($params) || !isset($params['usd_xof_rate'])) {
                continue;
            }

            unset($params['usd_xof_rate']);

            DB::table('gateways')
                ->where('id', $gateway->id)
                ->update(['gateway_parameters' => json_encode($params)]);
        }
    }
};
