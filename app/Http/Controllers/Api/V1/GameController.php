<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Games\Catalogue\GameCatalogue;
use App\Domain\Games\Enums\GameCategory;
use App\Domain\Games\Models\Game;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Public, read-only catalogue. Responses never contain provider URLs,
 * credentials or secret references.
 */
class GameController extends Controller
{
    public function index(Request $request, GameCatalogue $catalogue): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', Rule::enum(GameCategory::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $page = $catalogue->browse(
            isset($validated['category']) ? GameCategory::from($validated['category']) : null,
            $validated['search'] ?? null,
            (int) ($validated['per_page'] ?? 24),
        );

        return ApiResponse::success(
            $page->getCollection()->map(fn (Game $game): array => $catalogue->present($game))->values()->all(),
            meta: ['pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ]],
        );
    }

    public function categories(GameCatalogue $catalogue): JsonResponse
    {
        return ApiResponse::success($catalogue->categories());
    }

    public function show(string $slug, GameCatalogue $catalogue): JsonResponse
    {
        $game = $catalogue->findListed($slug);

        if ($game === null) {
            return ApiResponse::error('NOT_FOUND', 'The requested resource was not found.', 404);
        }

        return ApiResponse::success($catalogue->present($game));
    }
}
