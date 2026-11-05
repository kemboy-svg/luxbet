<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('username', 20);
            $table->char('country_code', 2)->nullable();
            $table->string('preferred_locale', 10)->default('en');
            // Only collected if a documented need and privacy review justify it.
            $table->date('date_of_birth')->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestampTz('age_confirmed_at')->nullable();
            $table->timestampTz('terms_accepted_at')->nullable();
            $table->string('terms_version', 20)->nullable();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX player_profiles_username_unique ON player_profiles (lower(username))');
            DB::statement("ALTER TABLE player_profiles ADD CONSTRAINT player_profiles_username_format CHECK (username ~ '^[A-Za-z0-9_]{3,20}$')");
            DB::statement("ALTER TABLE player_profiles ADD CONSTRAINT player_profiles_country_code_format CHECK (country_code IS NULL OR country_code ~ '^[A-Z]{2}$')");
        } else {
            Schema::table('player_profiles', fn (Blueprint $table) => $table->unique('username'));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
    }
};
