<?php

namespace Tests\Feature\Player;

use App\Domain\Games\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithSimulator;
use Tests\TestCase;

class FavouritesTest extends TestCase
{
    use InteractsWithSimulator, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSimulator();
    }

    public function test_guests_cannot_favourite_games(): void
    {
        $this->post('/games/candy-crown/favourite')->assertRedirect(route('login'));
        $this->get('/favourites')->assertRedirect(route('login'));
    }

    public function test_players_can_add_and_remove_a_favourite(): void
    {
        $player = $this->playerUser();
        $game = Game::query()->where('slug', 'candy-crown')->firstOrFail();

        $this->actingAs($player)->post('/games/candy-crown/favourite')->assertRedirect();
        // Adding twice is harmless.
        $this->actingAs($player)->post('/games/candy-crown/favourite')->assertRedirect();

        $this->assertDatabaseCount('favourite_games', 1);
        $this->assertDatabaseHas('favourite_games', ['user_id' => $player->id, 'game_id' => $game->id]);

        $this->actingAs($player)
            ->get('/games/candy-crown')
            ->assertInertia(fn (Assert $page) => $page->where('isFavourite', true));

        $this->actingAs($player)->delete('/games/candy-crown/favourite')->assertRedirect();

        $this->assertDatabaseCount('favourite_games', 0);
    }

    public function test_the_favourites_page_lists_only_the_players_own_favourites(): void
    {
        $player = $this->playerUser();
        $other = $this->playerUser();

        $this->actingAs($player)->post('/games/candy-crown/favourite');
        $this->actingAs($other)->post('/games/island-treasure/favourite');

        $this->actingAs($player)
            ->get('/favourites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Favourites')
                ->has('games', 1)
                ->where('games.0.slug', 'candy-crown'));
    }

    public function test_unknown_or_hidden_games_cannot_be_favourited(): void
    {
        $player = $this->playerUser();

        $this->actingAs($player)->post('/games/no-such-game/favourite')->assertNotFound();

        Game::query()->where('slug', 'candy-crown')->update(['is_enabled' => false]);

        $this->actingAs($player)->post('/games/candy-crown/favourite')->assertNotFound();
    }
}
