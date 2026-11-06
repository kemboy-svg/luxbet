<?php

namespace App\Domain\Games\Models;

use App\Domain\Games\Enums\GameCategory;
use App\Domain\Games\Enums\ProviderEnvironment;
use App\Domain\Games\Enums\ProviderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $provider_id
 * @property string $provider_game_code
 * @property string $name
 * @property string $slug
 * @property GameCategory $category
 * @property string|null $thumbnail_path
 * @property string|null $description
 * @property bool $supports_demo
 * @property bool $is_enabled
 * @property int $sort_order
 * @property array<string, mixed> $metadata
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read GameProvider $provider
 */
class Game extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => GameCategory::class,
            'supports_demo' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<GameProvider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(GameProvider::class, 'provider_id');
    }

    /**
     * Games that may appear in the public catalogue: enabled, from a
     * provider in a listable status. Some may still not be playable.
     *
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopeListed(Builder $query): Builder
    {
        return $query
            ->where('is_enabled', true)
            ->whereHas('provider', fn (Builder $provider) => $provider->whereIn('status', [
                ProviderStatus::Sandbox->value,
                ProviderStatus::Active->value,
            ]));
    }

    /**
     * Games that can be launched in demo mode right now.
     *
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopePlayable(Builder $query): Builder
    {
        return $query
            ->listed()
            ->where('supports_demo', true)
            ->whereHas('provider', fn (Builder $provider) => $provider
                ->where('supports_demo', true)
                ->where('environment', ProviderEnvironment::Sandbox->value));
    }

    public function isPlayable(): bool
    {
        return $this->is_enabled && $this->supports_demo && $this->provider->canServeDemo();
    }

    /**
     * @return array{background: string, border: string}
     */
    public function tileArt(): array
    {
        $tile = $this->metadata['tile'] ?? [];

        return [
            'background' => is_array($tile) && is_string($tile['background'] ?? null) ? $tile['background'] : '#2A1546',
            'border' => is_array($tile) && is_string($tile['border'] ?? null) ? $tile['border'] : '#7A5520',
        ];
    }
}
