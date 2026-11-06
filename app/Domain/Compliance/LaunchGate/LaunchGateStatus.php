<?php

namespace App\Domain\Compliance\LaunchGate;

use App\Domain\Compliance\Enums\EnvironmentMode;
use Carbon\CarbonImmutable;

/**
 * Read-only snapshot of the launch gate, safe to show to staff.
 */
final readonly class LaunchGateStatus
{
    /**
     * @param  list<string>  $blockingReasons  Every reason the gate is closed. Empty only when enabled.
     */
    public function __construct(
        public bool $realMoneyEnabled,
        public array $blockingReasons,
        public EnvironmentMode $environmentMode,
        public bool $recordPresent,
        public ?int $recordVersion,
        public ?CarbonImmutable $reviewDueAt,
    ) {}

    /**
     * @return array{real_money_enabled: bool, blocking_reasons: list<string>, environment_mode: string, record_present: bool, record_version: int|null, review_due_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'real_money_enabled' => $this->realMoneyEnabled,
            'blocking_reasons' => $this->blockingReasons,
            'environment_mode' => $this->environmentMode->value,
            'record_present' => $this->recordPresent,
            'record_version' => $this->recordVersion,
            'review_due_at' => $this->reviewDueAt?->toIso8601String(),
        ];
    }
}
