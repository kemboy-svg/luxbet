<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('game_providers')->restrictOnDelete();
            $table->string('provider_game_code', 100);
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('category', 24)->index();
            $table->string('thumbnail_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('supports_demo')->default(false);
            $table->boolean('is_enabled')->default(false);
            $table->unsignedInteger('sort_order')->default(100);
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();

            $table->unique(['provider_id', 'provider_game_code']);
            $table->index(['is_enabled', 'category', 'sort_order']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE games ADD CONSTRAINT games_category_valid CHECK (category IN ('slots', 'live_casino', 'blackjack', 'baccarat', 'poker', 'crash'))");
            DB::statement("ALTER TABLE games ADD CONSTRAINT games_slug_format CHECK (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$')");
            DB::statement("ALTER TABLE games ADD CONSTRAINT games_metadata_is_object CHECK (jsonb_typeof(metadata) = 'object')");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
