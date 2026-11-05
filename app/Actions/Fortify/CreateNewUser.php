<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Domain\Players\Actions\RegisterPlayer;
use App\Domain\Players\Models\PlayerProfile;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly RegisterPlayer $registerPlayer) {}

    /**
     * Validate and create a newly registered demo player.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'username' => [
                'required',
                'string',
                'regex:/^[A-Za-z0-9_]{3,20}$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && PlayerProfile::query()->whereUsername($value)->exists()) {
                        $fail('That username is already taken.');
                    }
                },
            ],
            'password' => $this->passwordRules(),
            'age_confirmed' => ['accepted'],
            'terms_accepted' => ['accepted'],
        ], [
            'username.regex' => 'Use 3 to 20 letters, numbers or underscores.',
            'age_confirmed.accepted' => 'You must be 18 or older to create an account.',
            'terms_accepted.accepted' => 'Please accept the Terms of demo use and Privacy notice.',
        ])->validate();

        return ($this->registerPlayer)(
            name: $input['name'],
            email: $input['email'],
            password: $input['password'],
            username: $input['username'],
        );
    }
}
