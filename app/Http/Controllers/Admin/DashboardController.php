<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Compliance\LaunchGate\LaunchGate;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Models\DemoWalletTransaction;
use App\Domain\GameProviders\Enums\WebhookProcessingStatus;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\GameProviders\Models\ProviderWebhookEvent;
use App\Domain\Games\Models\Game;
use App\Domain\Identity\Authorization\Permission;
use App\Domain\Identity\Authorization\Role;
use App\Domain\Identity\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff overview. Each block is included only when the viewer holds the
 * permission for the underlying data.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, LaunchGate $gate): Response
    {
        /** @var User $actor */
        $actor = $request->user();

        return Inertia::render('admin/Dashboard', [
            'roles' => $actor->getRoleNames()->values()->all(),
            'playerCounts' => $actor->can(Permission::PlayersView->value) ? $this->playerCounts() : null,
            'activity' => $actor->can(Permission::ReportsView->value) ? $this->activity() : null,
            'webhooks' => $actor->can(Permission::ProvidersView->value) ? $this->webhooks() : null,
            'gate' => $actor->can(Permission::LaunchGateView->value)
                ? ['real_money_enabled' => $gate->isRealMoneyEnabled()]
                : null,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function playerCounts(): array
    {
        /** @var array<string, int> $counts */
        $counts = User::query()
            ->role(Role::Player->value)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();

        return collect(UserStatus::cases())
            ->mapWithKeys(fn (UserStatus $status): array => [$status->value => $counts[$status->value] ?? 0])
            ->all();
    }

    /**
     * Demo play over the last 7 days.
     *
     * @return array<string, mixed>
     */
    private function activity(): array
    {
        $start = CarbonImmutable::instance(Date::now())->startOfDay()->subDays(6);

        $sessionsPerDay = GameSession::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy(DB::raw('date(created_at)'))
            ->toBase()
            ->pluck('total', 'day');

        $bets = DemoWalletTransaction::query()
            ->where('type', DemoTransactionType::Bet->value)
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, count(*) as rounds, sum(amount_minor) as staked')
            ->groupBy(DB::raw('date(created_at)'))
            ->toBase()
            ->get()
            ->keyBy('day');

        $daily = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i)->toDateString();
            $daily[] = [
                'date' => $day,
                'sessions' => (int) ($sessionsPerDay[$day] ?? 0),
                'rounds' => (int) ($bets[$day]->rounds ?? 0),
                'staked' => (int) ($bets[$day]->staked ?? 0),
            ];
        }

        $topGames = GameSession::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('game_id, count(*) as sessions, count(distinct user_id) as players')
            ->groupBy('game_id')
            ->orderByDesc('sessions')
            ->limit(5)
            ->toBase()
            ->get();

        $names = Game::query()->whereKey($topGames->pluck('game_id')->all())->pluck('name', 'id');

        return [
            'daily' => $daily,
            'sessions' => array_sum(array_column($daily, 'sessions')),
            'rounds' => array_sum(array_column($daily, 'rounds')),
            'staked' => array_sum(array_column($daily, 'staked')),
            'active_players' => GameSession::query()->where('created_at', '>=', $start)->distinct()->count('user_id'),
            'top_games' => $topGames->map(fn (object $row): array => [
                'name' => (string) ($names[$row->game_id] ?? 'Unknown game'),
                'sessions' => (int) $row->sessions,
                'players' => (int) $row->players,
            ])->all(),
        ];
    }

    /**
     * Provider events received in the last 24 hours.
     *
     * @return array<string, int>
     */
    private function webhooks(): array
    {
        $counts = ProviderWebhookEvent::query()
            ->where('received_at', '>=', Date::now()->subDay())
            ->selectRaw('processing_status, count(*) as total')
            ->groupBy('processing_status')
            ->toBase()
            ->pluck('total', 'processing_status');

        $statuses = collect(WebhookProcessingStatus::cases())
            ->mapWithKeys(fn (WebhookProcessingStatus $s): array => [$s->value => (int) ($counts[$s->value] ?? 0)]);

        return [
            'total' => (int) $statuses->sum(),
            ...$statuses->all(),
        ];
    }
}
