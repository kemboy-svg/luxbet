<?php

namespace App\Domain\DemoWallet\Actions;

use App\Domain\DemoWallet\Data\DemoTransactionRequest;
use App\Domain\DemoWallet\Data\DemoTransactionResult;
use App\Domain\DemoWallet\Enums\DemoTransactionSource;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Exceptions\DemoWalletException;
use App\Domain\DemoWallet\Models\DemoWallet;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * Tops a player's demo balance back up to the starting allowance. Allowed
 * once per UTC day and only when the balance is below the allowance. The
 * daily limit is enforced by the idempotency key, so double-clicks and
 * concurrent requests cannot grant twice.
 */
class ResetDemoAllowance
{
    public function __construct(private readonly RecordDemoTransaction $record) {}

    public function __invoke(DemoWallet $wallet, User $requestedBy): DemoTransactionResult
    {
        $allowance = (int) config('luxbet.demo_wallet.initial_allowance');
        $wallet->refresh();

        if ($this->usedToday($wallet) || $wallet->balance_minor >= $allowance) {
            throw DemoWalletException::resetNotAvailable();
        }

        return ($this->record)(new DemoTransactionRequest(
            walletId: $wallet->id,
            type: DemoTransactionType::Credit,
            amount: $allowance - $wallet->balance_minor,
            source: DemoTransactionSource::System,
            description: 'Allowance reset',
            idempotencyKey: self::keyFor($wallet, Date::now()),
            createdBy: $requestedBy->id,
        ));
    }

    public function availableFor(DemoWallet $wallet): bool
    {
        return $wallet->isActive()
            && $wallet->balance_minor < (int) config('luxbet.demo_wallet.initial_allowance')
            && ! $this->usedToday($wallet);
    }

    public static function keyFor(DemoWallet $wallet, CarbonInterface $day): string
    {
        return "allowance-reset:{$wallet->id}:".$day->utc()->toDateString();
    }

    private function usedToday(DemoWallet $wallet): bool
    {
        return $wallet->transactions()->where('idempotency_key', self::keyFor($wallet, Date::now()))->exists();
    }
}
