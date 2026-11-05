<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Compliance\LaunchGate\LaunchGate;
use App\Domain\Identity\Authorization\Permission;
use App\Domain\Identity\Authorization\Role;
use App\Domain\Identity\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LaunchGate $gate): Response
    {
        $actor = $request->user();
        $canViewPlayers = $actor?->can(Permission::PlayersView->value) === true;

        $playerCounts = null;

        if ($canViewPlayers) {
            /** @var array<string, int> $counts */
            $counts = User::query()
                ->role(Role::Player->value)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn (mixed $total): int => (int) $total)
                ->all();

            $playerCounts = collect(UserStatus::cases())
                ->mapWithKeys(fn (UserStatus $status): array => [$status->value => $counts[$status->value] ?? 0])
                ->all();
        }

        return Inertia::render('admin/Dashboard', [
            'roles' => $actor?->getRoleNames()->values()->all() ?? [],
            'playerCounts' => $playerCounts,
            'gate' => $actor?->can(Permission::LaunchGateView->value) === true
                ? ['real_money_enabled' => $gate->isRealMoneyEnabled()]
                : null,
        ]);
    }
}
