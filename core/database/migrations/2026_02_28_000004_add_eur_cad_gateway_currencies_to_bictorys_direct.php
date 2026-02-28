<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gateway_currencies')) {
            return;
        }

        $templateRow = DB::table('gateway_currencies')
            ->where('gateway_alias', 'BictorysDirect')
            ->orderByRaw("CASE WHEN currency = 'USD' THEN 0 ELSE 1 END")
            ->first();

        if (!$templateRow) {
            return;
        }

        $templateRow = (array) $templateRow;
        unset($templateRow['id']);

        foreach (['EUR', 'CAD'] as $currency) {
            $exists = DB::table('gateway_currencies')
                ->where('gateway_alias', 'BictorysDirect')
                ->where('currency', $currency)
                ->exists();

            if ($exists) {
                continue;
            }

            $row = $templateRow;
            $row['name'] = 'Bictorys Direct - ' . $currency;
            $row['currency'] = $currency;
            $row['symbol'] = $currency;
            $row['rate'] = 1;
            $row['created_at'] = now();
            $row['updated_at'] = now();

            DB::table('gateway_currencies')->insert($row);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('gateway_currencies')) {
            return;
        }

        DB::table('gateway_currencies')
            ->where('gateway_alias', 'BictorysDirect')
            ->whereIn('currency', ['EUR', 'CAD'])
            ->delete();
    }
};

