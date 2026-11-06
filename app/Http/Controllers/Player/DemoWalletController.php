<?php

namespace App\Http\Controllers\Player;

use App\Domain\DemoWallet\Actions\EnsureDemoWallet;
use App\Domain\DemoWallet\Actions\ResetDemoAllowance;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Exceptions\DemoWalletException;
use App\Http\Controllers\Controller;
use App\Http\Resources\DemoWalletResource;
use App\Http\Resources\DemoWalletTransactionResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DemoWalletController extends Controller
{
    public function show(Request $request, EnsureDemoWallet $ensure, ResetDemoAllowance $reset): Response
    {
        /** @var User $user */
        $user = $request->user();
        $wallet = $ensure($user);

        $validated = $request->validate([
            'type' => ['nullable', Rule::enum(DemoTransactionType::class)],
        ]);
        $type = $validated['type'] ?? null;

        $transactions = $wallet->transactions()
            ->when($type, fn ($query, $type) => $query->where('type', $type))
            ->orderByDesc('created_at')
            ->orderByDesc('reference')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('DemoWallet', [
            'wallet' => (new DemoWalletResource($wallet))->resolve($request),
            'transactions' => DemoWalletTransactionResource::collection($transactions),
            'filters' => ['type' => $type],
            'types' => collect(DemoTransactionType::cases())
                ->map(fn (DemoTransactionType $t): array => ['value' => $t->value, 'label' => $t->label()])
                ->all(),
            'allowance' => (int) config('luxbet.demo_wallet.initial_allowance'),
            'resetAvailable' => $reset->availableFor($wallet),
        ]);
    }

    public function reset(Request $request, EnsureDemoWallet $ensure, ResetDemoAllowance $reset): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $wallet = $ensure($user);

        Gate::authorize('reset', $wallet);

        try {
            $reset($wallet, $user);
        } catch (DemoWalletException $e) {
            return back()->withErrors(['reset' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Demo credits reset.']);

        return back();
    }
}
