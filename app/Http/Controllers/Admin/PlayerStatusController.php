<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Players\Actions\ChangeAccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlayerStatusRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PlayerStatusController extends Controller
{
    public function __invoke(UpdatePlayerStatusRequest $request, User $player, ChangeAccountStatus $changeStatus): RedirectResponse
    {
        $actor = $request->user();

        $changeStatus($player, $request->status(), $request->reason(), actor: $actor);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Player status updated.']);

        return back();
    }
}
