<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demo game sessions. `mode` is fixed to `demo` by a check constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('game_id')->constrained('games')->restrictOnDelete();
            $table->foreignUuid('provider_id')->constrained('game_providers')->restrictOnDelete();
            $table->foreignUuid('demo_wallet_id')->constrained('demo_wallets')->restrictOnDelete();
            $table->string('session_reference', 64)->unique();
            $table->string('provider_session_reference', 128)->nullable()->unique();
            $table->string('mode', 8)->default('demo');
            $table->string('status', 16)->default('created');
            $table->string('failure_reason', 255)->nullable();
            $table->timestampTz('launched_at')->nullable();
            $table->timestampTz('expires_at');
            $table->timestampTz('ended_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'expires_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE game_sessions ADD CONSTRAINT game_sessions_mode_demo_only CHECK (mode = 'demo')");
            DB::statement("ALTER TABLE game_sessions ADD CONSTRAINT game_sessions_status_valid CHECK (status IN ('created', 'launched', 'completed', 'expired', 'failed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
