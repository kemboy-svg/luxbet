<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Games\Catalogue\GameCatalogue;
use App\Domain\Games\Models\Game;
use App\Domain\Identity\Authorization\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGameRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(Request $request, GameCatalogue $catalogue): Response
    {
        $games = Game::query()
            ->with('provider')
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Game $game): array => [
                'id' => $game->id,
                'name' => $game->name,
                'slug' => $game->slug,
                'category' => $game->category->label(),
                'provider' => $game->provider->name,
                'provider_status' => $game->provider->status->value,
                'supports_demo' => $game->supports_demo,
                'is_enabled' => $game->is_enabled,
                'sort_order' => $game->sort_order,
                'public_status' => $game->is_enabled ? $catalogue->status($game) : 'hidden',
            ])
            ->all();

        return Inertia::render('admin/games/Index', [
            'games' => $games,
            'canManage' => $request->user()?->can(Permission::GamesManage->value) === true,
        ]);
    }

    public function update(UpdateGameRequest $request, Game $game, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $game, $audit): void {
            $game->forceFill($request->validated());

            $changes = collect($game->getDirty())
                ->mapWithKeys(fn (mixed $value, string $key): array => [$key => [
                    'from' => $game->getOriginal($key),
                    'to' => $value,
                ]])
                ->all();

            if ($changes === []) {
                return;
            }

            $game->save();

            $audit->record('game.updated', subject: $game, metadata: ['changes' => $changes]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Game updated.']);

        return back();
    }
}
