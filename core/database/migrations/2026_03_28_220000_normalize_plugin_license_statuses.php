<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plugin_licenses')) {
            return;
        }

        DB::table('plugin_licenses')
            ->select('id', 'status')
            ->orderBy('id')
            ->chunkById(200, function ($licenses): void {
                foreach ($licenses as $license) {
                    $raw = strtolower(trim((string) ($license->status ?? '')));

                    $normalized = match (true) {
                        in_array($raw, ['active', '1', 'true', 'enabled', 'approved'], true) => 'active',
                        in_array($raw, ['revoked', 'blocked', 'disabled'], true) => 'revoked',
                        default => 'inactive',
                    };

                    if ($normalized !== (string) $license->status) {
                        DB::table('plugin_licenses')
                            ->where('id', $license->id)
                            ->update([
                                'status' => $normalized,
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }
};

