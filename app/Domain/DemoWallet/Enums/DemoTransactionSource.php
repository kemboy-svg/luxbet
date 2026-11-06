<?php

namespace App\Domain\DemoWallet\Enums;

enum DemoTransactionSource: string
{
    case System = 'system';
    case ProviderSandbox = 'provider_sandbox';
    case Admin = 'admin';
}
