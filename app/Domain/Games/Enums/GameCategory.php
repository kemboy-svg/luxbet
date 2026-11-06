<?php

namespace App\Domain\Games\Enums;

enum GameCategory: string
{
    case Slots = 'slots';
    case LiveCasino = 'live_casino';
    case Blackjack = 'blackjack';
    case Baccarat = 'baccarat';
    case Poker = 'poker';
    case Crash = 'crash';

    public function label(): string
    {
        return match ($this) {
            self::Slots => 'Slots',
            self::LiveCasino => 'Live Casino',
            self::Blackjack => 'Blackjack',
            self::Baccarat => 'Baccarat',
            self::Poker => 'Poker',
            self::Crash => 'Crash Games',
        };
    }
}
