<?php

namespace App\Domain\DemoWallet\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\DemoWallet\Data\DemoTransactionRequest;
use App\Domain\DemoWallet\Data\DemoTransactionResult;
use App\Domain\DemoWallet\Enums\DemoTransactionDirection;
use App\Domain\DemoWallet\Enums\DemoTransactionSource;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Models\DemoWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Staff adjustment of demo credits. Always labelled demo-only, requires a
 * reason, and writes an audit record in the same database transaction.
 * Never affects anything but the demo ledger.
 */
class AdjustDemoBalance
{
    public function __construct(
        private readonly RecordDemoTransaction $record,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(
        DemoWallet $wallet,
        DemoTransactionDirection $direction,
        int $amount,
        string $reason,
        User $staff,
        string $idempotencyKey,
    ): DemoTransactionResult {
        return DB::transaction(function () use ($wallet, $direction, $amount, $reason, $staff, $idempotencyKey): DemoTransactionResult {
            $result = ($this->record)(new DemoTransactionRequest(
                walletId: $wallet->id,
                type: DemoTransactionType::Adjustment,
                amount: $amount,
                source: DemoTransactionSource::Admin,
                description: 'Demo adjustment (no cash value): '.$reason,
                direction: $direction,
                idempotencyKey: $idempotencyKey,
                createdBy: $staff->id,
            ));

            if (! $result->replayed) {
                $this->audit->record('demo_wallet.adjusted', subject: $wallet, actor: $staff, reason: $reason, metadata: [
                    'direction' => $direction->value,
                    'amount' => $amount,
                    'transaction_reference' => $result->transaction->reference,
                    'demo_only' => true,
                ]);
            }

            return $result;
        });
    }
}
