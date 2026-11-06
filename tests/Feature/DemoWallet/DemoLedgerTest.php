<?php

namespace Tests\Feature\DemoWallet;

use App\Domain\DemoWallet\Actions\EnsureDemoWallet;
use App\Domain\DemoWallet\Actions\RecordDemoTransaction;
use App\Domain\DemoWallet\Data\DemoTransactionRequest;
use App\Domain\DemoWallet\Data\DemoTransactionResult;
use App\Domain\DemoWallet\Enums\DemoTransactionDirection;
use App\Domain\DemoWallet\Enums\DemoTransactionSource;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Enums\DemoWalletStatus;
use App\Domain\DemoWallet\Exceptions\DemoWalletException;
use App\Domain\DemoWallet\Models\DemoWallet;
use App\Domain\DemoWallet\Models\DemoWalletTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class DemoLedgerTest extends TestCase
{
    use RefreshDatabase;

    private DemoWallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wallet = $this->playerUser()->demoWallets()->sole();
    }

    public function test_a_new_player_gets_one_wallet_in_demo_credits_with_the_starting_allowance(): void
    {
        $this->assertSame('XXX', $this->wallet->currency_code);
        $this->assertSame(10_000, $this->wallet->balance_minor);
        $this->assertSame(DemoWalletStatus::Active, $this->wallet->status);

        $grant = $this->wallet->transactions()->sole();
        $this->assertSame(DemoTransactionType::Credit, $grant->type);
        $this->assertSame(0, $grant->balance_before_minor);
        $this->assertSame(10_000, $grant->balance_after_minor);
    }

    public function test_ensuring_the_wallet_again_never_grants_twice(): void
    {
        $user = $this->wallet->user;

        app(EnsureDemoWallet::class)($user);
        app(EnsureDemoWallet::class)($user);

        $this->assertSame(1, $user->demoWallets()->count());
        $this->assertSame(10_000, $this->wallet->refresh()->balance_minor);
        $this->assertSame(1, $this->wallet->transactions()->count());
    }

    public function test_bets_and_wins_change_the_balance_and_each_writes_one_ledger_row(): void
    {
        $bet = $this->record(DemoTransactionType::Bet, 300)->transaction;
        $win = $this->record(DemoTransactionType::Win, 1_000)->transaction;

        $this->assertSame([10_000, 9_700], [$bet->balance_before_minor, $bet->balance_after_minor]);
        $this->assertSame([9_700, 10_700], [$win->balance_before_minor, $win->balance_after_minor]);
        $this->assertSame(10_700, $this->wallet->refresh()->balance_minor);
        $this->assertSame(
            $this->wallet->balance_minor,
            (int) $this->wallet->transactions()->get()->sum(fn (DemoWalletTransaction $tx): int => $tx->signedAmount()),
        );
    }

    public function test_balance_changes_lock_the_wallet_row(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->record(DemoTransactionType::Bet, 100);

        $this->assertNotEmpty(array_filter($queries, fn (string $sql): bool => str_contains(strtolower($sql), 'for update')));
    }

    public function test_repeating_an_idempotency_key_applies_the_change_only_once(): void
    {
        $first = $this->record(DemoTransactionType::Win, 500, key: 'provider-event-1');
        $second = $this->record(DemoTransactionType::Win, 500, key: 'provider-event-1');

        $this->assertFalse($first->replayed);
        $this->assertTrue($second->replayed);
        $this->assertSame($first->transaction->id, $second->transaction->id);
        $this->assertSame(10_500, $this->wallet->refresh()->balance_minor);
        $this->assertSame(2, $this->wallet->transactions()->count());
    }

    public function test_reusing_an_idempotency_key_for_a_different_request_is_refused(): void
    {
        $this->record(DemoTransactionType::Win, 500, key: 'provider-event-2');

        $this->expectException(DemoWalletException::class);

        try {
            $this->record(DemoTransactionType::Win, 900, key: 'provider-event-2');
        } finally {
            $this->assertSame(10_500, $this->wallet->refresh()->balance_minor);
        }
    }

    public function test_overdrafts_are_refused_without_any_change(): void
    {
        try {
            $this->record(DemoTransactionType::Bet, 10_001);
            $this->fail('Overdraft should be refused.');
        } catch (DemoWalletException) {
            // expected
        }

        $this->assertSame(10_000, $this->wallet->refresh()->balance_minor);
        $this->assertSame(1, $this->wallet->transactions()->count());
    }

    public function test_zero_negative_and_oversized_amounts_are_refused(): void
    {
        foreach ([0, -5, (int) config('luxbet.demo_wallet.max_transaction') + 1] as $amount) {
            try {
                $this->record(DemoTransactionType::Win, $amount);
                $this->fail("Amount {$amount} should be refused.");
            } catch (DemoWalletException) {
                // expected
            }
        }

        $this->assertSame(10_000, $this->wallet->refresh()->balance_minor);
    }

    public function test_types_cannot_move_credits_the_wrong_way(): void
    {
        $this->expectException(DemoWalletException::class);

        $this->record(DemoTransactionType::Bet, 100, direction: DemoTransactionDirection::Credit);
    }

    public function test_adjustments_need_a_direction(): void
    {
        $this->expectException(DemoWalletException::class);

        $this->record(DemoTransactionType::Adjustment, 100);
    }

    public function test_locked_wallets_refuse_changes(): void
    {
        DB::table('demo_wallets')->where('id', $this->wallet->id)->update(['status' => 'locked']);

        $this->expectException(DemoWalletException::class);

        $this->record(DemoTransactionType::Win, 100);
    }

    public function test_the_database_refuses_a_negative_balance(): void
    {
        $this->expectException(QueryException::class);

        DB::table('demo_wallets')->where('id', $this->wallet->id)->update(['balance_minor' => -1]);
    }

    public function test_the_database_refuses_inconsistent_ledger_rows(): void
    {
        $this->expectException(QueryException::class);

        DB::table('demo_wallet_transactions')->insert([
            'id' => (string) Str::uuid(),
            'wallet_id' => $this->wallet->id,
            'reference' => 'DTX-BADROW00001',
            'type' => 'demo_win',
            'direction' => 'credit',
            'amount_minor' => 100,
            'balance_before_minor' => 10_000,
            'balance_after_minor' => 99_999,
            'source' => 'system',
            'description' => 'Inconsistent row',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_the_database_refuses_non_positive_amounts(): void
    {
        $this->expectException(QueryException::class);

        DB::table('demo_wallet_transactions')->insert([
            'id' => (string) Str::uuid(),
            'wallet_id' => $this->wallet->id,
            'reference' => 'DTX-BADROW00002',
            'type' => 'demo_win',
            'direction' => 'credit',
            'amount_minor' => 0,
            'balance_before_minor' => 10_000,
            'balance_after_minor' => 10_000,
            'source' => 'system',
            'description' => 'Zero row',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_the_ledger_is_append_only(): void
    {
        $row = $this->wallet->transactions()->sole();

        try {
            $row->forceFill(['description' => 'tampered'])->save();
            $this->fail('Updating a ledger row through the model should throw.');
        } catch (LogicException) {
            // expected
        }

        $this->expectException(QueryException::class);

        DB::table('demo_wallet_transactions')->where('id', $row->id)->update(['amount_minor' => 1]);
    }

    public function test_the_ledger_refuses_deletes_at_the_database(): void
    {
        $this->expectException(QueryException::class);

        DB::table('demo_wallet_transactions')->where('wallet_id', $this->wallet->id)->delete();
    }

    private function record(
        DemoTransactionType $type,
        int $amount,
        ?string $key = null,
        ?DemoTransactionDirection $direction = null,
    ): DemoTransactionResult {
        return app(RecordDemoTransaction::class)(new DemoTransactionRequest(
            walletId: $this->wallet->id,
            type: $type,
            amount: $amount,
            source: DemoTransactionSource::ProviderSandbox,
            description: 'Test '.$type->label(),
            direction: $direction,
            idempotencyKey: $key,
        ));
    }
}
