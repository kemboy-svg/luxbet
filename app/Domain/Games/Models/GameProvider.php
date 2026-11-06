<?php

namespace App\Domain\Games\Models;

use App\Domain\Games\Enums\ProviderEnvironment;
use App\Domain\Games\Enums\ProviderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A game provider. Holds only references to secrets, never secret values,
 * and those references are hidden from serialisation so they cannot reach
 * the browser.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property ProviderEnvironment $environment
 * @property ProviderStatus $status
 * @property string|null $base_url
 * @property string|null $credentials_secret_reference
 * @property bool $supports_demo
 * @property bool $is_simulator
 * @property string|null $webhook_signing_key_reference
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Hidden(['credentials_secret_reference', 'webhook_signing_key_reference', 'base_url'])]
class GameProvider extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'environment' => 'sandbox',
        'status' => 'inactive',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'environment' => ProviderEnvironment::class,
            'status' => ProviderStatus::class,
            'supports_demo' => 'boolean',
            'is_simulator' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Game, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'provider_id');
    }

    /**
     * Demo sessions may only use sandbox endpoints of a provider that offers
     * demo play and is in a listable status.
     */
    public function canServeDemo(): bool
    {
        return $this->supports_demo
            && $this->status->isListable()
            && $this->environment === ProviderEnvironment::Sandbox;
    }
}
