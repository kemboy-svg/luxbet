<?php

namespace App\Domain\GameProviders\Simulator;

final readonly class SimulatorOutcome
{
    /**
     * @param  array<string, mixed>  $display  what the play screen shows (reels, cards, result)
     */
    public function __construct(
        public int $stake,
        public int $payout,
        public string $result,
        public array $display,
    ) {}
}
