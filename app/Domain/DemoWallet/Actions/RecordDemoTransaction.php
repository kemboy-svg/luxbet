<?php

namespace App\Domain\DemoWallet\Actions;

use App\Domain\DemoWallet\Data\DemoTransactionRequest;
use App\Domain\DemoWallet\Data\DemoTransactionResult;
use App\Domain\DemoWallet\Enums\DemoTransactionDirection;
use App\Domain\DemoWallet\Exceptions\DemoWalletException;
use App\Domain\DemoWallet\Models\DemoWallet;
use App\Domain\DemoWallet\Models\DemoWalletTransaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The single way a demo balance changes.
 *
 * - Runs in a database transaction with the wallet row locked (SELECT ... FOR
 *   UPDATE), so concurrent operations on one wallet are serialised.
 * - Writes exactly one ledger row per balance change, with before/after
 *   balances computed here from the locked row.
 * - Idempotent: repeating a request with the same idempotency key returns the
 *   original transaction and changes nothing; reusing a key for a different
 *   request is refused.
 * - Refuses zero, negative or oversized amounts, overdrafts, and inactive
 *   wallets. A refused request changes nothing.
 */
class RecordDemoTransaction
{
    public function __invoke(DemoTransactionRequest $request): DemoTransactionResult
    {
        $this->validate($request);

        if ($request->idempotencyKey !== null && ($existing = $this->findByKey($request->idempotencyKey)) !== null) {
            return $this->replay($existing, $request);
        }

        try {
            return DB::transaction(fn (): DemoTransactionResult => $this->apply($request));
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent request with the same key won the race.
            if ($request->idempotencyKey !== null && ($existing = $this->findByKey($request->idempotencyKey)) !== null) {
                return $this->replay($existing, $request);
            }

            throw $e;
        }
    }

    private function apply(DemoTransactionRequest $request): DemoTransactionResult
    {
        /** @var DemoWallet $wallet */
        $wallet = DemoWallet::query()->whereKey($request->walletId)->lockForUpdate()->firstOrFail();

        if ($request->idempotencyKey !== null && ($existing = $this->findByKey($request->idempotencyKey)) !== null) {
            return $this->replay($existing, $request);
        }

        if (! $wallet->isActive()) {
            throw DemoWalletException::walletNotActive();
        }

        /** @var DemoTransactionDirection $direction validated above */
        $direction = $request->resolvedDirection();
        $before = $wallet->balance_minor;
        $after = $direction->apply($before, $request->amount);

        if ($after < 0) {
            throw DemoWalletException::insufficientCredits();
        }

        $transaction = new DemoWalletTransaction;
        $transaction->forceFill([
            'wallet_id' => $wallet->id,
            'reference' => 'DTX-'.Str::upper(Str::random(12)),
            'type' => $request->type,
            'direction' => $direction,
            'amount_minor' => $request->amount,
            'balance_before_minor' => $before,
            'balance_after_minor' => $after,
            'source' => $request->source,
            'external_reference' => $request->externalReference,
            'idempotency_key' => $request->idempotencyKey,
            'description' => Str::limit($request->description, 250, ''),
            'created_by' => $request->createdBy,
        ])->save();

        $wallet->forceFill(['balance_minor' => $after])->save();

        return new DemoTransactionResult($transaction, replayed: false);
    }

    private function validate(DemoTransactionRequest $request): void
    {
        if ($request->amount <= 0) {
            throw DemoWalletException::invalidAmount();
        }

        if ($request->amount > (int) config('luxbet.demo_wallet.max_transaction')) {
            throw DemoWalletException::amountTooLarge();
        }

        $fixed = $request->type->fixedDirection();

        if ($request->resolvedDirection() === null || ($fixed !== null && $request->direction !== null && $request->direction !== $fixed)) {
            throw DemoWalletException::directionMismatch();
        }

        if ($request->idempotencyKey !== null && ($request->idempotencyKey === '' || strlen($request->idempotencyKey) > 191)) {
            throw new DemoWalletException('Idempotency keys must be 1 to 191 characters.');
        }
    }

    private function findByKey(string $key): ?DemoWalletTransaction
    {
        return DemoWalletTransaction::query()->where('idempotency_key', $key)->first();
    }

    private function replay(DemoWalletTransaction $existing, DemoTransactionRequest $request): DemoTransactionResult
    {
        $matches = $existing->wallet_id === $request->walletId
            && $existing->type === $request->type
            && $existing->direction === $request->resolvedDirection()
            && $existing->amount_minor === $request->amount;

        if (! $matches) {
            throw DemoWalletException::idempotencyConflict();
        }

        return new DemoTransactionResult($existing, replayed: true);
    }
}
