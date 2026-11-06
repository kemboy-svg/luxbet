<?php

namespace App\Http\Controllers;

use App\Domain\Games\Catalogue\GameCatalogue;
use App\Domain\Games\Models\Game;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(GameCatalogue $catalogue): Response
    {
        return Inertia::render('Welcome', [
            'categories' => $catalogue->categories(),
            'featuredGames' => $catalogue->featured()
                ->map(fn (Game $game): array => $catalogue->present($game))
                ->values()
                ->all(),
        ]);
    }
}
