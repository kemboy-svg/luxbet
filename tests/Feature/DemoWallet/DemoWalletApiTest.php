<?php

namespace Tests\Feature\DemoWallet;

use App\Domain\DemoWallet\Actions\RecordDemoTransaction;
use App\Domain\DemoWallet\Data\DemoTransactionRequest;
use App\Domain\DemoWallet\Enums\DemoTransactionSource;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Models\DemoWallet;
use App\Domain\Identity\Authorization\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoWalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_an_unauthenticated_error(): void
    {
        $this->getJson('/api/v1/demo-wallets')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_players_list_their_own_demo_wallet_labelled_as_demo(): void
    {
        $player = $this->playerUser();

        $this->actingAs($player)
            ->getJson('/api/v1/demo-wallets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.balance', 10_000)
            ->assertJsonPath('data.0.unit', 'demo_credits')
            ->assertJsonPath('data.0.demo', true)
            ->assertJsonPath('data.0.cash_value', null);
    }

    public function test_players_read_their_own_transactions_without_internal_fields(): void
    {
        $player = $this->playerUser();
        $wallet = $this->walletOf($player);

        $response = $this->actingAs($player)
            ->getJson("/api/v1/demo-wallets/{$wallet->id}/transactions")
            ->assertOk()
            ->assertJsonPath('data.0.type', 'demo_credit')
            ->assertJsonPath('data.0.amount', 10_000)
            ->assertJsonPath('meta.pagination.total', 1);

        $this->assertArrayNotHasKey('idempotency_key', $response->json('data.0'));
        $this->assertArrayNotHasKey('created_by', $response->json('data.0'));
    }

    public function test_players_cannot_see_another_players_wallet(): void
    {
        $other = $this->walletOf($this->playerUser());

        $this->actingAs($this->playerUser())
            ->getJson("/api/v1/demo-wallets/{$other->id}/transactions")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');

        $this->actingAs($this->playerUser())
            ->postJson("/api/v1/demo-wallets/{$other->id}/reset")
            ->assertNotFound();
    }

    public function test_staff_with_wallet_view_and_two_factor_can_read_a_players_transactions(): void
    {
        $wallet = $this->walletOf($this->playerUser());

        $this->actingAs($this->staffUser(Role::FinanceViewer))
            ->getJson("/api/v1/demo-wallets/{$wallet->id}/transactions")
            ->assertOk();

        $noMfa = User::factory()->staff(Role::FinanceViewer)->create();

        $this->actingAs($noMfa)
            ->getJson("/api/v1/demo-wallets/{$wallet->id}/transactions")
            ->assertNotFound();
    }

    public function test_reset_tops_up_once_a_day_when_below_the_allowance(): void
    {
        $player = $this->playerUser();
        $wallet = $this->walletOf($player);
        $this->spend($wallet, 4_000);

        $this->actingAs($player)
            ->postJson("/api/v1/demo-wallets/{$wallet->id}/reset")
            ->assertOk()
            ->assertJsonPath('data.balance', 10_000);

        $this->spend($wallet, 1_000);

        $this->actingAs($player)
            ->postJson("/api/v1/demo-wallets/{$wallet->id}/reset")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'DEMO_WALLET_REFUSED');

        $this->assertSame(9_000, $wallet->refresh()->balance_minor);
    }

    public function test_reset_is_refused_at_or_above_the_allowance(): void
    {
        $player = $this->playerUser();
        $wallet = $this->walletOf($player);

        $this->actingAs($player)
            ->postJson("/api/v1/demo-wallets/{$wallet->id}/reset")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'DEMO_WALLET_REFUSED');
    }

    public function test_reset_is_available_again_the_next_day(): void
    {
        $player = $this->playerUser();
        $wallet = $this->walletOf($player);
        $this->spend($wallet, 2_000);

        $this->actingAs($player)->postJson("/api/v1/demo-wallets/{$wallet->id}/reset")->assertOk();

        $this->spend($wallet, 2_000);
        $this->travel(1)->days();

        $this->actingAs($player)->postJson("/api/v1/demo-wallets/{$wallet->id}/reset")->assertOk();
        $this->assertSame(10_000, $wallet->refresh()->balance_minor);
    }

    public function test_there_is_no_way_to_withdraw_transfer_or_convert_demo_credits(): void
    {
        $player = $this->playerUser();
        $wallet = $this->walletOf($player);

        foreach (['withdraw', 'transfer', 'convert', 'redeem', 'cashout', 'deposit', 'purchase'] as $action) {
            $this->actingAs($player)
                ->postJson("/api/v1/demo-wallets/{$wallet->id}/{$action}", ['amount' => 100])
                ->assertNotFound();
        }

        $this->assertSame(10_000, $wallet->refresh()->balance_minor);
    }

    private function walletOf(User $user): DemoWallet
    {
        return $user->demoWallets()->sole();
    }

    private function spend(DemoWallet $wallet, int $amount): void
    {
        app(RecordDemoTransaction::class)(new DemoTransactionRequest(
            walletId: $wallet->id,
            type: DemoTransactionType::Bet,
            amount: $amount,
            source: DemoTransactionSource::ProviderSandbox,
            description: 'Test demo bet',
        ));
    }
}
