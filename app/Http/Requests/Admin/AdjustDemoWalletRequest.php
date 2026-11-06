<?php

namespace App\Http\Requests\Admin;

use App\Domain\DemoWallet\Enums\DemoTransactionDirection;
use App\Domain\Identity\Authorization\Permission;
use App\Domain\Identity\Authorization\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustDemoWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        $player = $this->route('player');
        $actor = $this->user();

        return $player instanceof User
            && $actor instanceof User
            && $actor->can(Permission::DemoWalletAdjust->value)
            && ! $actor->is($player)
            && $player->hasRole(Role::Player->value)
            && ! $player->isStaff();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::enum(DemoTransactionDirection::class)],
            'amount' => ['required', 'integer', 'min:1', 'max:'.(int) config('luxbet.demo_wallet.max_admin_adjustment')],
            'reason' => ['required', 'string', 'min:5', 'max:200'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function direction(): DemoTransactionDirection
    {
        return DemoTransactionDirection::from($this->string('direction')->toString());
    }

    public function amount(): int
    {
        return $this->integer('amount');
    }

    public function reason(): string
    {
        return trim($this->string('reason')->toString());
    }

    public function idempotencyKey(): string
    {
        return 'admin-adjustment:'.strtolower($this->string('idempotency_key')->toString());
    }
}
