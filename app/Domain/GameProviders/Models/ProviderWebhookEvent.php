<?php

namespace App\Domain\GameProviders\Models;

use App\Domain\GameProviders\Enums\WebhookProcessingStatus;
use App\Domain\Games\Models\GameProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $provider_id
 * @property string $event_id
 * @property string $event_type
 * @property string $payload_hash
 * @property string|null $game_session_id
 * @property string|null $round_reference
 * @property int|null $amount
 * @property WebhookProcessingStatus $processing_status
 * @property string|null $failure_reason
 * @property CarbonImmutable $received_at
 * @property CarbonImmutable|null $processed_at
 * @property-read GameProvider $provider
 * @property-read GameSession|null $gameSession
 */
class ProviderWebhookEvent extends Model
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
            'processing_status' => WebhookProcessingStatus::class,
            'amount' => 'integer',
            'received_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<GameProvider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(GameProvider::class, 'provider_id');
    }

    /**
     * @return BelongsTo<GameSession, $this>
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}
