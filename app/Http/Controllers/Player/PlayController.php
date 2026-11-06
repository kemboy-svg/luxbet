<?php

namespace App\Http\Controllers\Player;

use App\Domain\GameProviders\Actions\StartDemoSession;
use App\Domain\GameProviders\Enums\GameSessionStatus;
use App\Domain\GameProviders\Exceptions\DemoSessionRefused;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\GameProviders\Support\ReturnUrlGuard;
use App\Domain\Games\Catalogue\GameCatalogue;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PlayController extends Controller
{
    /**
     * POST /games/{slug}/play — start a demo session and go to its launch URL.
     */
    public function start(Request $request, string $slug, GameCatalogue $catalogue, StartDemoSession $start): RedirectResponse|SymfonyResponse
    {
        $game = $catalogue->findListed($slug);
        abort_if($game === null, 404);

        /** @var User $user */
        $user = $request->user();

        try {
            $launch = $start($user, $game, url("/games/{$game->slug}"));
        } catch (DemoSessionRefused $e) {
            return back()->withErrors(['play' => $e->getMessage()]);
        }

        // The URL has already passed the launch allowlist.
        return Inertia::location($launch['launch_url']);
    }

    /**
     * GET /play/{session} — the local simulator's game screen (signed URL).
     */
    public function show(Request $request, GameSession $session, GameCatalogue $catalogue, ReturnUrlGuard $returnUrls): Response
    {
        Gate::authorize('play', $session);

        $session->loadMissing(['game.provider', 'wallet']);

        return Inertia::render('games/Play', [
            'session' => [
                'id' => $session->session_reference,
                'status' => $session->status->value,
                'open' => $session->isOpen(),
                'expires_at' => $session->expires_at->toIso8601String(),
            ],
            'game' => $catalogue->present($session->game),
            'balance' => $session->wallet->balance_minor,
            'stakes' => config('luxbet.providers.simulator.stakes'),
            'returnUrl' => $returnUrls->resolve($request->string('return')->toString()),
        ]);
    }

    /**
     * POST /play/{session}/end — close the session early.
     */
    public function end(Request $request, GameSession $session, ReturnUrlGuard $returnUrls): RedirectResponse
    {
        Gate::authorize('play', $session);

        if ($session->status === GameSessionStatus::Launched) {
            $session->forceFill(['status' => GameSessionStatus::Completed, 'ended_at' => Date::now()])->save();
        }

        return redirect()->to($returnUrls->resolve($request->string('return')->toString()));
    }
}
