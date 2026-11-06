<?php

namespace App\Domain\DemoWallet\Enums;

enum DemoTransactionType: string
{
    case Credit = 'demo_credit';
    case Bet = 'demo_bet';
    case Win = 'demo_win';
    case Refund = 'demo_refund';
    case Adjustment = 'demo_adjustment';

    /**
     * The fixed direction for each type. Adjustments can go either way and
     * return null here.
     */
    public function fixedDirection(): ?DemoTransactionDirection
    {
        return match ($this) {
            self::Credit, self::Win, self::Refund => DemoTransactionDirection::Credit,
            self::Bet => DemoTransactionDirection::Debit,
            self::Adjustment => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Demo credit',
            self::Bet => 'Demo bet',
            self::Win => 'Demo win',
            self::Refund => 'Demo refund',
            self::Adjustment => 'Demo adjustment',
        };
    }
}
