<?php

namespace App\Domain\Players\Queries;

use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Models\DemoWalletTransaction;
use App\Domain\GameProviders\Models\GameSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

/**
 * Read-only summaries of a player's demo play, for the dashboard and the
 * session history page.
 *
 * Simulator ledger rows carry `external_reference` = "<session_reference>:<round>",
 * so per-session totals are grouped on the part before the first colon.
 */
class PlayerActivity
{
    /**
     * Rounds, credits staked and credits won for each of the given sessions.
     *
     * @param  iterable<GameSession>  $sessions
     * @return array<string, array{rounds: int, staked: int, won: int}>
     */
    public function totalsFor(iterable $sessions): array
    {
        $references = [];
        foreach ($sessions as $session) {
            $references[] = $session->session_reference;
        }

        $totals = array_fill_keys($references, ['rounds' => 0, 'staked' => 0, 'won' => 0]);

        if ($references === []) {
            return $totals;
        }

        $rows = DemoWalletTransaction::query()
            ->selectRaw("split_part(external_reference, ':', 1) as session_reference")
            ->selectRaw('sum(case when type = ? then 1 else 0 end) as rounds', [DemoTransactionType::Bet->value])
            ->selectRaw('sum(case when type = ? then amount_minor else 0 end) as staked', [DemoTransactionType::Bet->value])
            ->selectRaw('sum(case when type = ? then amount_minor else 0 end) as won', [DemoTransactionType::Win->value])
            ->whereIn(DB::raw("split_part(external_reference, ':', 1)"), $references)
            ->groupBy(DB::raw("split_part(external_reference, ':', 1)"))
            ->toBase()
            ->get();

        foreach ($rows as $row) {
            $totals[(string) $row->session_reference] = [
                'rounds' => (int) $row->rounds,
                'staked' => (int) $row->staked,
                'won' => (int) $row->won,
            ];
        }

        return $totals;
    }

    /**
     * Sessions and rounds per day for the last $days days, oldest first.
     * Days with no play are included with zeros.
     *
     * @return list<array{date: string, sessions: int, rounds: int}>
     */
    public function daily(User $user, int $days = 7): array
    {
        $start = CarbonImmutable::instance(Date::now())->startOfDay()->subDays($days - 1);

        $sessions = GameSession::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at at time zone ?) as day, count(*) as total', [config('app.timezone')])
            ->groupBy('day')
            ->toBase()
            ->pluck('total', 'day');

        $rounds = DemoWalletTransaction::query()
            ->whereIn('wallet_id', $user->demoWallets()->select('id'))
            ->where('type', DemoTransactionType::Bet->value)
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at at time zone ?) as day, count(*) as total', [config('app.timezone')])
            ->groupBy('day')
            ->toBase()
            ->pluck('total', 'day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->addDays($i)->toDateString();
            $series[] = [
                'date' => $day,
                'sessions' => (int) ($sessions[$day] ?? 0),
                'rounds' => (int) ($rounds[$day] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * Distinct games the player launched most recently, newest first.
     *
     * @return Collection<int, string> game ids
     */
    public function recentGameIds(User $user, int $limit = 4): Collection
    {
        return GameSession::query()
            ->where('user_id', $user->id)
            ->selectRaw('game_id, max(created_at) as last_played')
            ->groupBy('game_id')
            ->orderByDesc('last_played')
            ->limit($limit)
            ->toBase()
            ->pluck('game_id')
            ->map(fn (mixed $id): string => (string) $id);
    }

    /**
     * @param  array{rounds: int, staked: int, won: int}  $totals
     * @return array<string, mixed>
     */
    public function presentSession(GameSession $session, array $totals): array
    {
        return [
            'reference' => $session->session_reference,
            'game' => [
                'slug' => $session->game->slug,
                'name' => $session->game->name,
            ],
            'status' => $session->status->value,
            'started_at' => $session->created_at?->toIso8601String(),
            'ended_at' => $session->ended_at?->toIso8601String(),
            'rounds' => $totals['rounds'],
            'staked' => $totals['staked'],
            'won' => $totals['won'],
            'net' => $totals['won'] - $totals['staked'],
        ];
    }
}
