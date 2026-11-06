<?php

namespace App\Http\Resources;

use App\Domain\GameProviders\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GameSession
 */
class GameSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'session_id' => $this->session_reference,
            'mode' => 'demo',
            'status' => $this->status->value,
            'game' => [
                'slug' => $this->game->slug,
                'name' => $this->game->name,
            ],
            'launched_at' => $this->launched_at?->toIso8601String(),
            'expires_at' => $this->expires_at->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
        ];
    }
}
