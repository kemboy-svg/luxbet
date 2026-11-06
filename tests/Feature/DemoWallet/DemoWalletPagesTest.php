<?php

namespace Tests\Feature\DemoWallet;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Models\DemoWallet;
use App\Domain\Identity\Authorization\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DemoWalletPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_wallet_page_shows_the_balance_and_history(): void
    {
        $this->actingAs($this->playerUser())
            ->get(route('demo-wallet.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DemoWallet')
                ->where('wallet.balance', 10_000)
                ->where('wallet.demo', true)
                ->where('allowance', 10_000)
                ->where('resetAvailable', false)
                ->has('transactions.data', 1));
    }

    public function test_the_page_creates_a_wallet_for_players_who_have_none(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('demo-wallet.show'))->assertOk();

        $this->assertSame(10_000, $user->demoWallets()->sole()->balance_minor);
    }

    public function test_a_refused_reset_returns_an_error_to_the_page(): void
    {
        $this->actingAs($this->playerUser())
            ->post(route('demo-wallet.reset'))
            ->assertSessionHasErrors('reset');
    }

    public function test_a_super_admin_can_adjust_demo_credits_with_a_reason_and_it_is_audited(): void
    {
        $admin = $this->staffUser(Role::SuperAdmin);
        $player = $this->playerUser();

        $this->actingAs($admin)
            ->post($this->adjustUrl($player), [
                'direction' => 'credit',
                'amount' => 1_700,
                'reason' => 'Restored after simulator fault',
                'idempotency_key' => (string) Str::uuid(),
            ])
            ->assertSessionHasNoErrors();

        $wallet = $this->walletOf($player);
        $this->assertSame(11_700, $wallet->balance_minor);

        $tx = $wallet->transactions()->where('type', DemoTransactionType::Adjustment->value)->sole();
        $this->assertSame($admin->id, $tx->created_by);
        $this->assertStringContainsString('no cash value', $tx->description);

        $log = AuditLog::query()->where('action', 'demo_wallet.adjusted')->sole();
        $this->assertSame('Restored after simulator fault', $log->reason);
        $this->assertTrue($log->metadata['demo_only']);
    }

    public function test_repeating_an_adjustment_request_applies_it_once(): void
    {
        $admin = $this->staffUser(Role::SuperAdmin);
        $player = $this->playerUser();
        $payload = [
            'direction' => 'debit',
            'amount' => 500,
            'reason' => 'Duplicate click test',
            'idempotency_key' => (string) Str::uuid(),
        ];

        $this->actingAs($admin)->post($this->adjustUrl($player), $payload);
        $this->actingAs($admin)->post($this->adjustUrl($player), $payload);

        $this->assertSame(9_500, $this->walletOf($player)->balance_minor);
        $this->assertSame(1, AuditLog::query()->where('action', 'demo_wallet.adjusted')->count());
    }

    public function test_adjustments_cannot_overdraw(): void
    {
        $player = $this->playerUser();

        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->post($this->adjustUrl($player), [
                'direction' => 'debit',
                'amount' => 10_001,
                'reason' => 'Too large debit',
                'idempotency_key' => (string) Str::uuid(),
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(10_000, $this->walletOf($player)->balance_minor);
    }

    public function test_adjustments_need_a_reason(): void
    {
        $this->actingAs($this->staffUser(Role::SuperAdmin))
            ->post($this->adjustUrl($this->playerUser()), [
                'direction' => 'credit',
                'amount' => 100,
                'reason' => '',
                'idempotency_key' => (string) Str::uuid(),
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_only_staff_with_demo_wallet_adjust_can_adjust(): void
    {
        $player = $this->playerUser();

        foreach ([Role::SupportAgent, Role::FinanceViewer, Role::ComplianceOfficer, Role::GameManager] as $role) {
            $this->actingAs($this->staffUser($role))
                ->post($this->adjustUrl($player), [
                    'direction' => 'credit',
                    'amount' => 100,
                    'reason' => 'Should be refused',
                    'idempotency_key' => (string) Str::uuid(),
                ])
                ->assertForbidden();
        }

        $this->assertSame(10_000, $this->walletOf($player)->balance_minor);
    }

    public function test_staff_cannot_adjust_their_own_or_a_colleagues_credits(): void
    {
        $admin = $this->staffUser(Role::SuperAdmin);
        $colleague = $this->staffUser(Role::SupportAgent);

        foreach ([$admin, $colleague] as $target) {
            $this->actingAs($admin)
                ->post($this->adjustUrl($target), [
                    'direction' => 'credit',
                    'amount' => 100,
                    'reason' => 'Self or staff adjustment',
                    'idempotency_key' => (string) Str::uuid(),
                ])
                ->assertForbidden();
        }
    }

    private function adjustUrl(User $player): string
    {
        return "/admin/players/{$player->id}/demo-wallet/adjustments";
    }

    private function walletOf(User $user): DemoWallet
    {
        return $user->demoWallets()->sole();
    }
}
