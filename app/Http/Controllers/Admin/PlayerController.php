<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Authorization\Role;
use App\Http\Controllers\Controller;
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
                'can_change_status' => $actor?->can('updatePlayerStatus', $player) === true,
            ]);

        return Inertia::render('admin/players/Index', [
            'players' => $players,
            'filters' => ['search' => $search],
        ]);
    }
}
