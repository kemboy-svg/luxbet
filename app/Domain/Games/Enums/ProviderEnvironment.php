<?php

namespace App\Domain\Games\Enums;

enum ProviderEnvironment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';
}
