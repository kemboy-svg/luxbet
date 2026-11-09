<?php

namespace App\Domain\GameProviders\Simulator;

use App\Domain\Games\Enums\GameCategory;
use InvalidArgumentException;

/**
 * Outcomes for the LOCAL SIMULATOR only. This is a development tool for the
 * demo user interface, not a certified random number generator or casino
 * game, and must never be used for anything with monetary value.
 *
 * Return-to-player figures are design targets for an enjoyable demo, checked
 * by tests/Unit/GameProviders/SimulatorReturnTest.php and documented in
 * docs/simulator-games.md:
 *
 * - Slots: 5 reels x 3 rows, 5 paylines, crown is wild. About 95.5% return,
 *   a win on roughly 3 spins in 10. Each title uses a volatility profile.
 * - Blackjack: dealt and scored by the rules (infinite shoe, dealer stands on
 *   all 17s, blackjack pays 3:2, the player follows a simple basic strategy
 *   with no doubles or splits). About 97.5% return.
 * - Baccarat: a bet on Player, Punto Banco drawing rules, ties push.
 *   About 98.8% return.
 */
class SimulatorRoundEngine
{
    public const WILD = 'crown';

    /** Cap on one round's payout, as a multiple of the stake. */
    public const MAX_MULTIPLIER = 500;

    /**
     * Paylines as the row (0 top, 1 middle, 2 bottom) used on each reel.
     *
     * @var list<list<int>>
     */
    public const PAYLINES = [
        [1, 1, 1, 1, 1],
        [0, 0, 0, 0, 0],
        [2, 2, 2, 2, 2],
        [0, 1, 2, 1, 0],
        [2, 1, 0, 1, 2],
    ];

    /**
     * Symbol weights and paytables per volatility profile. Pays are x10 of
     * the line bet (stake / 5) for 3, 4 and 5 matching symbols from the left.
     *
     * @var array<string, array{label: string, rtp: float, weights: array<string, int>, pays: array<string, array{int, int, int}>}>
     */
    public const SLOT_PROFILES = [
        'low' => [
            'label' => 'Low volatility: frequent, smaller wins',
            'rtp' => 95.6,
            'weights' => ['cherry' => 27, 'bell' => 22, 'bar' => 18, 'heart' => 13, 'diamond' => 9, 'seven' => 5, 'crown' => 6],
            'pays' => [
                'cherry' => [50, 100, 250], 'bell' => [70, 150, 350], 'bar' => [80, 200, 400],
                'heart' => [120, 300, 600], 'diamond' => [200, 500, 1000], 'seven' => [400, 1000, 4000],
                'crown' => [500, 2000, 10000],
            ],
        ],
        'medium' => [
            'label' => 'Medium volatility: balanced wins',
            'rtp' => 94.8,
            'weights' => ['cherry' => 28, 'bell' => 22, 'bar' => 17, 'heart' => 13, 'diamond' => 9, 'seven' => 6, 'crown' => 5],
            'pays' => [
                'cherry' => [40, 100, 250], 'bell' => [60, 150, 400], 'bar' => [100, 250, 600],
                'heart' => [160, 400, 1000], 'diamond' => [250, 600, 1500], 'seven' => [600, 1500, 6000],
                'crown' => [1000, 4000, 20000],
            ],
        ],
        'high' => [
            'label' => 'High volatility: rarer, bigger wins',
            'rtp' => 95.6,
            'weights' => ['cherry' => 30, 'bell' => 24, 'bar' => 18, 'heart' => 13, 'diamond' => 8, 'seven' => 4, 'crown' => 3],
            'pays' => [
                'cherry' => [40, 100, 300], 'bell' => [80, 200, 500], 'bar' => [125, 300, 800],
                'heart' => [200, 600, 1600], 'diamond' => [400, 1000, 3000], 'seven' => [1000, 4000, 20000],
                'crown' => [2000, 10000, 50000],
            ],
        ],
    ];

