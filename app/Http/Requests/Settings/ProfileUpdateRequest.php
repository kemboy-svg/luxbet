<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Domain\Players\Models\PlayerProfile;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    public const LOCALES = ['en', 'fr'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            ...$this->profileRules($user->id),
            'username' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[A-Za-z0-9_]{3,20}$/',
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    if (is_string($value) && PlayerProfile::query()->whereUsername($value)->where('user_id', '!=', $user->id)->exists()) {
                        $fail('That username is already taken.');
                    }
                },
            ],
            'country_code' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Z]{2}$/'],
            'preferred_locale' => ['sometimes', 'required', 'in:'.implode(',', self::LOCALES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['username.regex' => 'Use 3 to 20 letters, numbers or underscores.'];
    }
}
