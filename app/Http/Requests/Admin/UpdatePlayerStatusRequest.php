<?php

namespace App\Http\Requests\Admin;

use App\Domain\Identity\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlayerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $player = $this->route('player');

        return $player instanceof User
            && $this->user()?->can('updatePlayerStatus', $player) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(UserStatus::class)->only([
                UserStatus::Active,
                UserStatus::Suspended,
                UserStatus::Closed,
            ])],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function status(): UserStatus
    {
        return UserStatus::from($this->string('status')->toString());
    }

    public function reason(): string
    {
        return trim($this->string('reason')->toString());
    }
}
