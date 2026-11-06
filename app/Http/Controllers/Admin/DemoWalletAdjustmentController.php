<?php

namespace App\Http\Controllers\Admin;

use App\Domain\DemoWallet\Actions\AdjustDemoBalance;
use App\Domain\DemoWallet\Actions\EnsureDemoWallet;
use App\Domain\DemoWallet\Exceptions\DemoWalletException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustDemoWalletRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Staff adjustment of a player's demo credits. Demo-only, reasoned and
 * audited; it never touches anything with cash value.
 */
class DemoWalletAdjustmentController extends Controller
{
    public function __invoke(
        AdjustDemoWalletRequest $request,
        User $player,
        EnsureDemoWallet $ensure,
        AdjustDemoBalance $adjust,
    ): RedirectResponse {
        /** @var User $staff */
        $staff = $request->user();
        $wallet = $ensure($player);

        Gate::authorize('adjust', $wallet);

        try {
            $adjust($wallet, $request->direction(), $request->amount(), $request->reason(), $staff, $request->idempotencyKey());
        } catch (DemoWalletException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Demo credits adjusted.']);

        return back();
    }
}
