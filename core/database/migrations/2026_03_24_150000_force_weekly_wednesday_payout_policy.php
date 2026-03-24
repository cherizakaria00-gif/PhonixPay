<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('plans')) {
            DB::table('plans')->update([
                'payout_frequency' => 'weekly_7d',
                'payout_delay_days' => 7,
            ]);

            DB::table('plans')
                ->where('slug', 'business')
                ->update([
                    'payout_frequency' => 'twice_weekly',
                    'payout_delay_days' => null,
                ]);
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'plan_custom_overrides')) {
            DB::table('users')
                ->whereNotNull('plan_custom_overrides')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $raw = $row->plan_custom_overrides;
                        $overrides = is_array($raw) ? $raw : json_decode((string) $raw, true);

                        if (!is_array($overrides)) {
                            continue;
                        }

                        unset($overrides['payout_frequency'], $overrides['payout_delay_days']);

                        DB::table('users')
                            ->where('id', $row->id)
                            ->update([
                                'plan_custom_overrides' => empty($overrides) ? null : json_encode($overrides),
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Intentionally left blank to avoid restoring legacy mixed payout policies.
    }
};
