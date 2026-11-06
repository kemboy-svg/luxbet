<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Behaviour while email verification is switched off
 * (luxbet.require_email_verification = false), the current default.
 */
class EmailVerificationOptionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['luxbet.require_email_verification' => false]);
    }

    public function test_registration_sends_no_verification_email_and_lands_on_the_dashboard(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Test User',
            'username' => 'no_mail_player',
            'email' => 'nomail@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'age_confirmed' => '1',
            'terms_accepted' => '1',
        ])->assertRedirect(route('dashboard', absolute: false));

        Notification::assertNothingSent();

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_unverified_players_can_use_the_site(): void
    {
        $player = User::factory()->unverified()->player()->create();

        $this->actingAs($player)->get(route('dashboard'))->assertOk();
        $this->actingAs($player)->get(route('demo-wallet.show'))->assertOk();
        $this->actingAs($player)->getJson('/api/v1/demo-wallets')->assertOk();
    }

    public function test_the_profile_page_does_not_ask_for_verification(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('profile.edit'))
            ->assertInertia(fn (Assert $page) => $page->where('mustVerifyEmail', false));
    }

    public function test_turning_verification_back_on_restores_the_requirement(): void
    {
        config(['luxbet.require_email_verification' => true]);
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));

        $user->sendEmailVerificationNotification();
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
