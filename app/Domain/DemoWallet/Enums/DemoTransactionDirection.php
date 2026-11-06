<?php

namespace App\Domain\DemoWallet\Enums;

enum DemoTransactionDirection: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    public function apply(int $balance, int $amount): int
    {
        return $this === self::Credit ? $balance + $amount : $balance - $amount;
    }
}
