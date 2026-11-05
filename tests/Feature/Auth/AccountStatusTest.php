<?php

namespace Tests\Feature\Auth;

use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Players\Actions\ChangeAccountStatus;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_closed_and_pending_accounts_cannot_sign_in(): void
    {
        foreach ([UserStatus::Suspended, UserStatus::Closed, UserStatus::Pending] as $status) {
            $user = User::factory()->withStatus($status)->create();

            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
                ->assertSessionHasErrors(['email' => EnsureAccountIsActive::MESSAGE]);

            $this->assertGuest();
        }
    }

    public function test_a_wrong_password_does_not_reveal_account_status(): void
    {
        $user = User::factory()->withStatus(UserStatus::Suspended)->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    public function test_active_accounts_can_sign_in(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_signed_in_user_is_signed_out_once_suspended(): void
    {
        $user = $this->playerUser();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        app(ChangeAccountStatus::class)($user, UserStatus::Suspended, 'Suspended during test');

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_suspending_revokes_the_remember_me_token(): void
    {
        $user = $this->playerUser();
        $user->forceFill(['remember_token' => 'original-token'])->save();

        app(ChangeAccountStatus::class)($user, UserStatus::Suspended, 'Suspended during test');

        $this->assertNotSame('original-token', $user->fresh()?->remember_token);
    }
}
