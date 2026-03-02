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

            if (!isset($params['eur_xof_rate'])) {
                $params['eur_xof_rate'] = [
                    'title' => 'EUR to XOF Rate',
                    'global' => true,
                    'value' => '655.957',
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
            if (!is_array($params) || !isset($params['eur_xof_rate'])) {
                continue;
            }

            unset($params['eur_xof_rate']);

            DB::table('gateways')
                ->where('id', $gateway->id)
                ->update(['gateway_parameters' => json_encode($params)]);
        }
    }
};
