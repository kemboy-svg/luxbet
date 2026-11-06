<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithSimulator;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithSimulator, RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_a_new_player_sees_their_allowance_and_empty_activity()
    {
        $this->actingAs($this->playerUser())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('balance', (int) config('luxbet.demo_wallet.initial_allowance'))
                ->has('daily', 7)
                ->where('week.sessions', 0)
                ->where('recentSessions', [])
                ->where('recentGames', []));
    }

    public function test_the_dashboard_and_history_summarise_demo_play()
    {
        $this->seedSimulator();
        $player = $this->playerUser();
        $session = $this->launchSession($player);

        $this->actingAs($player)
            ->postJson("/api/v1/game-sessions/{$session->session_reference}/simulator-rounds", ['stake' => 100])
            ->assertOk();

        $this->actingAs($player)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('week.sessions', 1)
                ->where('week.rounds', 1)
                ->where('recentGames.0.slug', 'candy-crown')
                ->where('recentSessions.0.reference', $session->session_reference)
                ->where('recentSessions.0.rounds', 1)
                ->where('recentSessions.0.staked', 100));

        $this->actingAs($player)
            ->get(route('play-history.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SessionHistory')
                ->where('sessions.total', 1)
                ->where('sessions.data.0.game.slug', 'candy-crown'));
    }

    public function test_players_only_see_their_own_sessions()
    {
        $this->seedSimulator();
        $this->launchSession($this->playerUser());

        $this->actingAs($this->playerUser())
            ->get(route('play-history.index'))
            ->assertInertia(fn (Assert $page) => $page->where('sessions.total', 0));
    }
}
