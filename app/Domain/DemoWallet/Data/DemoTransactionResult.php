<?php

namespace App\Domain\DemoWallet\Data;

use App\Domain\DemoWallet\Models\DemoWalletTransaction;

final readonly class DemoTransactionResult
{
    /**
     * @param  bool  $replayed  True when an earlier transaction with the same idempotency key was returned instead of applying a new one.
     */
    public function __construct(
        public DemoWalletTransaction $transaction,
        public bool $replayed,
    ) {}
}
