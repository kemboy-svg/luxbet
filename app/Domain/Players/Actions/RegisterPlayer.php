<?php

namespace App\Domain\Players\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\DemoWallet\Actions\EnsureDemoWallet;
use App\Domain\Identity\Authorization\Role;
use App\Domain\Players\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a demo player account: user, profile, player role and demo
 * wallet (with its starting allowance) together.
 * Input must already be validated.
 */
class RegisterPlayer
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly EnsureDemoWallet $ensureDemoWallet,
    ) {}

    public function __invoke(string $name, string $email, string $password, string $username): User
    {
        return DB::transaction(function () use ($name, $email, $password, $username): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $profile = new PlayerProfile(['username' => $username, 'preferred_locale' => app()->getLocale()]);
            $profile->forceFill([
                'age_confirmed_at' => now(),
                'terms_accepted_at' => now(),
                'terms_version' => config('luxbet.terms_version'),
            ]);
            $user->playerProfile()->save($profile);

            $user->assignRole(Role::Player->value);

            ($this->ensureDemoWallet)($user);

            $this->audit->record('player.registered', subject: $user, actor: $user, metadata: [
                'terms_version' => config('luxbet.terms_version'),
            ]);

            return $user;
        });
    }
}
