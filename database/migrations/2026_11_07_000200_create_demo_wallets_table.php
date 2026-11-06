<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demo-credit wallets. Fictional, non-redeemable balances that must never be
 * reused as, or connected to, a cash ledger (ADR-002).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->char('currency_code', 3);
            $table->bigInteger('balance_minor')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestampsTz();

            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete();
            $table->unique(['user_id', 'currency_code']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE demo_wallets ADD CONSTRAINT demo_wallets_balance_non_negative CHECK (balance_minor >= 0)');
            DB::statement("ALTER TABLE demo_wallets ADD CONSTRAINT demo_wallets_status_valid CHECK (status IN ('active', 'locked', 'closed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_wallets');
    }
};
