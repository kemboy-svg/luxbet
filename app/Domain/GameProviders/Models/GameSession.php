<?php

namespace App\Domain\GameProviders\Models;

use App\Domain\DemoWallet\Models\DemoWallet;
use App\Domain\GameProviders\Enums\GameSessionStatus;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameProvider;
use App\Models\User;
use App\Policies\GameSessionPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

/**
 * A short-lived demo game session. Always mode `demo`.
 *
 * @property string $id
 * @property string $user_id
 * @property string $game_id
 * @property string $provider_id
 * @property string $demo_wallet_id
 * @property string $session_reference
 * @property string|null $provider_session_reference
 * @property string $mode
 * @property GameSessionStatus $status
 * @property string|null $failure_reason
 * @property CarbonImmutable|null $launched_at
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $ended_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Game $game
 * @property-read GameProvider $provider
 * @property-read DemoWallet $wallet
 */
#[UsePolicy(GameSessionPolicy::class)]
class GameSession extends Model
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
        'mode' => 'demo',
        'status' => 'created',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GameSessionStatus::class,
            'launched_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'session_reference';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<GameProvider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(GameProvider::class, 'provider_id');
    }

    /**
     * @return BelongsTo<DemoWallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(DemoWallet::class, 'demo_wallet_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->lessThanOrEqualTo(Date::now());
    }

    /**
     * Whether new bets may be placed in this session right now.
     */
    public function isOpen(): bool
    {
        return $this->status === GameSessionStatus::Launched && ! $this->isExpired();
    }
}
