<?php

namespace Tests\Feature\GameProviders;

use App\Domain\DemoWallet\Models\DemoWallet;
use App\Domain\GameProviders\Enums\WebhookProcessingStatus;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\GameProviders\Models\ProviderWebhookEvent;
use App\Domain\GameProviders\Simulator\SimulatorSigner;
use App\Domain\GameProviders\Support\ProviderSecrets;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\InteractsWithSimulator;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use InteractsWithSimulator, RefreshDatabase;

    private User $player;

    private GameSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSimulator();
        $this->player = $this->playerUser();
        $this->session = $this->launchSession($this->player);
    }

    public function test_a_signed_bet_is_recorded_once_and_charged_to_the_sessions_wallet(): void
    {
        $this->sendWebhook($this->event($this->session, 'demo.bet', 250))
            ->assertOk()
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.balance_after', 9_750);

        $receipt = ProviderWebhookEvent::query()->sole();
        $this->assertSame(WebhookProcessingStatus::Processed, $receipt->processing_status);
        $this->assertSame($this->session->id, $receipt->game_session_id);
        $this->assertSame(9_750, $this->wallet()->balance_minor);
    }

    public function test_invalid_signatures_are_rejected_before_anything_is_stored(): void
    {
        $this->sendWebhook($this->event($this->session, 'demo.win', 5_000), secret: 'wrong-secret')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_SIGNATURE');

        $body = json_encode($this->event($this->session, 'demo.win', 5_000));
        $this->call('POST', '/api/v1/webhooks/providers/local-simulator', content: (string) $body)
            ->assertStatus(401);

        $this->assertSame(0, ProviderWebhookEvent::query()->count());
        $this->assertSame(10_000, $this->wallet()->balance_minor);
    }

    public function test_a_tampered_body_fails_the_signature_check(): void
    {
        $original = json_encode($this->event($this->session, 'demo.win', 10), JSON_THROW_ON_ERROR);
        $timestamp = Date::now()->getTimestamp();
        $headers = SimulatorSigner::headers(
            $original,
            $timestamp,
            app(ProviderSecrets::class)->webhookSecret($this->simulator()),
        );
        $tampered = str_replace('"amount":10', '"amount":900000', $original);

        $this->call('POST', '/api/v1/webhooks/providers/local-simulator',
            server: $this->transformHeadersToServerVars($headers),
            content: $tampered,
        )->assertStatus(401);

        $this->assertSame(10_000, $this->wallet()->balance_minor);
    }

    public function test_replayed_old_webhooks_are_rejected(): void
    {
        $this->sendWebhook($this->event($this->session, 'demo.win', 100), timestamp: Date::now()->subMinutes(10)->getTimestamp())
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'STALE_WEBHOOK');

        $this->assertSame(10_000, $this->wallet()->balance_minor);
    }

    public function test_a_repeated_event_id_has_no_further_effect(): void
    {
        $event = $this->event($this->session, 'demo.win', 500);

        $this->sendWebhook($event)->assertJsonPath('data.status', 'processed');
        $this->sendWebhook($event)->assertOk()->assertJsonPath('data.status', 'duplicate');
        $this->sendWebhook([...$event, 'amount' => 999])->assertJsonPath('data.status', 'duplicate');

        $this->assertSame(10_500, $this->wallet()->balance_minor);
        $this->assertSame(1, ProviderWebhookEvent::query()->count());
    }

    public function test_out_of_order_delivery_settles_correctly(): void
    {
        $this->sendWebhook($this->event($this->session, 'demo.win', 400, ['round_id' => 'r-7']))->assertJsonPath('data.status', 'processed');
        $this->sendWebhook($this->event($this->session, 'demo.bet', 100, ['round_id' => 'r-7']))->assertJsonPath('data.status', 'processed');

        $this->assertSame(10_300, $this->wallet()->balance_minor);
    }

    public function test_refunds_must_match_an_earlier_bet_in_the_same_round(): void
    {
        $this->sendWebhook($this->event($this->session, 'demo.refund', 100, ['round_id' => 'r-1']))
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.reason', 'refund_without_bet');

        $this->sendWebhook($this->event($this->session, 'demo.bet', 100, ['round_id' => 'r-2']));
        $this->sendWebhook($this->event($this->session, 'demo.refund', 150, ['round_id' => 'r-2']))
            ->assertJsonPath('data.reason', 'refund_exceeds_bet');
        $this->sendWebhook($this->event($this->session, 'demo.refund', 100, ['round_id' => 'r-2']))
            ->assertJsonPath('data.status', 'processed');

        $this->assertSame(10_000, $this->wallet()->balance_minor);
    }

    public function test_bets_on_closed_sessions_are_rejected_but_late_wins_settle(): void
    {
        $this->sendWebhook($this->event($this->session, 'demo.bet', 100, ['round_id' => 'late']));
        $this->travel(31)->minutes();

        $this->sendWebhook($this->event($this->session, 'demo.bet', 100))
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.reason', 'session_closed');

        $this->sendWebhook($this->event($this->session, 'demo.win', 300, ['round_id' => 'late']))
            ->assertJsonPath('data.status', 'processed');

        $this->assertSame(10_200, $this->wallet()->balance_minor);
    }

    public function test_events_for_unknown_sessions_are_rejected(): void
    {
        $this->sendWebhook([...$this->event($this->session, 'demo.win', 100), 'session_reference' => 'gs_does_not_exist'])
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.reason', 'unknown_session');

        $this->assertSame(10_000, $this->wallet()->balance_minor);
    }

    public function test_insufficient_credits_reject_the_bet_without_changing_the_balance(): void
    {
        $this->sendWebhook($this->event($this->session, 'demo.bet', 10_001))
            ->assertJsonPath('data.status', 'rejected');

        $this->assertSame(10_000, $this->wallet()->balance_minor);
    }

    public function test_real_money_and_unknown_event_types_are_refused(): void
    {
        foreach (['bet', 'win', 'cash.deposit', 'withdrawal.request', 'real.bet'] as $type) {
            $this->sendWebhook($this->event($this->session, $type, 100))
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'INVALID_PAYLOAD');
        }

        $this->assertSame(0, ProviderWebhookEvent::query()->count());
    }

    public function test_malformed_and_oversized_payloads_are_refused(): void
    {
        $this->sendWebhook('{not json')->assertStatus(422);
        $this->sendWebhook($this->event($this->session, 'demo.win', -5))->assertStatus(422);
        $this->sendWebhook($this->event($this->session, 'demo.win', '100'))->assertStatus(422);
        $this->sendWebhook(str_repeat('a', 70_000))->assertStatus(413)->assertJsonPath('error.code', 'PAYLOAD_TOO_LARGE');

        $this->assertSame(10_000, $this->wallet()->balance_minor);
    }

    public function test_client_supplied_player_wallet_and_balance_fields_are_ignored(): void
    {
        $victim = $this->playerUser();
        $victimWallet = $victim->demoWallets()->sole();

        $this->sendWebhook([
            ...$this->event($this->session, 'demo.win', 100),
            'player_id' => $victim->id,
            'wallet_id' => $victimWallet->id,
            'balance' => 999_999,
        ])->assertJsonPath('data.status', 'processed');

        $this->assertSame(10_100, $this->wallet()->balance_minor);
        $this->assertSame(10_000, $victimWallet->refresh()->balance_minor);
    }

    public function test_unknown_providers_are_not_found(): void
    {
        $this->postJson('/api/v1/webhooks/providers/not-a-provider', [])->assertNotFound();
    }

    public function test_receipts_store_a_payload_hash_not_the_payload(): void
    {
        $payload = $this->event($this->session, 'demo.win', 100);
        $this->sendWebhook($payload);

        $receipt = ProviderWebhookEvent::query()->sole();
        $this->assertSame(hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)), $receipt->payload_hash);
        $this->assertFalse(Schema::hasColumn('provider_webhook_events', 'payload'));
    }

    private function wallet(): DemoWallet
    {
        return $this->player->demoWallets()->sole();
    }
}
