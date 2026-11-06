<?php

namespace App\Domain\GameProviders\Actions;

use App\Domain\GameProviders\Enums\GameSessionStatus;
use App\Domain\GameProviders\Models\GameSession;
use Illuminate\Support\Facades\Date;

class ExpireGameSessions
{
    /**
     * Marks launched sessions past their expiry as expired.
     *
     * @return int number of sessions expired
     */
    public function __invoke(): int
    {
        $now = Date::now();

        return GameSession::query()
            ->whereIn('status', [GameSessionStatus::Created->value, GameSessionStatus::Launched->value])
            ->where('expires_at', '<=', $now)
            ->update(['status' => GameSessionStatus::Expired->value, 'ended_at' => $now, 'updated_at' => $now]);
    }
}
