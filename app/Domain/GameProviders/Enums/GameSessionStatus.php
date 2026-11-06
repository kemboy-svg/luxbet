<?php

namespace App\Domain\GameProviders\Enums;

enum GameSessionStatus: string
{
    case Created = 'created';
    case Launched = 'launched';
    case Completed = 'completed';
    case Expired = 'expired';
    case Failed = 'failed';
}
