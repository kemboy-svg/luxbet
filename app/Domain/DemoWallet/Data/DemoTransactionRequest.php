<?php

namespace App\Domain\DemoWallet\Data;

use App\Domain\DemoWallet\Enums\DemoTransactionDirection;
use App\Domain\DemoWallet\Enums\DemoTransactionSource;
use App\Domain\DemoWallet\Enums\DemoTransactionType;

/**
 * A requested change to a demo wallet. The resulting balance is always
 * computed by the server from the locked wallet row, never supplied.
 */
final readonly class DemoTransactionRequest
{
    public function __construct(
        public string $walletId,
        public DemoTransactionType $type,
        public int $amount,
        public DemoTransactionSource $source,
        public string $description,
        public ?DemoTransactionDirection $direction = null,
        public ?string $idempotencyKey = null,
        public ?string $externalReference = null,
        public ?string $createdBy = null,
    ) {}

    public function resolvedDirection(): ?DemoTransactionDirection
    {
        return $this->type->fixedDirection() ?? $this->direction;
    }
}
