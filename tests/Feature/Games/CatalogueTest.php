<?php

namespace Tests\Feature\Games;

use App\Domain\Games\Catalogue\GameCatalogue;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameProvider;
use Database\Seeders\LocalSimulatorCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LocalSimulatorCatalogueSeeder::class);
    }

    public function test_only_categories_with_a_playable_game_are_available(): void
    {
        $available = collect(app(GameCatalogue::class)->categories())
            ->mapWithKeys(fn (array $c): array => [$c['id'] => $c['available']])
            ->all();

        $this->assertSame([
            'slots' => true,
            'live_casino' => false,
            'blackjack' => true,
            'baccarat' => true,
            'poker' => false,
            'crash' => false,
        ], $available);
    }

    public function test_games_are_labelled_playable_demo_not_offered_or_coming_soon(): void
    {
        $catalogue = app(GameCatalogue::class);

        $this->assertSame('playable', $catalogue->status($this->game('candy-crown')));
        $this->assertSame('demo_not_offered', $catalogue->status($this->game('harbour-lights')));
        $this->assertSame('coming_soon', $catalogue->status($this->game('dodo-rocket')));
    }

    public function test_the_catalogue_page_lists_filters_and_searches(): void
    {
        $this->get(route('games.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('games/Index')
                ->where('games.total', 10)
                ->has('categories', 6));

        $this->get(route('games.index', ['category' => 'slots']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('games.total', 5)
                ->where('filters.category', 'slots'));

        $this->get(route('games.index', ['search' => 'ROYAL']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('games.total', 1)
                ->where('games.data.0.slug', 'royal-21'));
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $this->get(route('games.index', ['search' => '%']))
            ->assertInertia(fn (Assert $page) => $page->where('games.total', 0));
    }

    public function test_an_unknown_category_is_rejected(): void
    {
        $this->get(route('games.index', ['category' => 'roulette']))
            ->assertSessionHasErrors('category');
    }

    public function test_the_game_page_shows_a_listed_game(): void
    {
        $this->get(route('games.show', 'candy-crown'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('games/Show')
                ->where('game.name', 'Candy Crown')
                ->where('game.playable', true)
                ->where('game.demo_only', true)
                ->where('game.provider.is_simulator', true)
                ->where('game.simulation.volatility', 'low')
                ->where('game.simulation.rtp', 95.6)
                ->has('related'));

        // Each slot title plays with its own profile.
        $this->get(route('games.show', 'copper-comet'))
            ->assertInertia(fn (Assert $page) => $page->where('game.simulation.volatility', 'high'));
        $this->get(route('games.show', 'royal-21'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('game.simulation.volatility', null)
                ->where('game.simulation.rtp', 97.6));
    }

    public function test_disabled_games_are_hidden_everywhere(): void
    {
        DB::table('games')->where('slug', 'candy-crown')->update(['is_enabled' => false]);

        $this->get(route('games.show', 'candy-crown'))->assertNotFound();
        $this->get(route('games.index', ['search' => 'candy']))
            ->assertInertia(fn (Assert $page) => $page->where('games.total', 0));
    }

    public function test_a_suspended_provider_hides_its_games_and_categories(): void
    {
        DB::table('game_providers')->update(['status' => 'suspended']);

        $this->get(route('games.index'))
            ->assertInertia(fn (Assert $page) => $page->where('games.total', 0));

        $this->assertFalse(collect(app(GameCatalogue::class)->categories())->contains('available', true));
    }

    public function test_a_production_provider_is_never_playable_in_demo_mode(): void
    {
        DB::table('game_providers')->update(['environment' => 'production']);

        $game = $this->game('candy-crown');

        $this->assertFalse($game->isPlayable());
        $this->assertSame(0, Game::query()->playable()->count());
        $this->assertNotSame('playable', app(GameCatalogue::class)->status($game));
    }

    public function test_the_home_page_shows_featured_games_and_categories_from_the_catalogue(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('categories', 6)
                ->has('featuredGames', 5)
                ->where('featuredGames.0.slug', 'candy-crown'));
    }

    public function test_pages_never_expose_provider_secrets(): void
    {
        DB::table('game_providers')->update([
            'credentials_secret_reference' => 'vault://luxbet/simulator/credentials',
            'webhook_signing_key_reference' => 'vault://luxbet/simulator/webhook',
            'base_url' => 'https://sandbox.simulator.example.test',
        ]);

        foreach ([route('home'), route('games.index'), route('games.show', 'candy-crown')] as $url) {
            $body = (string) $this->get($url)->getContent();

            $this->assertStringNotContainsString('vault://', $body);
            $this->assertStringNotContainsString('sandbox.simulator.example.test', $body);
            $this->assertStringNotContainsString('provider_game_code', $body);
        }
    }

    public function test_the_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(LocalSimulatorCatalogueSeeder::class);

        $this->assertSame(1, GameProvider::query()->count());
        $this->assertSame(10, Game::query()->count());
    }

    private function game(string $slug): Game
    {
        return Game::query()->with('provider')->where('slug', $slug)->firstOrFail();
    }
}
