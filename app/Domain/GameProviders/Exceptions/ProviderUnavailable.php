<?php

namespace App\Domain\GameProviders\Exceptions;

use RuntimeException;

class ProviderUnavailable extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
