<?php

namespace App\Domain\Compliance\Enums;

enum EnvironmentMode: string
{
    case Demo = 'demo';
    case Staging = 'staging';
    case Production = 'production';

    /**
     * Resolve the configured mode, falling back to demo for anything unknown.
     */
    public static function fromConfig(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? self::Demo) : self::Demo;
    }
}
