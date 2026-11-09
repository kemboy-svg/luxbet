<?php

namespace App\Domain\Games\Catalogue;

use App\Domain\GameProviders\Simulator\SimulatorRoundEngine;
use App\Domain\Games\Enums\GameCategory;
use App\Domain\Games\Models\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read side of the game catalogue.
 *
 * A category counts as available only when it has at least one game that
 * can actually be launched in demo mode, so nothing is advertised as
 * playable before a provider integration for it exists.
 */
class GameCatalogue
{
    public const STATUS_PLAYABLE = 'playable';

    public const STATUS_DEMO_NOT_OFFERED = 'demo_not_offered';

    public const STATUS_COMING_SOON = 'coming_soon';

    /** @var array<string, int>|null */
    private ?array $playableCounts = null;

    /**
     * @return list<array{id: string, name: string, available: bool, playable_games: int}>
     */
    public function categories(): array
    {
        $counts = $this->playableCounts();

        return array_map(fn (GameCategory $category): array => [
            'id' => $category->value,
            'name' => $category->label(),
            'available' => ($counts[$category->value] ?? 0) > 0,
            'playable_games' => $counts[$category->value] ?? 0,
        ], GameCategory::cases());
    }

    public function isCategoryAvailable(GameCategory $category): bool
    {
        return ($this->playableCounts()[$category->value] ?? 0) > 0;
    }

    /**
     * @return LengthAwarePaginator<int, Game>
     */
    public function browse(?GameCategory $category = null, ?string $search = null, int $perPage = 24): LengthAwarePaginator
    {
        $search = $search !== null ? trim($search) : null;

        return Game::query()
            ->listed()
            ->with('provider')
            ->when($category, fn (Builder $query, GameCategory $category) => $query->where('category', $category->value))
            ->when($search !== null && $search !== '', function (Builder $query) use ($search): void {
                $query->whereRaw('lower(name) like ?', ['%'.mb_strtolower(addcslashes((string) $search, '%_\\')).'%']);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Game>
     */
    public function featured(int $limit = 5): Collection
    {
        return Game::query()
            ->listed()
            ->with('provider')
            ->whereRaw("(metadata->>'featured')::boolean is true")
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    public function findListed(string $slug): ?Game
    {
        return Game::query()->listed()->with('provider')->where('slug', $slug)->first();
    }

    public function status(Game $game): string
    {
        if ($game->isPlayable()) {
            return self::STATUS_PLAYABLE;
        }

        return $this->isCategoryAvailable($game->category)
            ? self::STATUS_DEMO_NOT_OFFERED
            : self::STATUS_COMING_SOON;
    }

    /**
     * Public representation of a game. Never includes provider URLs,
     * credentials, secret references or internal codes.
     *
     * @return array<string, mixed>
     */
    public function present(Game $game): array
    {
        $tile = $game->metadata['tile'] ?? [];
        $art = $game->tileArt();

        return [
            'slug' => $game->slug,
            'name' => $game->name,
            'category' => $game->category->value,
            'category_label' => $game->category->label(),
            'description' => $game->description,
            'tile_title' => is_array($tile) && is_string($tile['title'] ?? null) ? $tile['title'] : $game->name,
            'background' => $art['background'],
            'border' => $art['border'],
            'status' => $this->status($game),
            'playable' => $game->isPlayable(),
            'demo_only' => true,
            'provider' => [
                'name' => $game->provider->name,
                'is_simulator' => $game->provider->is_simulator,
            ],
            'simulation' => $game->provider->is_simulator ? $this->simulation($game) : null,
        ];
    }

    /**
     * Design figures of a local-simulator game, shown on the game page so
     * players know what to expect. Not certified; demo credits only.
     *
     * @return array{rtp: float, volatility: string|null}|null
     */
    private function simulation(Game $game): ?array
    {
        $rtp = SimulatorRoundEngine::designRtp($game->category, $game->code);

        if ($rtp === null) {
            return null;
        }

        return [
            'rtp' => $rtp,
            'volatility' => $game->category === GameCategory::Slots
                ? SimulatorRoundEngine::profileFor($game->code)
                : null,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function playableCounts(): array
    {
        return $this->playableCounts ??= Game::query()
            ->playable()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }
}
