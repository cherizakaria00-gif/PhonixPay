<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $codes = [112, 116, 122];
        $aliases = ['Instamojo', 'Cashmaal', 'BTCPay'];

        DB::table('gateway_currencies')
            ->whereIn('method_code', $codes)
            ->delete();

        DB::table('gateways')
            ->whereIn('code', $codes)
            ->orWhereIn('alias', $aliases)
            ->delete();
    }

    public function down(): void
    {
        // Intentionally left empty. These gateways are removed permanently.
    }
};
