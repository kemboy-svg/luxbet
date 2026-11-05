<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the singleton launch-control record with real-money mode OFF.
 *
 * PostgreSQL check constraints make the record a singleton and lock
 * real_money_enabled to false. Lifting that lock requires a new, separately
 * approved migration (see docs/regulatory-launch-gate.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('launch_control', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();
            $table->boolean('real_money_enabled')->default(false);
            $table->string('environment_mode', 16)->default('demo');
            $table->jsonb('approved_jurisdictions')->default(DB::raw("'[]'::jsonb"));
            $table->string('approval_reference')->nullable();
            $table->string('approval_document_path')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('review_due_at')->nullable();
            $table->text('change_reason')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE launch_control ADD CONSTRAINT launch_control_singleton CHECK (id = 1)');
            DB::statement("ALTER TABLE launch_control ADD CONSTRAINT launch_control_environment_mode_valid CHECK (environment_mode IN ('demo', 'staging', 'production'))");
            DB::statement("ALTER TABLE launch_control ADD CONSTRAINT launch_control_jurisdictions_is_array CHECK (jsonb_typeof(approved_jurisdictions) = 'array')");
            DB::statement('ALTER TABLE launch_control ADD CONSTRAINT launch_control_version_positive CHECK (version >= 1)');
            DB::statement('ALTER TABLE launch_control ADD CONSTRAINT launch_control_real_money_locked CHECK (real_money_enabled = false)');
        }

        DB::table('launch_control')->insert([
            'id' => 1,
            'real_money_enabled' => false,
            'environment_mode' => 'demo',
            'change_reason' => 'Initial installation: real-money mode disabled for the demo-only release.',
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('launch_control');
    }
};
