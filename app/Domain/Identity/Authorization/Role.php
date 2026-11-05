<?php

namespace App\Domain\Identity\Authorization;

/**
 * Roles and their least-privilege permission sets (instructions.md §4).
 *
 * super_admin is NOT granted a blanket Gate bypass. It holds only the
 * permissions listed here, none of which can activate real-money mode.
 */
enum Role: string
{
    case Player = 'player';
    case SupportAgent = 'support_agent';
    case ComplianceOfficer = 'compliance_officer';
    case GameManager = 'game_manager';
    case FinanceViewer = 'finance_viewer';
    case SuperAdmin = 'super_admin';

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Player => [],
            self::SupportAgent => [
                Permission::AdminAccess,
                Permission::PlayersView,
            ],
            self::ComplianceOfficer => [
                Permission::AdminAccess,
                Permission::PlayersView,
                Permission::PlayersSuspend,
                Permission::ComplianceView,
                Permission::ComplianceReview,
                Permission::AuditView,
                Permission::ReportsView,
                Permission::LaunchGateView,
            ],
            self::GameManager => [
                Permission::AdminAccess,
                Permission::GamesView,
                Permission::GamesManage,
                Permission::ProvidersView,
                Permission::ProvidersManage,
            ],
            self::FinanceViewer => [
                Permission::AdminAccess,
                Permission::DemoWalletView,
                Permission::ReportsView,
            ],
            self::SuperAdmin => [
                Permission::AdminAccess,
                Permission::PlayersView,
                Permission::PlayersSuspend,
                Permission::GamesView,
                Permission::GamesManage,
                Permission::ProvidersView,
                Permission::ProvidersManage,
                Permission::DemoWalletView,
                Permission::DemoWalletAdjust,
                Permission::ComplianceView,
                Permission::ReportsView,
                Permission::AuditView,
                Permission::StaffManage,
                Permission::LaunchGateView,
            ],
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Player;
    }

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    /**
     * @return list<string>
     */
    public static function staffRoleNames(): array
    {
        return array_values(array_map(
            fn (self $role): string => $role->value,
            array_filter(self::cases(), fn (self $role): bool => $role->isStaff()),
        ));
    }
}
