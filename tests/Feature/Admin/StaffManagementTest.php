<?php

namespace Tests\Feature\Admin;

use App\Domain\Identity\Actions\ManageStaff;
use App\Domain\Identity\Authorization\Role;
use App\Domain\Identity\Exceptions\StaffManagementException;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return $this->staffUser(Role::SuperAdmin);
    }

    /**
     * Staff management sits behind password re-confirmation.
     *
     * @return $this
     */
    private function confirmed(User $user): static
    {
        return $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_only_staff_managers_can_open_the_page(): void
    {
        foreach ([Role::SupportAgent, Role::ComplianceOfficer, Role::GameManager, Role::FinanceViewer] as $role) {
            $this->confirmed($this->staffUser($role))->get('/admin/staff')->assertForbidden();
        }

        $this->confirmed($this->playerUser())->get('/admin/staff')->assertForbidden();
    }

    public function test_the_password_must_be_confirmed_first(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/staff')
            ->assertRedirect(route('password.confirm'));
    }

    public function test_the_page_lists_staff_but_not_players(): void
    {
        $admin = $this->admin();
        $player = $this->playerUser();

        $this->confirmed($admin)
            ->get('/admin/staff')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Staff')
                ->has('staff', 1)
                ->where('staff.0.id', $admin->id)
                ->where('staff.0.is_self', true)
                ->where('roles', fn ($roles) => ! collect($roles)->pluck('value')->contains('player')));

        $this->assertFalse($player->isStaff());
    }

    public function test_a_staff_account_can_be_created(): void
    {
        Notification::fake();
        $admin = $this->admin();

        $this->confirmed($admin)
            ->post('/admin/staff', [
                'name' => 'Sam Support',
                'email' => 'sam@luxbet.test',
                'role' => 'support_agent',
                'reason' => 'New support hire',
            ])
            ->assertSessionHasNoErrors();

        $staff = User::query()->where('email', 'sam@luxbet.test')->firstOrFail();
        $this->assertTrue($staff->hasRole('support_agent'));
        $this->assertFalse($staff->hasRole('player'));
        $this->assertNull($staff->playerProfile);
        Notification::assertSentTo($staff, ResetPassword::class);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff.created',
            'subject_id' => $staff->id,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_existing_emails_cannot_be_reused_for_staff(): void
    {
        $player = $this->playerUser();

        $this->confirmed($this->admin())
            ->post('/admin/staff', [
                'name' => 'Someone',
                'email' => $player->email,
                'role' => 'support_agent',
                'reason' => 'Trying to promote a player',
            ])
            ->assertSessionHasErrors('email');

        $this->assertFalse($player->fresh()?->isStaff());
    }

    public function test_roles_can_be_granted_and_revoked_with_an_audit_trail(): void
    {
        $admin = $this->admin();
        $colleague = $this->staffUser(Role::SupportAgent);

        $this->confirmed($admin)
            ->post("/admin/staff/{$colleague->id}/roles", ['role' => 'finance_viewer', 'reason' => 'Covering month end'])
            ->assertSessionHasNoErrors();
        $this->assertTrue($colleague->fresh()?->hasRole('finance_viewer'));

        $this->confirmed($admin)
            ->delete("/admin/staff/{$colleague->id}/roles", ['role' => 'finance_viewer', 'reason' => 'Month end done'])
            ->assertSessionHasNoErrors();
        $this->assertFalse($colleague->fresh()?->hasRole('finance_viewer'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'staff.role_granted', 'subject_id' => $colleague->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff.role_revoked', 'subject_id' => $colleague->id]);
    }

    public function test_nobody_can_change_their_own_roles(): void
    {
        $admin = $this->admin();
        $this->admin(); // a second super admin, so only the self-rule applies

        $this->confirmed($admin)
            ->delete("/admin/staff/{$admin->id}/roles", ['role' => 'super_admin', 'reason' => 'Stepping down'])
            ->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()?->hasRole('super_admin'));
    }

    public function test_player_accounts_cannot_be_given_staff_roles(): void
    {
        $player = $this->playerUser();

        $this->confirmed($this->admin())
            ->post("/admin/staff/{$player->id}/roles", ['role' => 'support_agent', 'reason' => 'Promote player'])
            ->assertSessionHasErrors('role');

        $this->assertFalse($player->fresh()?->isStaff());
    }

    public function test_the_player_role_cannot_be_granted_here(): void
    {
        $colleague = $this->staffUser(Role::SupportAgent);

        $this->confirmed($this->admin())
            ->post("/admin/staff/{$colleague->id}/roles", ['role' => 'player', 'reason' => 'Let them play'])
            ->assertSessionHasErrors('role');
    }

    public function test_a_super_admin_can_be_removed_while_another_remains(): void
    {
        $admin = $this->admin();
        $other = $this->admin();

        $this->confirmed($admin)
            ->delete("/admin/staff/{$other->id}/roles", ['role' => 'super_admin', 'reason' => 'Left the company'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($other->fresh()?->hasRole('super_admin'));
    }

    public function test_the_last_super_admin_is_never_removed(): void
    {
        // Over HTTP the self-rule already prevents this (only super admins
        // manage staff), so check the domain rule directly.
        $last = $this->admin();
        $actor = $this->staffUser(Role::SupportAgent);

        try {
            app(ManageStaff::class)->revoke($actor, $last, Role::SuperAdmin, 'Removing the last one');
            $this->fail('Expected the last super admin to be kept.');
        } catch (StaffManagementException $e) {
            $this->assertSame('At least one super admin must remain.', $e->getMessage());
        }

        $this->assertTrue($last->fresh()?->hasRole('super_admin'));
        $this->assertDatabaseMissing('audit_logs', ['action' => 'staff.role_revoked']);
    }
}
