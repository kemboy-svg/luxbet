<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Currency catalogue for display and demo configuration only. A listed
 * currency does NOT mean payments or gambling in it are authorised.
 *
 * XXX is ISO 4217's code for "no currency": demo wallets use it so demo
 * credits are abstract units, never MUR, USD, EUR or KES amounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->char('code', 3)->primary();
            $table->string('name', 64);
            $table->unsignedSmallInteger('minor_unit');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_demo_unit')->default(false);
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE currencies ADD CONSTRAINT currencies_code_format CHECK (code ~ '^[A-Z]{3}$')");
            DB::statement('ALTER TABLE currencies ADD CONSTRAINT currencies_minor_unit_range CHECK (minor_unit <= 4)');
        }

        $now = now();

        DB::table('currencies')->insert([
            ['code' => 'XXX', 'name' => 'Demo credits', 'minor_unit' => 0, 'is_enabled' => true, 'is_demo_unit' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'MUR', 'name' => 'Mauritian rupee', 'minor_unit' => 2, 'is_enabled' => true, 'is_demo_unit' => false, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'USD', 'name' => 'US dollar', 'minor_unit' => 2, 'is_enabled' => true, 'is_demo_unit' => false, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'EUR', 'name' => 'Euro', 'minor_unit' => 2, 'is_enabled' => true, 'is_demo_unit' => false, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'KES', 'name' => 'Kenyan shilling', 'minor_unit' => 2, 'is_enabled' => true, 'is_demo_unit' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