    /**
     * Which profile each simulator slot uses (by game code). Others: medium.
     *
     * @var array<string, string>
     */
    public const GAME_PROFILES = [
        'sim-candy-crown' => 'low',
        'sim-island-treasure' => 'medium',
        'sim-night-orchard' => 'medium',
        'sim-copper-comet' => 'high',
        'sim-harbour-lights' => 'low',
    ];

    public const TABLE_RTP = [
        'blackjack' => 97.6,
        'baccarat' => 98.8,
    ];

    private const RANKS = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

    private const SUITS = ['♠', '♥', '♦', '♣'];

    public function play(GameCategory $category, int $stake, ?string $gameCode = null): SimulatorOutcome
    {
        if ($stake <= 0) {
            throw new InvalidArgumentException('Stake must be positive.');
        }

        return match ($category) {
            GameCategory::Slots => $this->slots($stake, self::profileFor($gameCode)),
            GameCategory::Blackjack => $this->blackjack($stake),
            GameCategory::Baccarat => $this->baccarat($stake),
            default => throw new InvalidArgumentException('The simulator does not support this category.'),
        };
    }

    public static function profileFor(?string $gameCode): string
    {
        return self::GAME_PROFILES[$gameCode] ?? 'medium';
    }

    /**
     * Design return-to-player for a game, for display on the game page.
     */
    public static function designRtp(GameCategory $category, ?string $gameCode): ?float
    {
        return match ($category) {
            GameCategory::Slots => self::SLOT_PROFILES[self::profileFor($gameCode)]['rtp'],
            GameCategory::Blackjack => self::TABLE_RTP['blackjack'],
            GameCategory::Baccarat => self::TABLE_RTP['baccarat'],
            default => null,
        };
    }

    // ----------------------------------------------------------------- slots

    private function slots(int $stake, string $profileName): SimulatorOutcome
    {
        $profile = self::SLOT_PROFILES[$profileName];

        $reels = [];
        for ($reel = 0; $reel < 5; $reel++) {
            $reels[] = [
                $this->symbol($profile['weights']),
                $this->symbol($profile['weights']),
                $this->symbol($profile['weights']),
            ];
        }

        $wins = [];
        $tenths = 0;
        foreach (self::PAYLINES as $index => $rows) {
            $line = [];
            foreach ($rows as $reel => $row) {
                $line[] = $reels[$reel][$row];
            }

            $win = $this->evaluateLine($line, $profile['pays']);
            if ($win !== null) {
                $wins[] = ['line' => $index, 'symbol' => $win['symbol'], 'count' => $win['count']];
                $tenths += $win['pay'];
            }
        }

        // Pays are tenths of the line bet, and the line bet is stake / 5.
        $payout = min(intdiv($stake * $tenths, 50), $stake * self::MAX_MULTIPLIER);
        $best = $wins === [] ? null : $wins[0];

        return new SimulatorOutcome($stake, $payout, $payout > 0 ? 'win' : 'lose', [
            'kind' => 'slots',
            'profile' => $profileName,
            'reels' => $reels,
            'lines' => $wins,
            'winning_symbol' => $best['symbol'] ?? null,
            'match_count' => $best['count'] ?? 0,
            'tier' => $this->tier($stake, $payout),
        ]);
    }

