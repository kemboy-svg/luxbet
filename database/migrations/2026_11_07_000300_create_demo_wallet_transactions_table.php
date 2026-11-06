<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only demo-credit ledger. Every balance change has exactly one row.
 * The database checks that amounts are positive and that each row's
 * before/after balances agree with its direction, and a trigger rejects
 * UPDATE and DELETE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained('demo_wallets')->restrictOnDelete();
            $table->string('reference', 32)->unique();
            $table->string('type', 24);
            $table->string('direction', 8);
            $table->bigInteger('amount_minor');
            $table->bigInteger('balance_before_minor');
            $table->bigInteger('balance_after_minor');
            $table->string('source', 24);
            $table->string('external_reference')->nullable();
            $table->string('idempotency_key', 191)->nullable()->unique();
            $table->string('description', 255);
            // No foreign key: ledger rows must outlive staff accounts, and the
            // append-only trigger would reject a cascading update.
            $table->uuid('created_by')->nullable()->index();
            $table->timestampsTz();

            $table->index(['wallet_id', 'created_at']);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE demo_wallet_transactions ADD CONSTRAINT demo_tx_amount_positive CHECK (amount_minor > 0)');
        DB::statement('ALTER TABLE demo_wallet_transactions ADD CONSTRAINT demo_tx_balances_non_negative CHECK (balance_before_minor >= 0 AND balance_after_minor >= 0)');
        DB::statement("ALTER TABLE demo_wallet_transactions ADD CONSTRAINT demo_tx_type_valid CHECK (type IN ('demo_credit', 'demo_bet', 'demo_win', 'demo_refund', 'demo_adjustment'))");
        DB::statement("ALTER TABLE demo_wallet_transactions ADD CONSTRAINT demo_tx_source_valid CHECK (source IN ('system', 'provider_sandbox', 'admin'))");
        DB::statement(<<<'SQL'
            ALTER TABLE demo_wallet_transactions ADD CONSTRAINT demo_tx_direction_matches CHECK (
                (direction = 'credit' AND balance_after_minor = balance_before_minor + amount_minor AND type IN ('demo_credit', 'demo_win', 'demo_refund', 'demo_adjustment'))
                OR
                (direction = 'debit' AND balance_after_minor = balance_before_minor - amount_minor AND type IN ('demo_bet', 'demo_adjustment'))
            )
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE FUNCTION demo_wallet_transactions_block_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'demo_wallet_transactions is append-only';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER demo_wallet_transactions_append_only
                BEFORE UPDATE OR DELETE ON demo_wallet_transactions
                FOR EACH ROW EXECUTE FUNCTION demo_wallet_transactions_block_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_wallet_transactions');

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS demo_wallet_transactions_block_mutation()');
        }
    }
};
