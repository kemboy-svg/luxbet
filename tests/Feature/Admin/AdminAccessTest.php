<?php

namespace Tests\Feature\Admin;

use App\Domain\Identity\Authorization\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_players_cannot_reach_any_admin_route(): void
    {
        $player = $this->playerUser();

        foreach (['/admin', '/admin/players', '/admin/launch-control'] as $uri) {
            $this->actingAs($player)->get($uri)->assertForbidden();
        }
    }

    public function test_players_do_not_receive_admin_permissions_in_page_props(): void
    {
        $this->actingAs($this->playerUser())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.permissions', []));
    }

    public function test_staff_without_two_factor_are_sent_to_set_it_up(): void
    {
        $staff = User::factory()->staff(Role::SuperAdmin)->create();

        $this->actingAs($staff)
            ->get('/admin')
            ->assertRedirect(route('security.edit'));
    }

    public function test_staff_with_two_factor_can_open_the_admin_overview(): void
    {
        $this->actingAs($this->staffUser(Role::SupportAgent))
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Dashboard')
                ->where('roles', ['support_agent'])
                ->where('gate', null));
    }

    public function test_staff_with_unverified_email_cannot_open_admin(): void
    {
        $staff = User::factory()->unverified()->withTwoFactor()->staff(Role::SuperAdmin)->create();

        $this->actingAs($staff)->get('/admin')->assertRedirect(route('verification.notice'));
    }

    public function test_each_admin_page_requires_its_own_permission(): void
    {
        $this->actingAs($this->staffUser(Role::GameManager))
            ->get('/admin/players')
            ->assertForbidden();

        $this->actingAs($this->staffUser(Role::SupportAgent))
            ->get('/admin/players')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/players/Index'));
    }

    public function test_staff_permissions_are_shared_for_navigation(): void
    {
        $this->actingAs($this->staffUser(Role::SupportAgent))
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.permissions', fn ($permissions) => collect($permissions)->sort()->values()->all() === ['admin.access', 'players.view']));
    }
}
