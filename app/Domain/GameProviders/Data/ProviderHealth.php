<?php

namespace App\Domain\GameProviders\Data;

final readonly class ProviderHealth
{
    public function __construct(
        public bool $available,
        public ?int $latencyMs = null,
        public ?string $message = null,
    ) {}

    /**
     * @return array{available: bool, latency_ms: int|null, message: string|null}
     */
    public function toArray(): array
    {
        return ['available' => $this->available, 'latency_ms' => $this->latencyMs, 'message' => $this->message];
    }
}
