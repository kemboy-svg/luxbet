<?php

namespace Tests\Feature;

use App\Domain\GameProviders\Models\GameSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithSimulator;
use Tests\TestCase;

/**
 * Laravel stores timestamps without an offset, so PostgreSQL must interpret
 * them in the app's time zone whatever the server's own setting is.
 * CI runs PostgreSQL in Indian/Mauritius time to keep this honest.
 */
class DatabaseTimezoneTest extends TestCase
{
    use InteractsWithSimulator, RefreshDatabase;

    public function test_the_database_session_uses_the_app_time_zone(): void
    {
        $this->assertSame('UTC', DB::selectOne("select current_setting('TimeZone') as tz")->tz);
    }

    public function test_a_new_game_session_is_not_already_expired(): void
    {
        $this->seedSimulator();
        $session = $this->launchSession($this->playerUser());

        $stored = GameSession::query()->findOrFail($session->id);

        $this->assertTrue($stored->expires_at->isFuture());
        $this->assertEqualsWithDelta(30, now()->diffInMinutes($stored->expires_at), 1);
    }
}
