<?php

namespace Tests\Feature\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Authorization\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminViewersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: list<Role>}>
     */
    public static function pages(): array
    {
        return [
            'demo ledger' => ['/admin/demo-transactions', [Role::FinanceViewer, Role::SuperAdmin]],
            'provider events' => ['/admin/provider-events', [Role::GameManager, Role::SuperAdmin]],
            'audit log' => ['/admin/audit-log', [Role::ComplianceOfficer, Role::SuperAdmin]],
            'system health' => ['/admin/system-health', [Role::ComplianceOfficer, Role::FinanceViewer, Role::SuperAdmin]],
        ];
    }

    /**
     * @param  list<Role>  $allowed
     */
    #[DataProvider('pages')]
    public function test_each_viewer_is_limited_to_its_permission(string $uri, array $allowed): void
    {
        foreach (Role::cases() as $role) {
            if (! $role->isStaff()) {
                continue;
            }

            $response = $this->actingAs($this->staffUser($role))->get($uri);

            in_array($role, $allowed, true)
                ? $response->assertOk()
                : $response->assertForbidden();
        }

        $this->actingAs($this->playerUser())->get($uri)->assertForbidden();
    }

    public function test_the_overview_only_includes_blocks_the_viewer_may_see(): void
    {
        $this->actingAs($this->staffUser(Role::SupportAgent))
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->where('activity', null)
                ->where('webhooks', null)
                ->whereType('playerCounts', 'array'));

        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->has('activity.daily', 7)
                ->where('webhooks.total', 0));
    }

    public function test_player_detail_hides_the_ledger_without_demo_wallet_view(): void
    {
        $player = $this->playerUser();

        $this->actingAs($this->staffUser(Role::SupportAgent))
            ->get("/admin/players/{$player->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/players/Show')
                ->where('player.email', $player->email)
                ->where('balance', null)
                ->where('transactions', null)
                ->where('audit', null)
                ->where('can.adjust_wallet', false));

        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->get("/admin/players/{$player->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('balance', (int) config('luxbet.demo_wallet.initial_allowance'))
                ->has('transactions', 1)
                ->where('can.adjust_wallet', true));
    }

    public function test_staff_accounts_are_not_shown_as_players(): void
    {
        $colleague = $this->staffUser(Role::GameManager);

        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->get("/admin/players/{$colleague->id}")
            ->assertNotFound();
    }

    public function test_the_audit_log_can_be_filtered_by_action(): void
    {
        $admin = $this->staffUser(Role::SuperAdmin);
        app(AuditLogger::class)->record('player.example', actor: $admin);
        app(AuditLogger::class)->record('staff.example', actor: $admin);

        $this->actingAs($admin)
            ->get('/admin/audit-log?action=player.')
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'player.example')
                ->where('logs.data.0.actor', $admin->email));
    }

    public function test_system_health_reports_the_launch_gate_as_disabled(): void
    {
        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->get('/admin/system-health')
            ->assertInertia(fn (Assert $page) => $page
                ->where('checks', fn ($checks) => collect($checks)
                    ->firstWhere('name', 'Real-money launch gate')['status'] === 'ok'));
    }
}
