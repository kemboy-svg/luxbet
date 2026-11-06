<?php

namespace App\Domain\GameProviders\Data;

use App\Domain\DemoWallet\Models\DemoWalletTransaction;
use App\Domain\GameProviders\Models\ProviderWebhookEvent;

final readonly class IngestResult
{
    public function __construct(
        public string $status,
        public ProviderWebhookEvent $event,
        public ?DemoWalletTransaction $transaction = null,
        public ?string $reason = null,
    ) {}

    public static function duplicate(ProviderWebhookEvent $event): self
    {
        return new self('duplicate', $event);
    }

    /**
     * @return array{status: string, event_id: string, reason: string|null, balance_after: int|null}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'event_id' => $this->event->event_id,
            'reason' => $this->reason,
            'balance_after' => $this->transaction?->balance_after_minor,
        ];
    }
}
