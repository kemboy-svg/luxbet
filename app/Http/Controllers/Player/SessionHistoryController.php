<?php

namespace App\Http\Controllers\Player;

use App\Domain\GameProviders\Models\GameSession;
use App\Domain\Players\Queries\PlayerActivity;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionHistoryController extends Controller
{
    public function __invoke(Request $request, PlayerActivity $activity): Response
    {
        /** @var User $user */
        $user = $request->user();

        $sessions = GameSession::query()
            ->where('user_id', $user->id)
            ->with('game')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $totals = $activity->totalsFor($sessions->items());

        return Inertia::render('SessionHistory', [
            'sessions' => $sessions->through(
                fn (GameSession $session): array => $activity->presentSession($session, $totals[$session->session_reference]),
            ),
        ]);
    }
}
