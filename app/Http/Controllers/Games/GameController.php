<?php

namespace App\Http\Controllers\Games;

use App\Domain\Games\Catalogue\GameCatalogue;
use App\Domain\Games\Enums\GameCategory;
use App\Domain\Games\Models\Game;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(Request $request, GameCatalogue $catalogue): Response
    {
        $validated = $request->validate([
            'category' => ['nullable', Rule::enum(GameCategory::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $category = isset($validated['category']) ? GameCategory::from($validated['category']) : null;
        $search = $validated['search'] ?? null;

        $games = $catalogue->browse($category, $search)
            ->withQueryString()
            ->through(fn (Game $game): array => $catalogue->present($game));

        return Inertia::render('games/Index', [
            'games' => $games,
            'categories' => $catalogue->categories(),
            'filters' => [
                'category' => $category?->value,
                'search' => $search,
            ],
        ]);
    }

    public function show(string $slug, GameCatalogue $catalogue): Response
    {
        $game = $catalogue->findListed($slug);

        abort_if($game === null, 404);

        return Inertia::render('games/Show', [
            'game' => $catalogue->present($game),
            'related' => $catalogue->browse($game->category, null, 5)
                ->getCollection()
                ->reject(fn (Game $other): bool => $other->is($game))
                ->take(4)
                ->map(fn (Game $other): array => $catalogue->present($other))
                ->values()
                ->all(),
        ]);
    }
}
