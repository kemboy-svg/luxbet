<?php

namespace App\Http\Controllers\Admin;

use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Models\DemoWalletTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only view of the demo-credit ledger across all players
 * (demo_wallet.view). Amounts are demo credits with no cash value.
 */
class DemoTransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::enum(DemoTransactionType::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $type = $validated['type'] ?? null;
        $search = trim((string) ($validated['search'] ?? ''));

        $transactions = DemoWalletTransaction::query()
            ->with('wallet.user.playerProfile')
            ->when($type, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.mb_strtolower(addcslashes($search, '%_\\')).'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->whereRaw('lower(reference) like ?', [$like])
                        ->orWhereHas('wallet.user', fn (Builder $user) => $user->whereRaw('lower(email) like ?', [$like]));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('reference')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (DemoWalletTransaction $tx): array => [
                'reference' => $tx->reference,
                'player' => [
                    'id' => $tx->wallet->user_id,
                    'email' => $tx->wallet->user->email,
                    'username' => $tx->wallet->user->playerProfile?->username,
                ],
                'type' => $tx->type->value,
                'type_label' => $tx->type->label(),
                'source' => $tx->source->value,
                'signed_amount' => $tx->signedAmount(),
                'balance_after' => $tx->balance_after_minor,
                'description' => $tx->description,
                'created_at' => $tx->created_at->toIso8601String(),
            ]);

        return Inertia::render('admin/DemoTransactions', [
            'transactions' => $transactions,
            'filters' => ['type' => $type, 'search' => $search],
            'types' => collect(DemoTransactionType::cases())
                ->map(fn (DemoTransactionType $t): array => ['value' => $t->value, 'label' => $t->label()])
                ->all(),
        ]);
    }
}
