<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'setup_fee_status')) {
                $table->string('setup_fee_status', 30)->default('unpaid');
            }

            if (!Schema::hasColumn('users', 'setup_fee_payment_link_id')) {
                $table->unsignedBigInteger('setup_fee_payment_link_id')->nullable();
            }

            if (!Schema::hasColumn('users', 'setup_fee_submitted_at')) {
                $table->dateTime('setup_fee_submitted_at')->nullable();
            }

            if (!Schema::hasColumn('users', 'setup_fee_reviewed_at')) {
                $table->dateTime('setup_fee_reviewed_at')->nullable();
            }

            if (!Schema::hasColumn('users', 'setup_fee_rejection_reason')) {
                $table->text('setup_fee_rejection_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'setup_fee_rejection_reason',
                'setup_fee_reviewed_at',
                'setup_fee_submitted_at',
                'setup_fee_payment_link_id',
                'setup_fee_status',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
