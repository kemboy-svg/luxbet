<?php

namespace App\Domain\DemoWallet\Enums;

enum DemoWalletStatus: string
{
    case Active = 'active';
    case Locked = 'locked';
    case Closed = 'closed';
}
