<?php

namespace App\Domain\Games\Enums;

enum ProviderStatus: string
{
    case Inactive = 'inactive';
    case Sandbox = 'sandbox';
    case Active = 'active';
    case Suspended = 'suspended';

    /**
     * Whether games from a provider in this status may be listed at all.
     */
    public function isListable(): bool
    {
        return $this === self::Sandbox || $this === self::Active;
    }
}
