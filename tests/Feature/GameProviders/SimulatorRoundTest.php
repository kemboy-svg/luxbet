<?php

namespace Tests\Feature\GameProviders;

use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\GameProviders\Enums\WebhookProcessingStatus;
use App\Domain\GameProviders\Models\ProviderWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithSimulator;
use Tests\TestCase;

class SimulatorRoundTest extends TestCase
{
    use InteractsWithSimulator, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSimulator();
    }

    public function test_a_slot_round_goes_through_signed_events_and_the_demo_ledger(): void
    {
        $player = $this->playerUser();
        $session = $this->launchSession($player);

        $response = $this->actingAs($player)
            ->postJson("/api/v1/game-sessions/{$session->session_reference}/simulator-rounds", ['stake' => 100])
            ->assertOk()
            ->assertJsonPath('data.stake', 100)
            ->assertJsonPath('data.demo', true)
            ->assertJsonPath('data.display.kind', 'slots')
            // candy-crown (the default launched game) uses the low-volatility profile.
            ->assertJsonPath('data.display.profile', 'low')
            ->assertJsonCount(5, 'data.display.reels');

        $payout = (int) $response->json('data.payout');
        $this->assertSame(10_000 - 100 + $payout, $response->json('data.balance'));
        $this->assertSame(10_000 - 100 + $payout, $player->demoWallets()->sole()->balance_minor);

        $receipts = ProviderWebhookEvent::query()->get();
        $this->assertCount($payout > 0 ? 2 : 1, $receipts);
        $this->assertTrue($receipts->every(fn ($r) => $r->processing_status === WebhookProcessingStatus::Processed));

        $bet = $player->demoWallets()->sole()->transactions()->where('type', DemoTransactionType::Bet->value)->sole();
        $this->assertStringStartsWith($session->session_reference.':r_', (string) $bet->external_reference);
    }

    public function test_table_games_deal_cards(): void
    {
        $player = $this->playerUser();
        $session = $this->launchSession($player, 'royal-21');

        $this->actingAs($player)
            ->postJson("/api/v1/game-sessions/{$session->session_reference}/simulator-rounds", ['stake' => 50])
            ->assertOk()
            ->assertJsonPath('data.display.kind', 'blackjack')
            ->assertJsonCount(2, 'data.display.player_cards');
    }

    public function test_only_configured_stakes_are_accepted(): void
    {
        $player = $this->playerUser();
        $session = $this->launchSession($player);

        $this->actingAs($player)
            ->postJson("/api/v1/game-sessions/{$session->session_reference}/simulator-rounds", ['stake' => 123])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'DEMO_SESSION_REFUSED');

        $this->assertSame(10_000, $player->demoWallets()->sole()->balance_minor);
    }

    public function test_a_player_without_enough_credits_is_told_how_to_reset(): void
    {
        $player = $this->playerUser();
        $session = $this->launchSession($player);
        DB::table('demo_wallets')->where('user_id', $player->id)->update(['balance_minor' => 20]);

        $this->actingAs($player)
            ->postJson("/api/v1/game-sessions/{$session->session_reference}/simulator-rounds", ['stake' => 50])
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'Not enough demo credits for that stake. You can reset your credits from the Demo wallet page.');

        $this->assertSame(20, $player->demoWallets()->sole()->balance_minor);
    }

    public function test_players_cannot_play_someone_elses_session(): void
    {
        $session = $this->launchSession($this->playerUser());

        $this->actingAs($this->playerUser())
            ->postJson("/api/v1/game-sessions/{$session->session_reference}/simulator-rounds", ['stake' => 100])
            ->assertNotFound();
    }

    public function test_ended_sessions_cannot_be_played(): void
    {
        $player = $this->playerUser();
        $session = $this->launchSession($player);
        $this->actingAs($player)->post("/play/{$session->session_reference}/end");

        $this->actingAs($player)
            ->postJson("/api/v1/game-sessions/{$session->session_reference}/simulator-rounds", ['stake' => 100])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'DEMO_SESSION_REFUSED');
    }
}
