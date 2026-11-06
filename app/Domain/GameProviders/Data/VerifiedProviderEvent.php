<?php

namespace App\Domain\GameProviders\Data;

use App\Domain\GameProviders\Enums\DemoEventType;
use Carbon\CarbonImmutable;

/**
 * A provider event whose signature, timestamp and schema have been checked.
 * It identifies the session, never the player or wallet: those are always
 * looked up from our own session record.
 */
final readonly class VerifiedProviderEvent
{
    public function __construct(
        public string $eventId,
        public DemoEventType $type,
        public string $sessionReference,
        public string $roundReference,
        public int $amount,
        public CarbonImmutable $occurredAt,
        public string $payloadHash,
    ) {}
}
