<?php

namespace App\Domain\GameProviders\Enums;

use App\Domain\DemoWallet\Enums\DemoTransactionType;

/**
 * The only provider event types accepted. Anything else, including any
 * real-money event type, is rejected.
 */
enum DemoEventType: string
{
    case Bet = 'demo.bet';
    case Win = 'demo.win';
    case Refund = 'demo.refund';

    public function ledgerType(): DemoTransactionType
    {
        return match ($this) {
            self::Bet => DemoTransactionType::Bet,
            self::Win => DemoTransactionType::Win,
            self::Refund => DemoTransactionType::Refund,
        };
    }
}
