<?php

namespace Tests\Unit\Identity;

use App\Domain\Identity\Authorization\Permission;
use App\Domain\Identity\Authorization\Role;
use PHPUnit\Framework\TestCase;

class RolePermissionsTest extends TestCase
{
    public function test_players_have_no_staff_permissions(): void
    {
        $this->assertSame([], Role::Player->permissions());
        $this->assertFalse(Role::Player->isStaff());
    }

    public function test_every_staff_role_can_reach_the_admin_area(): void
    {
        foreach (Role::cases() as $role) {
            if ($role->isStaff()) {
                $this->assertContains(Permission::AdminAccess, $role->permissions(), $role->value);
            }
        }
    }

    public function test_support_agents_cannot_edit_balances_or_approve_anything(): void
    {
        $permissions = Role::SupportAgent->permissions();

        $this->assertNotContains(Permission::DemoWalletAdjust, $permissions);
        $this->assertNotContains(Permission::PlayersSuspend, $permissions);
        $this->assertNotContains(Permission::ComplianceReview, $permissions);
        $this->assertNotContains(Permission::LaunchGateView, $permissions);
    }

    public function test_game_managers_cannot_touch_financial_settings(): void
    {
        $permissions = Role::GameManager->permissions();

        $this->assertNotContains(Permission::DemoWalletView, $permissions);
        $this->assertNotContains(Permission::DemoWalletAdjust, $permissions);
    }

    public function test_finance_viewers_are_read_only(): void
    {
        $this->assertSame(
            [Permission::AdminAccess, Permission::DemoWalletView, Permission::ReportsView],
            Role::FinanceViewer->permissions(),
        );
    }

    public function test_no_permission_can_change_the_launch_gate(): void
    {
        foreach (Permission::cases() as $permission) {
            if (str_starts_with($permission->value, 'launch_gate.')) {
                $this->assertSame(Permission::LaunchGateView, $permission);
            }
        }
    }
}
