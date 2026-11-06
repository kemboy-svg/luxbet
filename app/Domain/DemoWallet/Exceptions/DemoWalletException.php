<?php

namespace App\Domain\DemoWallet\Exceptions;

use DomainException;

/**
 * A demo-wallet operation was refused. Nothing was changed.
 */
class DemoWalletException extends DomainException
{
    public static function invalidAmount(): self
    {
        return new self('Demo amounts must be a positive whole number of credits.');
    }

    public static function amountTooLarge(): self
    {
        return new self('That demo amount is larger than allowed.');
    }

    public static function insufficientCredits(): self
    {
        return new self('There are not enough demo credits for this.');
    }

    public static function walletNotActive(): self
    {
        return new self('This demo wallet is not active.');
    }

    public static function directionMismatch(): self
    {
        return new self('This transaction type cannot move credits in that direction.');
    }

    public static function idempotencyConflict(): self
    {
        return new self('This idempotency key was already used for a different demo transaction.');
    }

    public static function resetNotAvailable(): self
    {
        return new self('Your demo credits can be reset once a day, when your balance is below the starting allowance.');
    }
}
