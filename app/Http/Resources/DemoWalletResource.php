<?php

namespace App\Http\Resources;

use App\Domain\DemoWallet\Models\DemoWallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DemoWallet
 */
class DemoWalletResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'balance' => $this->balance_minor,
            'unit' => 'demo_credits',
            'currency_code' => $this->currency_code,
            'status' => $this->status->value,
            'demo' => true,
            'cash_value' => null,
        ];
    }
}
