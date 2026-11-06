<?php

namespace App\Domain\GameProviders\Simulator;

use App\Domain\Games\Enums\GameCategory;
use InvalidArgumentException;

/**
 * Outcomes for the LOCAL SIMULATOR only. This is a development tool for the
 * demo user interface, not a certified random number generator or casino
 * game, and must never be used for anything with monetary value.
 */
class SimulatorRoundEngine
{
    /** Slot symbols with relative weights. */
    private const SYMBOLS = [
        'cherry' => 30,
        'bell' => 22,
        'bar' => 18,
        'heart' => 14,
        'diamond' => 9,
        'crown' => 5,
        'seven' => 2,
    ];

    /** Payout multipliers (x10) for 3, 4 and 5 matching symbols from the left. */
    private const PAYTABLE = [
        'cherry' => [5, 10, 20],
        'bell' => [10, 20, 40],
        'bar' => [15, 30, 60],
        'heart' => [20, 40, 80],
        'diamond' => [40, 80, 150],
        'crown' => [80, 150, 300],
        'seven' => [150, 300, 1000],
    ];

    private const RANKS = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

    private const SUITS = ['♠', '♥', '♦', '♣'];

    public function play(GameCategory $category, int $stake): SimulatorOutcome
    {
        if ($stake <= 0) {
            throw new InvalidArgumentException('Stake must be positive.');
        }

        return match ($category) {
            GameCategory::Slots => $this->slots($stake),
            GameCategory::Blackjack, GameCategory::Baccarat => $this->table($category, $stake),
            default => throw new InvalidArgumentException('The simulator does not support this category.'),
        };
    }

    private function slots(int $stake): SimulatorOutcome
    {
        $reels = [];
        for ($reel = 0; $reel < 5; $reel++) {
            $reels[] = [$this->symbol(), $this->symbol(), $this->symbol()];
        }

        $line = array_column($reels, 1);
        $first = $line[0];
        $run = 1;
        while ($run < 5 && $line[$run] === $first) {
            $run++;
        }

        $payout = $run >= 3 ? intdiv($stake * self::PAYTABLE[$first][$run - 3], 10) : 0;

        return new SimulatorOutcome($stake, $payout, $payout > 0 ? 'win' : 'lose', [
            'kind' => 'slots',
            'reels' => $reels,
            'winning_symbol' => $payout > 0 ? $first : null,
            'match_count' => $payout > 0 ? $run : 0,
        ]);
    }

    private function table(GameCategory $category, int $stake): SimulatorOutcome
    {
        $roll = random_int(1, 1000);

        [$result, $payout] = match (true) {
            $roll <= 440 => ['win', $stake * 2],
            $roll <= 520 => ['push', $stake],
            default => ['lose', 0],
        };

        return new SimulatorOutcome($stake, $payout, $result, [
            'kind' => $category->value,
            'player_cards' => [$this->card(), $this->card()],
            'dealer_cards' => [$this->card(), $this->card()],
        ]);
    }

    private function symbol(): string
    {
        $pick = random_int(1, array_sum(self::SYMBOLS));

        foreach (self::SYMBOLS as $symbol => $weight) {
            $pick -= $weight;
            if ($pick <= 0) {
                return $symbol;
            }
        }

        return 'cherry';
    }

    private function card(): string
    {
        return self::RANKS[random_int(0, 12)].self::SUITS[random_int(0, 3)];
    }
}
