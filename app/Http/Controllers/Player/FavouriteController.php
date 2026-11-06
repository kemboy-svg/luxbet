<?php

namespace App\Http\Controllers\Player;

use App\Domain\Games\Catalogue\GameCatalogue;
use App\Domain\Games\Models\Game;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavouriteController extends Controller
{
    public function index(Request $request, GameCatalogue $catalogue): Response
    {
        /** @var User $user */
        $user = $request->user();

        $games = $user->favouriteGames()->listed()->with('provider')
            ->orderByPivot('created_at', 'desc')
            ->get();

        return Inertia::render('Favourites', [
            'games' => $games->map(fn (Game $game): array => $catalogue->present($game))->all(),
        ]);
    }

    public function store(Request $request, string $slug, GameCatalogue $catalogue): RedirectResponse
    {
        $game = $catalogue->findListed($slug);
        abort_if($game === null, 404);

        /** @var User $user */
        $user = $request->user();
        $user->favouriteGames()->syncWithoutDetaching([$game->id]);

        return back();
    }

    public function destroy(Request $request, string $slug, GameCatalogue $catalogue): RedirectResponse
    {
        $game = $catalogue->findListed($slug);
        abort_if($game === null, 404);

        /** @var User $user */
        $user = $request->user();
        $user->favouriteGames()->detach($game->id);

        return back();
    }
}
