<?php

namespace App\Domain\Identity\Authorization;

/**
 * Every permission in the system. There is deliberately no permission that
 * changes the real-money launch gate: `launch_gate.view` is read-only.
 */
enum Permission: string
{
    case AdminAccess = 'admin.access';

    case PlayersView = 'players.view';
    case PlayersSuspend = 'players.suspend';

    case GamesView = 'games.view';
    case GamesManage = 'games.manage';

    case ProvidersView = 'providers.view';
    case ProvidersManage = 'providers.manage';

    case DemoWalletView = 'demo_wallet.view';
    case DemoWalletAdjust = 'demo_wallet.adjust';

    case ComplianceView = 'compliance.view';
    case ComplianceReview = 'compliance.review';

    case ReportsView = 'reports.view';
    case AuditView = 'audit.view';
    case StaffManage = 'staff.manage';
    case LaunchGateView = 'launch_gate.view';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
