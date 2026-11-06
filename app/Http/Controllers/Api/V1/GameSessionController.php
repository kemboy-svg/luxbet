<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\GameProviders\Actions\PlaySimulatorRound;
use App\Domain\GameProviders\Actions\StartDemoSession;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\Games\Catalogue\GameCatalogue;
use App\Http\Controllers\Controller;
use App\Http\Resources\GameSessionResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GameSessionController extends Controller
{
    /**
     * POST /api/v1/games/{slug}/demo-session
     */
    public function store(Request $request, string $slug, GameCatalogue $catalogue, StartDemoSession $start): JsonResponse
    {
        $validated = $request->validate(['return_url' => ['nullable', 'string', 'max:2048']]);

        $game = $catalogue->findListed($slug);

        if ($game === null) {
            return ApiResponse::error('NOT_FOUND', 'The requested resource was not found.', 404);
        }

        /** @var User $user */
        $user = $request->user();
        $launch = $start($user, $game, $validated['return_url'] ?? null);
        $session = $launch['session'];

        return ApiResponse::success([
            'session_id' => $session->session_reference,
            'mode' => 'demo',
            'launch_url' => $launch['launch_url'],
            'expires_at' => $session->expires_at->toIso8601String(),
        ], 201);
    }

    /**
     * GET /api/v1/game-sessions
     */
    public function index(Request $request): JsonResponse
    {
        $page = GameSession::query()
            ->with('game')
            ->where('user_id', $request->user()?->getAuthIdentifier())
            ->latest()
            ->paginate(20);

        return ApiResponse::success(
            GameSessionResource::collection($page->getCollection())->resolve($request),
            meta: ['pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ]],
        );
    }

    /**
     * GET /api/v1/game-sessions/{session}
     */
    public function show(Request $request, GameSession $session): JsonResponse
    {
        Gate::authorize('view', $session);

        return ApiResponse::success((new GameSessionResource($session->load('game')))->resolve($request));
    }

    /**
     * POST /api/v1/game-sessions/{session}/simulator-rounds
     */
    public function simulatorRound(Request $request, GameSession $session, PlaySimulatorRound $play): JsonResponse
    {
        Gate::authorize('play', $session);

        $validated = $request->validate(['stake' => ['required', 'integer']]);

        $round = $play($session, (int) $validated['stake']);
        $outcome = $round['outcome'];

        return ApiResponse::success([
            'round_id' => $round['round_id'],
            'stake' => $outcome->stake,
            'payout' => $outcome->payout,
            'result' => $outcome->result,
            'display' => $outcome->display,
            'balance' => $round['balance'],
            'demo' => true,
        ]);
    }
}
