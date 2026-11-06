<?php

namespace App\Domain\GameProviders\Exceptions;

use RuntimeException;

/**
 * The webhook could not be trusted or understood. Nothing was processed.
 */
class InvalidProviderWebhook extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'INVALID_WEBHOOK', public readonly int $status = 400)
    {
        parent::__construct($message);
    }

    public static function signature(): self
    {
        return new self('The webhook signature is missing or invalid.', 'INVALID_SIGNATURE', 401);
    }

    public static function stale(): self
    {
        return new self('The webhook timestamp is outside the allowed window.', 'STALE_WEBHOOK', 401);
    }

    public static function schema(string $detail): self
    {
        return new self('The webhook payload is invalid: '.$detail, 'INVALID_PAYLOAD', 422);
    }

    public static function tooLarge(): self
    {
        return new self('The webhook payload is too large.', 'PAYLOAD_TOO_LARGE', 413);
    }
}
