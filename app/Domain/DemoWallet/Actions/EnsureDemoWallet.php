<?php

namespace App\Domain\DemoWallet\Actions;

use App\Domain\DemoWallet\Data\DemoTransactionRequest;
use App\Domain\DemoWallet\Enums\DemoTransactionSource;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Models\DemoWallet;
use App\Models\User;

/**
 * Returns the player's demo wallet, creating it and granting the initial
 * allowance the first time. Safe to call repeatedly: the allowance grant is
 * idempotent per wallet.
 */
class EnsureDemoWallet
{
    public function __construct(private readonly RecordDemoTransaction $record) {}

    public function __invoke(User $user): DemoWallet
    {
        $currency = (string) config('luxbet.demo_wallet.currency');

        $wallet = DemoWallet::query()->where('user_id', $user->id)->where('currency_code', $currency)->first();

        if ($wallet === null) {
            $wallet = new DemoWallet;
            $wallet->forceFill(['user_id' => $user->id, 'currency_code' => $currency]);
            $wallet->saveOrFail();
        }

        $allowance = (int) config('luxbet.demo_wallet.initial_allowance');

        if ($allowance > 0) {
            ($this->record)(new DemoTransactionRequest(
                walletId: $wallet->id,
                type: DemoTransactionType::Credit,
                amount: $allowance,
                source: DemoTransactionSource::System,
                description: 'Welcome demo allowance',
                idempotencyKey: "initial-allowance:{$wallet->id}",
            ));
        }

        return $wallet->refresh();
    }
}
