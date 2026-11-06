<?php

namespace App\Http\Controllers\Player;

use App\Domain\DemoWallet\Actions\EnsureDemoWallet;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\Games\Catalogue\GameCatalogue;
use App\Domain\Games\Models\Game;
use App\Domain\Players\Queries\PlayerActivity;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, EnsureDemoWallet $ensure, GameCatalogue $catalogue, PlayerActivity $activity): Response
    {
        /** @var User $user */
        $user = $request->user();
        $wallet = $ensure($user);

        $recentSessions = GameSession::query()
            ->where('user_id', $user->id)
            ->with('game')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $totals = $activity->totalsFor($recentSessions);

        $recentIds = $activity->recentGameIds($user);
        $recentGames = Game::query()->listed()->with('provider')->whereKey($recentIds->all())->get()
            ->sortBy(fn (Game $game): int|false => $recentIds->search($game->id))
            ->values();

        $favourites = $user->favouriteGames()->listed()->with('provider')
            ->orderByPivot('created_at', 'desc')
            ->limit(8)
            ->get();

        $daily = $activity->daily($user);

        return Inertia::render('Dashboard', [
            'balance' => $wallet->balance_minor,
            'allowance' => (int) config('luxbet.demo_wallet.initial_allowance'),
            'daily' => $daily,
            'week' => [
                'sessions' => array_sum(array_column($daily, 'sessions')),
                'rounds' => array_sum(array_column($daily, 'rounds')),
            ],
            'recentGames' => $recentGames->map(fn (Game $game): array => $catalogue->present($game))->all(),
            'favourites' => $favourites->map(fn (Game $game): array => $catalogue->present($game))->all(),
            'recentSessions' => $recentSessions
                ->map(fn (GameSession $session): array => $activity->presentSession($session, $totals[$session->session_reference]))
                ->all(),
        ]);
    }
}
