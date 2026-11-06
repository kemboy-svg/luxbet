<?php

namespace Tests\Feature\Auth;

use App\Domain\Identity\Authorization\Role;
use App\Domain\Players\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), $this->validInput());

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_creates_a_player_with_profile_role_and_audit_record()
    {
        $this->post(route('register.store'), $this->validInput());

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole(Role::Player->value));
        $this->assertFalse($user->isStaff());
        $this->assertTrue($user->isActive());

        $profile = $user->playerProfile;
        $this->assertNotNull($profile);
        $this->assertSame('test_player', $profile->username);
        $this->assertNotNull($profile->age_confirmed_at);
        $this->assertNotNull($profile->terms_accepted_at);
        $this->assertSame(config('luxbet.terms_version'), $profile->terms_version);
        $this->assertNull($profile->date_of_birth);

        $this->assertDatabaseHas('audit_logs', ['action' => 'player.registered', 'subject_id' => $user->id]);

        $wallet = $user->demoWallets()->sole();
        $this->assertSame('XXX', $wallet->currency_code);
        $this->assertSame(config('luxbet.demo_wallet.initial_allowance'), $wallet->balance_minor);
    }

    public function test_age_confirmation_and_terms_acceptance_are_required()
    {
        $this->post(route('register.store'), $this->validInput(['age_confirmed' => null, 'terms_accepted' => null]))
            ->assertSessionHasErrors(['age_confirmed', 'terms_accepted']);

        $this->assertGuest();
    }

    public function test_usernames_must_be_valid_and_unique_ignoring_case()
    {
        PlayerProfile::factory()->create(['username' => 'Lucky_Star']);

        $this->post(route('register.store'), $this->validInput(['username' => 'lucky_star']))
            ->assertSessionHasErrors('username');

        $this->post(route('register.store'), $this->validInput(['username' => 'no spaces!']))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_registration_is_rate_limited()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('register.store'), $this->validInput(['email' => "bad-{$i}", 'username' => "user_{$i}"]));
        }

        $this->post(route('register.store'), $this->validInput())->assertStatus(429);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validInput(array $overrides = []): array
    {
        return array_filter([
            'name' => 'Test User',
            'username' => 'test_player',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'age_confirmed' => '1',
            'terms_accepted' => '1',
            ...$overrides,
        ], fn (mixed $value): bool => $value !== null);
    }
}
