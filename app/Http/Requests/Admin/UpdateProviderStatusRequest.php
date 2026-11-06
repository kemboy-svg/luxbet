<?php

namespace App\Http\Requests\Admin;

use App\Domain\Games\Enums\ProviderStatus;
use App\Domain\Identity\Authorization\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Staff may switch a provider between inactive, sandbox and suspended.
 * `active` (live production play) is not selectable in the demo release.
 */
class UpdateProviderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ProvidersManage->value) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ProviderStatus::class)->only([
                ProviderStatus::Inactive,
                ProviderStatus::Sandbox,
                ProviderStatus::Suspended,
            ])],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function status(): ProviderStatus
    {
        return ProviderStatus::from($this->string('status')->toString());
    }
}
