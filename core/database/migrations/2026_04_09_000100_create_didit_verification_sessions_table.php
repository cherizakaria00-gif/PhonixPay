<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('didit_verification_sessions')) {
            Schema::create('didit_verification_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('session_id', 120)->unique();
                $table->string('workflow_id', 120)->nullable();
                $table->string('status', 60)->default('not_started')->index();
                $table->string('vendor_data', 191)->nullable()->index();
                $table->text('verification_url')->nullable();
                $table->json('decision')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('last_webhook_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'identity_verification_status')) {
                    $table->string('identity_verification_status', 60)->nullable()->after('kv')->index();
                }

                if (!Schema::hasColumn('users', 'didit_last_session_id')) {
                    $table->string('didit_last_session_id', 120)->nullable()->after('identity_verification_status');
                }

                if (!Schema::hasColumn('users', 'didit_verified_at')) {
                    $table->timestamp('didit_verified_at')->nullable()->after('didit_last_session_id');
                }

                if (!Schema::hasColumn('users', 'didit_decision')) {
                    $table->json('didit_decision')->nullable()->after('didit_verified_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $drop = [];

                foreach (['identity_verification_status', 'didit_last_session_id', 'didit_verified_at', 'didit_decision'] as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $drop[] = $column;
                    }
                }

                if ($drop !== []) {
                    $table->dropColumn($drop);
                }
            });
        }

        Schema::dropIfExists('didit_verification_sessions');
    }
};
