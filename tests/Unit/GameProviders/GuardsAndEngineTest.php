<?php

namespace Tests\Unit\GameProviders;

use App\Domain\GameProviders\Exceptions\DemoSessionRefused;
use App\Domain\GameProviders\Simulator\SimulatorRoundEngine;
use App\Domain\GameProviders\Simulator\SimulatorSigner;
use App\Domain\GameProviders\Support\LaunchUrlGuard;
use App\Domain\GameProviders\Support\ReturnUrlGuard;
use App\Domain\Games\Enums\GameCategory;
use InvalidArgumentException;
use Tests\TestCase;

class GuardsAndEngineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://luxbet.example.test', 'luxbet.providers.launch_hosts' => ['games.sandbox-provider.example']]);
    }

    public function test_launch_urls_must_be_on_the_allowlist(): void
    {
        $guard = new LaunchUrlGuard;

        $guard->assertAllowed('https://luxbet.example.test/play/gs_abc?signature=x');
        $guard->assertAllowed('https://games.sandbox-provider.example/launch?token=1');

        foreach ([
            'https://evil.example.test/launch',
            'http://games.sandbox-provider.example/launch',
            'https://user:pass@games.sandbox-provider.example/launch',
            'javascript:alert(1)',
            '/relative/path',
        ] as $url) {
            try {
                $guard->assertAllowed($url);
                $this->fail("{$url} should be refused.");
            } catch (DemoSessionRefused) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_return_urls_fall_back_to_the_catalogue_unless_same_origin(): void
    {
        $guard = new ReturnUrlGuard;
        $fallback = url('/games');

        $this->assertSame('https://luxbet.example.test/games/candy-crown', $guard->resolve('https://luxbet.example.test/games/candy-crown'));
        $this->assertSame($fallback, $guard->resolve('https://evil.example.test/'));
        $this->assertSame($fallback, $guard->resolve('//evil.example.test'));
        $this->assertSame($fallback, $guard->resolve('https://luxbet.example.test.evil.test/'));
        $this->assertSame($fallback, $guard->resolve(null));
    }

    public function test_the_signer_is_deterministic_and_secret_dependent(): void
    {
        $a = SimulatorSigner::sign('{"x":1}', 1_700_000_000, 'secret');

        $this->assertSame($a, SimulatorSigner::sign('{"x":1}', 1_700_000_000, 'secret'));
        $this->assertNotSame($a, SimulatorSigner::sign('{"x":1}', 1_700_000_000, 'other'));
        $this->assertNotSame($a, SimulatorSigner::sign('{"x":2}', 1_700_000_000, 'secret'));
        $this->assertStringStartsWith('v1=', $a);
    }

    public function test_simulator_outcomes_stay_within_bounds(): void
    {
        $engine = new SimulatorRoundEngine;

        for ($i = 0; $i < 300; $i++) {
            $slots = $engine->play(GameCategory::Slots, 100);
            $this->assertGreaterThanOrEqual(0, $slots->payout);
            $this->assertLessThanOrEqual(100 * 100, $slots->payout);
            $this->assertCount(5, $slots->display['reels']);

            $table = $engine->play(GameCategory::Baccarat, 100);
            $this->assertContains($table->payout, [0, 100, 200]);
        }
    }

    public function test_the_simulator_refuses_unsupported_categories(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SimulatorRoundEngine)->play(GameCategory::Crash, 100);
    }
}
