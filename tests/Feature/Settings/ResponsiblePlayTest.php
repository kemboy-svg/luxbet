<?php

namespace Tests\Feature\Settings;

use App\Domain\ResponsiblePlay\Models\ResponsiblePlaySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResponsiblePlayTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_shows_safe_defaults(): void
    {
        $this->actingAs($this->playerUser())
            ->get(route('responsible-play.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/ResponsiblePlay')
                ->where('reminderMinutes', ResponsiblePlaySetting::DEFAULT_REMINDER_MINUTES)
                ->where('marketingOptIn', false)
                ->where('reminderOptions', ResponsiblePlaySetting::REMINDER_OPTIONS));
    }

    public function test_settings_can_be_updated_and_are_shared_with_pages(): void
    {
        $player = $this->playerUser();

        $this->actingAs($player)
            ->patch(route('responsible-play.update'), [
                'demo_session_reminder_minutes' => 15,
                'marketing_opt_in' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('responsible-play.edit'));

        $this->assertDatabaseHas('responsible_play_settings', [
            'user_id' => $player->id,
            'demo_session_reminder_minutes' => 15,
            'marketing_opt_in' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'player.marketing_preference_changed',
            'subject_id' => $player->id,
        ]);

        $this->actingAs($player)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('luxbet.reminderMinutes', 15));
    }

    public function test_reminders_can_be_switched_off(): void
    {
        $player = $this->playerUser();

        $this->actingAs($player)
            ->patch(route('responsible-play.update'), [
                'demo_session_reminder_minutes' => null,
                'marketing_opt_in' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull(ResponsiblePlaySetting::forUser($player)->demo_session_reminder_minutes);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'player.marketing_preference_changed']);
    }

    public function test_only_offered_reminder_intervals_are_accepted(): void
    {
        $this->actingAs($this->playerUser())
            ->patch(route('responsible-play.update'), [
                'demo_session_reminder_minutes' => 7,
                'marketing_opt_in' => false,
            ])
            ->assertSessionHasErrors('demo_session_reminder_minutes');
    }
}
