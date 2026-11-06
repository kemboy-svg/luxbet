<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Receipt of every signature-verified provider event, stored before any
 * business processing. Only a hash of the payload and a few non-personal
 * fields are kept; tokens and personal data are never stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('game_providers')->restrictOnDelete();
            $table->string('event_id', 128);
            $table->string('event_type', 64);
            $table->string('payload_hash', 64);
            $table->foreignUuid('game_session_id')->nullable()->constrained('game_sessions')->nullOnDelete();
            $table->string('round_reference', 128)->nullable();
            $table->bigInteger('amount')->nullable();
            $table->string('processing_status', 16)->default('received');
            $table->string('failure_reason', 255)->nullable();
            $table->timestampTz('received_at');
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['provider_id', 'event_id']);
            $table->index(['processing_status', 'received_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE provider_webhook_events ADD CONSTRAINT provider_webhook_events_status_valid CHECK (processing_status IN ('received', 'processed', 'rejected', 'failed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_webhook_events');
    }
};
