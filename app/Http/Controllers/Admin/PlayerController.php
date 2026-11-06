<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\DemoWallet\Actions\EnsureDemoWallet;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\Identity\Authorization\Permission;
use App\Domain\Identity\Authorization\Role;
use App\Domain\Players\Queries\PlayerActivity;
use App\Http\Controllers\Controller;
use App\Http\Resources\DemoWalletTransactionResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAnyPlayers', User::class);

        $search = trim($request->string('search')->toString());
        $actor = $request->user();

        $players = User::query()
            ->role(Role::Player->value)
            ->with('playerProfile')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.mb_strtolower(addcslashes($search, '%_\\')).'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->whereRaw('lower(email) like ?', [$like])
                        ->orWhereHas('playerProfile', fn (Builder $profile) => $profile->whereRaw('lower(username) like ?', [$like]));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $player): array => [
                'id' => $player->id,
                'username' => $player->playerProfile?->username,
                'email' => $player->email,
                'status' => $player->status->value,
                'email_verified' => $player->email_verified_at !== null,
                'joined_at' => $player->created_at?->toIso8601String(),
                'can_view' => true,
                'can_change_status' => $actor?->can('updatePlayerStatus', $player) === true,
            ]);

        return Inertia::render('admin/players/Index', [
            'players' => $players,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * One player's account, demo wallet and recent activity. Wallet ledger
     * rows need demo_wallet.view, audit entries need audit.view.
     */
    public function show(Request $request, User $player, EnsureDemoWallet $ensure, PlayerActivity $activity): Response
    {
        Gate::authorize('viewAnyPlayers', User::class);
        abort_unless($player->hasRole(Role::Player->value) && ! $player->isStaff(), 404);

        /** @var User $actor */
        $actor = $request->user();
        $player->load('playerProfile');
        $wallet = $ensure($player);

        $sessions = GameSession::query()
            ->where('user_id', $player->id)
            ->with('game')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $totals = $activity->totalsFor($sessions);

        $canViewWallet = $actor->can('view', $wallet);

        return Inertia::render('admin/players/Show', [
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
                'username' => $player->playerProfile?->username,
                'email' => $player->email,
                'status' => $player->status->value,
                'email_verified' => $player->email_verified_at !== null,
                'two_factor' => $player->hasConfirmedTwoFactor(),
                'country_code' => $player->playerProfile?->country_code,
                'preferred_locale' => $player->playerProfile?->preferred_locale,
                'joined_at' => $player->created_at?->toIso8601String(),
            ],
            'balance' => $canViewWallet ? $wallet->balance_minor : null,
            'sessions' => $sessions
                ->map(fn (GameSession $session): array => $activity->presentSession($session, $totals[$session->session_reference]))
                ->all(),
            'transactions' => $canViewWallet
                ? DemoWalletTransactionResource::collection(
                    $wallet->transactions()->orderByDesc('created_at')->orderByDesc('reference')->limit(15)->get(),
                )->resolve($request)
                : null,
            'audit' => $actor->can(Permission::AuditView->value)
                ? AuditLog::query()
                    ->where('subject_type', $player->getMorphClass())
                    ->where('subject_id', $player->id)
                    ->orderByDesc('created_at')
                    ->limit(15)
                    ->get()
                    ->map(fn (AuditLog $log): array => [
                        'id' => $log->id,
                        'action' => $log->action,
                        'reason' => $log->reason,
                        'created_at' => $log->created_at->toIso8601String(),
                    ])
                    ->all()
                : null,
            'can' => [
                'change_status' => $actor->can('updatePlayerStatus', $player),
                'adjust_wallet' => $actor->can('adjust', $wallet),
            ],
            'maxAdjustment' => (int) config('luxbet.demo_wallet.max_admin_adjustment'),
        ]);
    }
}
