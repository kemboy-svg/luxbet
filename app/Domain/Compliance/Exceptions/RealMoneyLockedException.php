<?php

namespace App\Domain\Compliance\Exceptions;

use LogicException;

/**
 * Thrown when application code tries to switch real-money mode on, or to
 * remove the launch-control record. Neither is possible in this release.
 */
class RealMoneyLockedException extends LogicException
{
    public static function enabling(): self
    {
        return new self('Real-money mode is locked off in this release and cannot be enabled by application code.');
    }

    public static function deleting(): self
    {
        return new self('The launch-control record cannot be deleted by application code.');
    }
}
