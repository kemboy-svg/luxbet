<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demo-mode responsible-play preferences. These are session reminders and
 * account controls, not a complete real-money responsible-gambling programme.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsible_play_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('demo_session_reminder_minutes')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE responsible_play_settings ADD CONSTRAINT rp_reminder_minutes_valid CHECK (demo_session_reminder_minutes IS NULL OR demo_session_reminder_minutes IN (15, 30, 45, 60, 90))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('responsible_play_settings');
    }
};
