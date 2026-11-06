<?php

namespace Tests;

use App\Domain\Identity\Authorization\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests never depend on a built frontend.
        $this->withoutVite();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * A staff member with confirmed two-factor authentication, ready to use
     * the admin area.
     */
    protected function staffUser(Role $role): User
    {
        return User::factory()->withTwoFactor()->staff($role)->create();
    }

    protected function playerUser(): User
    {
        return User::factory()->player()->create();
    }
}
