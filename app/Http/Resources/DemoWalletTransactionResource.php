<?php

namespace App\Http\Resources;

use App\Domain\DemoWallet\Models\DemoWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Player-facing view of a demo ledger row. Internal fields (idempotency
 * keys, creator IDs) are not exposed.
 *
 * @mixin DemoWalletTransaction
 */
class DemoWalletTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'direction' => $this->direction->value,
            'amount' => $this->amount_minor,
            'signed_amount' => $this->signedAmount(),
            'balance_after' => $this->balance_after_minor,
            'description' => $this->description,
            'created_at' => $this->created_at->toIso8601String(),
            'demo' => true,
        ];
    }
}
