<?php

namespace App\Domain\GameProviders\Actions;

use App\Domain\GameProviders\Data\IngestResult;
use App\Domain\GameProviders\Enums\WebhookProcessingStatus;
use App\Domain\GameProviders\Exceptions\InvalidProviderWebhook;
use App\Domain\GameProviders\Models\ProviderWebhookEvent;
use App\Domain\GameProviders\Support\ProviderAdapterRegistry;
use App\Domain\Games\Models\GameProvider;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Entry point for every provider event (HTTP webhooks and the simulator):
 *  1. reject oversized bodies;
 *  2. verify signature, timestamp and schema via the provider's adapter;
 *  3. persist the receipt (unique per provider + event ID) BEFORE processing;
 *  4. process it once; a repeated event ID changes nothing.
 */
class IngestProviderWebhook
{
    public function __construct(
        private readonly ProviderAdapterRegistry $adapters,
        private readonly ProcessDemoEvent $process,
    ) {}

    /**
     * @param  array<string, string>  $headers  lower-cased header names
     *
     * @throws InvalidProviderWebhook
     */
    public function __invoke(GameProvider $provider, string $body, array $headers): IngestResult
    {
        if (strlen($body) > (int) config('luxbet.providers.webhook.max_body_bytes')) {
            throw InvalidProviderWebhook::tooLarge();
        }

        try {
            $event = $this->adapters->for($provider)->verifyWebhook($provider, $body, $headers);
        } catch (InvalidProviderWebhook $e) {
            Log::warning('Rejected provider webhook.', ['provider' => $provider->slug, 'code' => $e->errorCode]);

            throw $e;
        }

        $receipt = new ProviderWebhookEvent;
        $receipt->forceFill([
            'provider_id' => $provider->id,
            'event_id' => $event->eventId,
            'event_type' => $event->type->value,
            'payload_hash' => $event->payloadHash,
            'round_reference' => $event->roundReference,
            'amount' => $event->amount,
            'processing_status' => WebhookProcessingStatus::Received,
            'received_at' => Date::now(),
        ]);

        try {
            // Savepoint, so a duplicate does not abort an enclosing transaction.
            DB::transaction(fn () => $receipt->save());
        } catch (UniqueConstraintViolationException) {
            $existing = ProviderWebhookEvent::query()
                ->where('provider_id', $provider->id)
                ->where('event_id', $event->eventId)
                ->firstOrFail();

            if ($existing->payload_hash !== $event->payloadHash) {
                Log::warning('Provider reused an event ID with a different payload; ignored.', [
                    'provider' => $provider->slug,
                    'event_id' => $event->eventId,
                ]);
            }

            return IngestResult::duplicate($existing);
        }

        return ($this->process)($provider, $event, $receipt);
    }
}
