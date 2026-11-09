<?php

namespace Tests\Unit\GameProviders;

use App\Domain\GameProviders\Simulator\SimulatorRoundEngine;
use App\Domain\Games\Enums\GameCategory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Plays many simulated rounds and checks each game returns roughly what its
 * design says, so the demo neither drains credits nor showers them.
 * Tolerances are several standard errors wide to keep the test stable.
 */
class SimulatorReturnTest extends TestCase
{
    /**
     * @return array<string, array{0: GameCategory, 1: ?string, 2: int, 3: float, 4: float, 5: float}>
     *                                                                                                 category, game code, rounds, design RTP %, RTP tolerance, minimum hit rate %
     */
    public static function games(): array
    {
        return [
            'slots, low volatility' => [GameCategory::Slots, 'sim-candy-crown', 120_000, 95.6, 4.0, 25.0],
            'slots, medium volatility' => [GameCategory::Slots, 'sim-island-treasure', 120_000, 94.8, 4.5, 25.0],
            'slots, high volatility' => [GameCategory::Slots, 'sim-copper-comet', 120_000, 95.6, 6.0, 22.0],
            'blackjack' => [GameCategory::Blackjack, null, 60_000, 97.6, 2.0, 40.0],
            'baccarat' => [GameCategory::Baccarat, null, 60_000, 98.8, 2.0, 40.0],
        ];
    }

    #[DataProvider('games')]
    public function test_return_and_win_frequency_stay_near_design(GameCategory $category, ?string $code, int $rounds, float $rtp, float $tolerance, float $minHitRate): void
    {
        $engine = new SimulatorRoundEngine;
        $staked = 0;
        $paid = 0;
        $wins = 0;

        for ($i = 0; $i < $rounds; $i++) {
            $outcome = $engine->play($category, 100, $code);
            $staked += 100;
            $paid += $outcome->payout;
            $wins += $outcome->payout > 0 ? 1 : 0;
        }

        $this->assertEqualsWithDelta($rtp, $paid / $staked * 100, $tolerance);
        $this->assertGreaterThanOrEqual($minHitRate, $wins / $rounds * 100);
        $this->assertSame($rtp, SimulatorRoundEngine::designRtp($category, $code));
    }

    public function test_wilds_substitute_and_runs_count_from_the_left(): void
    {
        $evaluate = new ReflectionMethod(SimulatorRoundEngine::class, 'evaluateLine');
        $pays = SimulatorRoundEngine::SLOT_PROFILES['medium']['pays'];
        $engine = new SimulatorRoundEngine;

        $this->assertSame(
            ['symbol' => 'bell', 'count' => 4, 'pay' => $pays['bell'][1]],
            $evaluate->invoke($engine, ['bell', 'crown', 'bell', 'bell', 'cherry'], $pays),
        );
        $this->assertSame(
            ['symbol' => 'seven', 'count' => 3, 'pay' => $pays['seven'][0]],
            $evaluate->invoke($engine, ['crown', 'crown', 'seven', 'bar', 'bar'], $pays),
        );
        $this->assertSame(
            ['symbol' => 'crown', 'count' => 5, 'pay' => $pays['crown'][2]],
            $evaluate->invoke($engine, ['crown', 'crown', 'crown', 'crown', 'crown'], $pays),
        );
        $this->assertNull($evaluate->invoke($engine, ['cherry', 'bell', 'cherry', 'cherry', 'cherry'], $pays));
    }

    public function test_slot_payouts_match_their_winning_lines_and_tiers(): void
    {
        $engine = new SimulatorRoundEngine;

        for ($i = 0; $i < 3_000; $i++) {
            $outcome = $engine->play(GameCategory::Slots, 250, 'sim-copper-comet');
            $display = $outcome->display;

            $this->assertSame($outcome->payout > 0, $display['lines'] !== []);
            $this->assertLessThanOrEqual(250 * SimulatorRoundEngine::MAX_MULTIPLIER, $outcome->payout);

            $expectedTier = match (true) {
                $outcome->payout === 0 => 'none',
                $outcome->payout < 250 => 'small',
                $outcome->payout < 1250 => 'win',
                $outcome->payout < 5000 => 'big',
                default => 'mega',
            };
            $this->assertSame($expectedTier, $display['tier']);
        }
    }

    public function test_blackjack_results_agree_with_the_cards(): void
    {
        $engine = new SimulatorRoundEngine;

        for ($i = 0; $i < 5_000; $i++) {
            $outcome = $engine->play(GameCategory::Blackjack, 100);
            ['player_total' => $player, 'dealer_total' => $dealer] = $outcome->display;
            $playerNatural = $player === 21 && count($outcome->display['player_cards']) === 2;
            $dealerNatural = $dealer === 21 && count($outcome->display['dealer_cards']) === 2;

            match ($outcome->result) {
                'win' => $this->assertTrue(
                    ($playerNatural && ! $dealerNatural && $outcome->payout === 250)
                    || ($player <= 21 && ($dealer > 21 || $player > $dealer) && $outcome->payout === 200),
                    json_encode($outcome->display, JSON_UNESCAPED_UNICODE) ?: '',
                ),
                'push' => $this->assertTrue($player === $dealer && $outcome->payout === 100),
                'lose' => $this->assertTrue(
                    $outcome->payout === 0 && ($player > 21 || $dealerNatural || ($dealer <= 21 && $dealer > $player)),
                    json_encode($outcome->display, JSON_UNESCAPED_UNICODE) ?: '',
                ),
                default => $this->fail('Unexpected result '.$outcome->result),
            };

            // The dealer only draws until 17 when the player has not bust.
            if ($player <= 21 && ! $playerNatural && ! $dealerNatural) {
                $this->assertGreaterThanOrEqual(17, $dealer);
            }
        }
    }

    public function test_baccarat_results_agree_with_the_cards(): void
    {
        $engine = new SimulatorRoundEngine;

        for ($i = 0; $i < 5_000; $i++) {
            $outcome = $engine->play(GameCategory::Baccarat, 100);
            ['player_total' => $player, 'dealer_total' => $banker] = $outcome->display;

            $this->assertLessThanOrEqual(3, count($outcome->display['player_cards']));
            $this->assertLessThanOrEqual(3, count($outcome->display['dealer_cards']));
            $this->assertSame(match (true) {
                $player > $banker => 'win',
                $player === $banker => 'push',
                default => 'lose',
            }, $outcome->result);
        }
    }
}