    /**
     * Best win on one payline: a run from the left of one symbol, with wilds
     * standing in for it, or a run of wilds on their own.
     *
     * @param  list<string>  $line
     * @param  array<string, array{int, int, int}>  $pays
     * @return array{symbol: string, count: int, pay: int}|null
     */
    private function evaluateLine(array $line, array $pays): ?array
    {
        $target = self::WILD;
        foreach ($line as $symbol) {
            if ($symbol !== self::WILD) {
                $target = $symbol;
                break;
            }
        }

        $run = 0;
        foreach ($line as $symbol) {
            if ($symbol !== $target && $symbol !== self::WILD) {
                break;
            }
            $run++;
        }

        $wildRun = 0;
        foreach ($line as $symbol) {
            if ($symbol !== self::WILD) {
                break;
            }
            $wildRun++;
        }

        $best = null;
        if ($run >= 3) {
            $best = ['symbol' => $target, 'count' => $run, 'pay' => $pays[$target][$run - 3]];
        }
        if ($wildRun >= 3 && ($best === null || $pays[self::WILD][$wildRun - 3] > $best['pay'])) {
            $best = ['symbol' => self::WILD, 'count' => $wildRun, 'pay' => $pays[self::WILD][$wildRun - 3]];
        }

        return $best;
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function symbol(array $weights): string
    {
        $pick = random_int(1, array_sum($weights));

        foreach ($weights as $symbol => $weight) {
            $pick -= $weight;
            if ($pick <= 0) {
                return $symbol;
            }
        }

        return array_key_first($weights);
    }

    /**
     * How the play screen celebrates a result: a payout below the stake is a
     * "small" win, never presented as beating the stake.
     */
    private function tier(int $stake, int $payout): string
    {
        return match (true) {
            $payout === 0 => 'none',
            $payout < $stake => 'small',
            $payout < $stake * 5 => 'win',
            $payout < $stake * 20 => 'big',
            default => 'mega',
        };
    }

    // ------------------------------------------------------------- blackjack

    private function blackjack(int $stake): SimulatorOutcome
    {
        $player = [$this->card(), $this->card()];
        $dealer = [$this->card(), $this->card()];

        $playerNatural = $this->blackjackTotal($player)['total'] === 21;
        $dealerNatural = $this->blackjackTotal($dealer)['total'] === 21;

        if ($playerNatural || $dealerNatural) {
            [$result, $payout, $note] = match (true) {
                $playerNatural && $dealerNatural => ['push', $stake, 'Both have blackjack'],
                $playerNatural => ['win', intdiv($stake * 5, 2), 'Blackjack! Pays 3:2'],
                default => ['lose', 0, 'Dealer has blackjack'],
            };

            return $this->tableOutcome('blackjack', $stake, $payout, $result, $note, $player, $dealer);
        }

        while ($this->shouldHit($player, $dealer[0])) {
            $player[] = $this->card();
        }
        $playerTotal = $this->blackjackTotal($player)['total'];

        if ($playerTotal > 21) {
            return $this->tableOutcome('blackjack', $stake, 0, 'lose', 'You bust', $player, $dealer);
        }

        while ($this->blackjackTotal($dealer)['total'] < 17) {
            $dealer[] = $this->card();
        }
        $dealerTotal = $this->blackjackTotal($dealer)['total'];

        [$result, $payout, $note] = match (true) {
            $dealerTotal > 21 => ['win', $stake * 2, 'Dealer busts'],
            $playerTotal > $dealerTotal => ['win', $stake * 2, "{$playerTotal} beats {$dealerTotal}"],
            $playerTotal === $dealerTotal => ['push', $stake, "Push on {$playerTotal}"],
            default => ['lose', 0, "Dealer's {$dealerTotal} beats {$playerTotal}"],
        };

        return $this->tableOutcome('blackjack', $stake, $payout, $result, $note, $player, $dealer);
    }

    /**
     * Simple basic strategy without doubling or splitting.
     *
     * @param  list<string>  $hand
     */
    private function shouldHit(array $hand, string $dealerUp): bool
    {
        ['total' => $total, 'soft' => $soft] = $this->blackjackTotal($hand);
        $up = $this->blackjackValue($dealerUp);

        if ($soft) {
            return $total <= 17 || ($total === 18 && $up >= 9);
        }

        return match (true) {
            $total <= 11 => true,
            $total === 12 => $up < 4 || $up > 6,
            $total <= 16 => $up < 2 || $up > 6,
            default => false,
        };
    }

    /**
     * @param  list<string>  $hand
     * @return array{total: int, soft: bool}
     */
    private function blackjackTotal(array $hand): array
    {
        $total = 0;
        $aces = 0;
        foreach ($hand as $card) {
            $value = $this->blackjackValue($card);
            $total += $value;
            $aces += $value === 11 ? 1 : 0;
        }

        while ($total > 21 && $aces > 0) {
            $total -= 10;
            $aces--;
        }

        return ['total' => $total, 'soft' => $aces > 0];
    }

    private function blackjackValue(string $card): int
    {
        $rank = $this->rank($card);

        return match (true) {
            $rank === 'A' => 11,
            in_array($rank, ['10', 'J', 'Q', 'K'], true) => 10,
            default => (int) $rank,
        };
    }

    // -------------------------------------------------------------- baccarat

    private function baccarat(int $stake): SimulatorOutcome
    {
        $player = [$this->card(), $this->card()];
        $banker = [$this->card(), $this->card()];

        $playerScore = $this->baccaratScore($player);
        $bankerScore = $this->baccaratScore($banker);

        if ($playerScore < 8 && $bankerScore < 8) {
            $playerThird = null;

            if ($playerScore <= 5) {
                $playerThird = $this->card();
                $player[] = $playerThird;
            }

            if ($this->bankerDraws($bankerScore, $playerThird)) {
                $banker[] = $this->card();
            }

            $playerScore = $this->baccaratScore($player);
            $bankerScore = $this->baccaratScore($banker);
        }

        [$result, $payout, $note] = match (true) {
            $playerScore > $bankerScore => ['win', $stake * 2, "Player {$playerScore} beats Banker {$bankerScore}"],
            $playerScore === $bankerScore => ['push', $stake, "Tie on {$playerScore}"],
            default => ['lose', 0, "Banker {$bankerScore} beats Player {$playerScore}"],
        };

        return $this->tableOutcome('baccarat', $stake, $payout, $result, $note, $player, $banker);
    }

    /**
     * Punto Banco third-card rule for the banker.
     */
    private function bankerDraws(int $bankerScore, ?string $playerThird): bool
    {
        if ($playerThird === null) {
            return $bankerScore <= 5;
        }

        $third = $this->baccaratValue($playerThird);

        return match ($bankerScore) {
            0, 1, 2 => true,
            3 => $third !== 8,
            4 => $third >= 2 && $third <= 7,
            5 => $third >= 4 && $third <= 7,
            6 => $third === 6 || $third === 7,
            default => false,
        };
    }

    /**
     * @param  list<string>  $hand
     */
    private function baccaratScore(array $hand): int
    {
        return array_sum(array_map(fn (string $card): int => $this->baccaratValue($card), $hand)) % 10;
    }

    private function baccaratValue(string $card): int
    {
        $rank = $this->rank($card);

        return match (true) {
            $rank === 'A' => 1,
            in_array($rank, ['10', 'J', 'Q', 'K'], true) => 0,
            default => (int) $rank,
        };
    }

    // ---------------------------------------------------------------- shared

    /**
     * @param  list<string>  $player
     * @param  list<string>  $dealer
     */
    private function tableOutcome(string $kind, int $stake, int $payout, string $result, string $note, array $player, array $dealer): SimulatorOutcome
    {
        $blackjack = $kind === 'blackjack';

        return new SimulatorOutcome($stake, $payout, $result, [
            'kind' => $kind,
            'player_cards' => $player,
            'dealer_cards' => $dealer,
            'player_total' => $blackjack ? $this->blackjackTotal($player)['total'] : $this->baccaratScore($player),
            'dealer_total' => $blackjack ? $this->blackjackTotal($dealer)['total'] : $this->baccaratScore($dealer),
            'dealer_label' => $blackjack ? 'Dealer' : 'Banker',
            'player_label' => $blackjack ? 'You' : 'Player',
            'note' => $note,
            'tier' => $result === 'push' ? 'none' : $this->tier($stake, $payout),
        ]);
    }

    /**
     * One card from an infinite shoe (each card independent and uniform).
     */
    private function card(): string
    {
        return self::RANKS[random_int(0, 12)].self::SUITS[random_int(0, 3)];
    }

    private function rank(string $card): string
    {
        return mb_substr($card, 0, -1);
    }
}
