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

        $gateway = DB::table('gateways')
            ->select('id', 'supported_currencies')
            ->where('alias', 'BictorysDirect')
            ->first();

        if (!$gateway) {
            return;
        }

        $currencies = json_decode((string) $gateway->supported_currencies, true);
        if (!is_array($currencies)) {
            $currencies = [];
        }

        $currencies['XOF'] = 'XOF';
        $currencies['USD'] = 'USD';
        $currencies['EUR'] = 'EUR';
        $currencies['CAD'] = 'CAD';

        DB::table('gateways')
            ->where('id', $gateway->id)
            ->update([
                'supported_currencies' => json_encode($currencies),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('gateways')) {
            return;
        }

        $gateway = DB::table('gateways')
            ->select('id', 'supported_currencies')
            ->where('alias', 'BictorysDirect')
            ->first();

        if (!$gateway) {
            return;
        }

        $currencies = json_decode((string) $gateway->supported_currencies, true);
        if (!is_array($currencies)) {
            return;
        }

        unset($currencies['EUR'], $currencies['CAD']);

        DB::table('gateways')
            ->where('id', $gateway->id)
            ->update([
                'supported_currencies' => json_encode($currencies),
                'updated_at' => now(),
            ]);
    }
};

