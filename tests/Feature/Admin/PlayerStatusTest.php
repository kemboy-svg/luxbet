<?php

namespace Tests\Feature\Admin;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Identity\Authorization\Role;
use App\Domain\Identity\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_compliance_officer_can_suspend_a_player_and_it_is_audited(): void
    {
        $officer = $this->staffUser(Role::ComplianceOfficer);
        $player = $this->playerUser();

        $this->actingAs($officer)
            ->patch("/admin/players/{$player->id}/status", [
                'status' => 'suspended',
                'reason' => 'Repeated automated sign-ups',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(UserStatus::Suspended, $player->fresh()?->status);

        $log = AuditLog::query()->where('action', 'account.status_changed')->sole();
        $this->assertSame($officer->id, $log->actor_user_id);
        $this->assertSame($player->id, $log->subject_id);
        $this->assertSame('Repeated automated sign-ups', $log->reason);
        $this->assertEquals(['from' => 'active', 'to' => 'suspended'], $log->metadata);
    }

    public function test_a_reason_is_required(): void
    {
        $player = $this->playerUser();

        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->patch("/admin/players/{$player->id}/status", ['status' => 'suspended', 'reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(UserStatus::Active, $player->fresh()?->status);
    }

    public function test_pending_cannot_be_set_by_staff(): void
    {
        $player = $this->playerUser();

        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->patch("/admin/players/{$player->id}/status", ['status' => 'pending', 'reason' => 'Testing invalid status'])
            ->assertSessionHasErrors('status');
    }

    public function test_a_support_agent_cannot_change_player_status(): void
    {
        $player = $this->playerUser();

        $this->actingAs($this->staffUser(Role::SupportAgent))
            ->patch("/admin/players/{$player->id}/status", ['status' => 'suspended', 'reason' => 'Should not work'])
            ->assertForbidden();

        $this->assertSame(UserStatus::Active, $player->fresh()?->status);
    }

    public function test_staff_cannot_change_their_own_or_another_staff_members_status(): void
    {
        $admin = $this->staffUser(Role::SuperAdmin);
        $colleague = $this->staffUser(Role::SupportAgent);

        $this->actingAs($admin)
            ->patch("/admin/players/{$admin->id}/status", ['status' => 'closed', 'reason' => 'Self change attempt'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch("/admin/players/{$colleague->id}/status", ['status' => 'suspended', 'reason' => 'Staff via player tool'])
            ->assertForbidden();
    }

    public function test_a_malformed_player_id_returns_not_found(): void
    {
        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->patch('/admin/players/not-a-uuid/status', ['status' => 'suspended', 'reason' => 'Malformed id'])
            ->assertNotFound();
    }

    public function test_a_player_cannot_change_another_players_status(): void
    {
        $other = $this->playerUser();

        $this->actingAs($this->playerUser())
            ->patch("/admin/players/{$other->id}/status", ['status' => 'suspended', 'reason' => 'Player attempt'])
            ->assertForbidden();
    }
}
