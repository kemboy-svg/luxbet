<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Game providers. Secrets are never stored here: only references to a
 * secrets manager or encrypted configuration entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 64)->unique();
            $table->string('environment', 16)->default('sandbox');
            $table->string('status', 16)->default('inactive');
            $table->string('base_url')->nullable();
            $table->string('credentials_secret_reference')->nullable();
            $table->boolean('supports_demo')->default(false);
            $table->boolean('is_simulator')->default(false);
            $table->string('webhook_signing_key_reference')->nullable();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE game_providers ADD CONSTRAINT game_providers_environment_valid CHECK (environment IN ('sandbox', 'production'))");
            DB::statement("ALTER TABLE game_providers ADD CONSTRAINT game_providers_status_valid CHECK (status IN ('inactive', 'sandbox', 'active', 'suspended'))");
            DB::statement("ALTER TABLE game_providers ADD CONSTRAINT game_providers_slug_format CHECK (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$')");
            DB::statement("ALTER TABLE game_providers ADD CONSTRAINT game_providers_base_url_https CHECK (base_url IS NULL OR base_url LIKE 'https://%')");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('game_providers');
    }
};
