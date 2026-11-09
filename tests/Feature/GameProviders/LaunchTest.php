<?php

namespace Tests\Feature\GameProviders;

use App\Domain\Compliance\Enums\EnvironmentMode;
use App\Domain\GameProviders\Enums\GameSessionStatus;
use App\Domain\GameProviders\Exceptions\DemoSessionRefused;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\GameProviders\Support\ProviderSecrets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Concerns\InteractsWithSimulator;
use Tests\Fakes\HostileLaunchAdapter;
use Tests\TestCase;

class LaunchTest extends TestCase
{
    use InteractsWithSimulator, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSimulator();
    }

    public function test_a_player_can_launch_a_demo_game_and_reach_the_simulator_screen(): void
    {
        $player = $this->playerUser();

        $response = $this->actingAs($player)->post('/games/candy-crown/play');

        $session = GameSession::query()->sole();
        $this->assertSame(GameSessionStatus::Launched, $session->status);
        $this->assertSame('demo', $session->mode);
        $this->assertSame($player->id, $session->user_id);
        $this->assertEqualsWithDelta(30, now()->diffInMinutes($session->expires_at), 1);

        $launchUrl = $response->headers->get('Location');
        $this->assertIsString($launchUrl);
        $this->assertStringContainsString("/play/{$session->session_reference}", $launchUrl);
        $this->assertStringContainsString('signature=', $launchUrl);

        $this->actingAs($player)
            ->get($launchUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('games/Play')
                ->where('session.open', true)
                ->where('balance', 10_000)
                ->where('game.slug', 'candy-crown'));
    }

    public function test_the_play_screen_requires_a_valid_signature(): void
    {
        $player = $this->playerUser();
        $session = $this->launchSession($player);

        $this->actingAs($player)->get("/play/{$session->session_reference}")->assertForbidden();
    }

    public function test_another_player_cannot_open_someone_elses_session_even_with_the_link(): void
    {
        $owner = $this->playerUser();
        $this->actingAs($owner)->post('/games/candy-crown/play');
        $launchUrl = (string) $this->actingAs($owner)->post('/games/royal-21/play')->headers->get('Location');

        $this->actingAs($this->playerUser())->get($launchUrl)->assertNotFound();
    }

    public function test_guests_must_sign_in_to_play(): void
    {
        $this->post('/games/candy-crown/play')->assertRedirect(route('login'));
        $this->assertSame(0, GameSession::query()->count());
    }

    public function test_games_without_demo_access_cannot_be_launched(): void
    {
        $this->actingAs($this->playerUser())
            ->post('/games/harbour-lights/play')
            ->assertSessionHasErrors('play');

        $this->assertSame(0, GameSession::query()->count());
    }

    public function test_provider_downtime_fails_safely(): void
    {
        config(['luxbet.providers.simulator.available' => false]);
        $player = $this->playerUser();

        $this->actingAs($player)
            ->post('/games/candy-crown/play')
            ->assertSessionHasErrors(['play' => 'The game provider is not responding. Please try again in a few minutes.']);

        $session = GameSession::query()->sole();
        $this->assertSame(GameSessionStatus::Failed, $session->status);
        $this->assertSame(10_000, $player->demoWallets()->sole()->balance_minor);
    }

    public function test_the_game_launches_when_the_site_is_opened_on_another_local_host(): void
    {
        // A common local setup: APP_URL says localhost but the browser uses 127.0.0.1.
        config(['app.url' => 'http://localhost:8000']);
        $player = $this->playerUser();

        $response = $this->actingAs($player)->post('http://127.0.0.1:8000/games/candy-crown/play');

        $response->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStringStartsWith('http://127.0.0.1:8000/play/', (string) $response->headers->get('Location'));

        $this->actingAs($player)->get((string) $response->headers->get('Location'))->assertOk();
    }

    public function test_launch_urls_off_the_allowlist_are_refused(): void
    {
        config(['luxbet.providers.adapters.local-simulator' => HostileLaunchAdapter::class]);

        $response = $this->actingAs($this->playerUser())->post('/games/candy-crown/play');

        $response->assertSessionHasErrors('play');
        $this->assertStringNotContainsString('evil.example.test', (string) $response->headers->get('Location'));
        $this->assertSame(GameSessionStatus::Failed, GameSession::query()->sole()->status);
    }

    /** §11.6 (7) */
    public function test_a_production_provider_endpoint_is_rejected_in_demo_mode(): void
    {
        DB::table('game_providers')->update(['environment' => 'production']);

        $this->actingAs($this->playerUser())
            ->post('/games/candy-crown/play')
            ->assertSessionHasErrors('play');

        $this->assertSame(0, GameSession::query()->count());

        $this->expectException(DemoSessionRefused::class);
        $this->launchSession($this->playerUser());
    }

    /** §11.6 (8) */
    public function test_production_credentials_are_not_loaded_outside_production_mode(): void
    {
        $this->assertSame(EnvironmentMode::Demo->value, config('luxbet.environment_mode'));

        DB::table('game_providers')->update(['environment' => 'production']);

        $this->expectException(RuntimeException::class);
        app(ProviderSecrets::class)->webhookSecret($this->simulator()->refresh());
    }

    public function test_the_api_launch_ignores_foreign_return_urls(): void
    {
        $response = $this->actingAs($this->playerUser())
            ->postJson('/api/v1/games/candy-crown/demo-session', ['return_url' => 'https://evil.example.test/phish'])
            ->assertCreated()
            ->assertJsonPath('data.mode', 'demo');

        $launchUrl = (string) $response->json('data.launch_url');
        parse_str((string) parse_url($launchUrl, PHP_URL_QUERY), $query);

        $this->assertSame(url('/games'), $query['return'] ?? null);
        $this->assertStringNotContainsString('evil.example.test', $launchUrl);
    }

    public function test_players_only_see_their_own_sessions(): void
    {
        $owner = $this->playerUser();
        $session = $this->launchSession($owner);

        $this->actingAs($owner)
            ->getJson('/api/v1/game-sessions')
            ->assertOk()
            ->assertJsonPath('data.0.session_id', $session->session_reference)
            ->assertJsonPath('data.0.mode', 'demo');

        $this->actingAs($this->playerUser())
            ->getJson("/api/v1/game-sessions/{$session->session_reference}")
            ->assertNotFound();

        $this->actingAs($this->playerUser())
            ->getJson('/api/v1/game-sessions')
            ->assertJsonCount(0, 'data');
    }

    public function test_expired_sessions_are_closed_by_the_scheduled_command(): void
    {
        $session = $this->launchSession($this->playerUser());

        $this->travel(31)->minutes();
        $this->artisan('luxbet:expire-game-sessions')->assertSuccessful();

        $this->assertSame(GameSessionStatus::Expired, $session->refresh()->status);
        $this->assertFalse($session->isOpen());
    }

    public function test_players_can_end_a_session(): void
    {
        $player = $this->playerUser();
        $session = $this->launchSession($player);

        $this->actingAs($player)
            ->post("/play/{$session->session_reference}/end", ['return' => 'https://evil.example.test'])
            ->assertRedirect(url('/games'));

        $this->assertSame(GameSessionStatus::Completed, $session->refresh()->status);
    }

    public function test_playing_many_rounds_does_not_block_starting_another_game(): void
    {
        $player = $this->playerUser();
        $session = $this->launchSession($player);

        for ($i = 0; $i < 25; $i++) {
            $this->actingAs($player)
                ->postJson("/api/v1/game-sessions/{$session->session_reference}/simulator-rounds", ['stake' => 10])
                ->assertOk();
        }

        // Each route has its own rate-limit counter, so 25 rounds do not use
        // up the 20-per-minute allowance for starting games.
        $this->actingAs($player)->post('/games/royal-21/play')->assertRedirect();
        $this->actingAs($player)->post('/games/candy-crown/favourite')->assertRedirect();
    }
}
