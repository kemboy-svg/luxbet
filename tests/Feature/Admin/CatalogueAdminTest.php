<?php

namespace Tests\Feature\Admin;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Games\Enums\ProviderStatus;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameProvider;
use App\Domain\Identity\Authorization\Role;
use Database\Seeders\LocalSimulatorCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogueAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LocalSimulatorCatalogueSeeder::class);
    }

    public function test_game_managers_can_disable_a_game_and_it_is_audited(): void
    {
        $manager = $this->staffUser(Role::GameManager);
        $game = $this->game('candy-crown');

        $this->actingAs($manager)
            ->patch("/admin/games/{$game->id}", ['is_enabled' => false])
            ->assertSessionHasNoErrors();

        $this->assertFalse($game->refresh()->is_enabled);
        $this->get(route('games.show', 'candy-crown'))->assertNotFound();

        $log = AuditLog::query()->where('action', 'game.updated')->sole();
        $this->assertSame($manager->id, $log->actor_user_id);
        $this->assertEquals(['is_enabled' => ['from' => true, 'to' => false]], $log->metadata['changes']);
    }

    public function test_game_managers_can_reorder_games(): void
    {
        $game = $this->game('royal-21');

        $this->actingAs($this->staffUser(Role::GameManager))
            ->patch("/admin/games/{$game->id}", ['sort_order' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $game->refresh()->sort_order);
    }

    public function test_game_updates_cannot_touch_unlisted_fields(): void
    {
        $game = $this->game('harbour-lights');

        $this->actingAs($this->staffUser(Role::GameManager))
            ->patch("/admin/games/{$game->id}", ['supports_demo' => true, 'slug' => 'hacked', 'provider_id' => null]);

        $game->refresh();
        $this->assertFalse($game->supports_demo);
        $this->assertSame('harbour-lights', $game->slug);
    }

    public function test_only_catalogue_staff_can_open_or_change_the_catalogue(): void
    {
        $game = $this->game('candy-crown');

        foreach ([Role::SupportAgent, Role::FinanceViewer, Role::ComplianceOfficer] as $role) {
            $staff = $this->staffUser($role);

            $this->actingAs($staff)->get('/admin/games')->assertForbidden();
            $this->actingAs($staff)->patch("/admin/games/{$game->id}", ['is_enabled' => false])->assertForbidden();
        }

        $this->actingAs($this->playerUser())->get('/admin/games')->assertForbidden();
        $this->assertTrue($game->refresh()->is_enabled);
    }

    public function test_the_admin_games_page_lists_every_game_with_its_public_status(): void
    {
        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->get('/admin/games')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/games/Index')
                ->has('games', 10)
                ->where('canManage', true));
    }

    public function test_provider_status_changes_are_audited_and_take_effect(): void
    {
        $manager = $this->staffUser(Role::GameManager);
        $provider = $this->provider();

        $this->actingAs($manager)
            ->patch("/admin/providers/{$provider->id}/status", ['status' => 'suspended', 'reason' => 'Simulator maintenance'])
            ->assertSessionHasNoErrors();

        $this->assertSame(ProviderStatus::Suspended, $provider->refresh()->status);
        $this->assertSame(0, Game::query()->playable()->count());

        $log = AuditLog::query()->where('action', 'provider.status_changed')->sole();
        $this->assertSame('Simulator maintenance', $log->reason);
        $this->assertEquals(['from' => 'sandbox', 'to' => 'suspended'], $log->metadata);
    }

    public function test_providers_cannot_be_made_active_or_switched_to_production(): void
    {
        $provider = $this->provider();

        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->patch("/admin/providers/{$provider->id}/status", ['status' => 'active', 'reason' => 'Go live attempt'])
            ->assertSessionHasErrors('status');

        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->patch("/admin/providers/{$provider->id}/status", ['status' => 'sandbox', 'environment' => 'production', 'reason' => 'Environment switch attempt']);

        $provider->refresh();
        $this->assertSame(ProviderStatus::Sandbox, $provider->status);
        $this->assertSame('sandbox', $provider->environment->value);
    }

    public function test_provider_changes_need_a_reason_and_permission(): void
    {
        $provider = $this->provider();

        $this->actingAs($this->staffUser(Role::GameManager))
            ->patch("/admin/providers/{$provider->id}/status", ['status' => 'suspended', 'reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($this->staffUser(Role::SupportAgent))
            ->patch("/admin/providers/{$provider->id}/status", ['status' => 'suspended', 'reason' => 'No permission'])
            ->assertForbidden();

        $this->assertSame(ProviderStatus::Sandbox, $provider->refresh()->status);
    }

    public function test_the_providers_page_never_shows_secrets(): void
    {
        DB::table('game_providers')->update([
            'credentials_secret_reference' => 'vault://luxbet/simulator/credentials',
            'base_url' => 'https://sandbox.simulator.example.test',
        ]);

        $body = (string) $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->get('/admin/providers')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('vault://', $body);
        $this->assertStringNotContainsString('example.test', $body);
    }

    private function game(string $slug): Game
    {
        return Game::query()->where('slug', $slug)->firstOrFail();
    }

    private function provider(): GameProvider
    {
        return GameProvider::query()->where('slug', LocalSimulatorCatalogueSeeder::PROVIDER_SLUG)->firstOrFail();
    }
}
