<?php

namespace Tests\Unit\DemoWallet;

use App\Domain\DemoWallet\Data\DemoTransactionRequest;
use App\Domain\DemoWallet\Enums\DemoTransactionDirection;
use App\Domain\DemoWallet\Enums\DemoTransactionSource;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use PHPUnit\Framework\TestCase;

class DemoTransactionRulesTest extends TestCase
{
    public function test_direction_arithmetic(): void
    {
        $this->assertSame(1_500, DemoTransactionDirection::Credit->apply(1_000, 500));
        $this->assertSame(500, DemoTransactionDirection::Debit->apply(1_000, 500));
        $this->assertSame(-1, DemoTransactionDirection::Debit->apply(0, 1));
    }

    public function test_each_type_has_a_fixed_direction_except_adjustments(): void
    {
        $this->assertSame(DemoTransactionDirection::Credit, DemoTransactionType::Credit->fixedDirection());
        $this->assertSame(DemoTransactionDirection::Credit, DemoTransactionType::Win->fixedDirection());
        $this->assertSame(DemoTransactionDirection::Credit, DemoTransactionType::Refund->fixedDirection());
        $this->assertSame(DemoTransactionDirection::Debit, DemoTransactionType::Bet->fixedDirection());
        $this->assertNull(DemoTransactionType::Adjustment->fixedDirection());
    }

    public function test_requests_resolve_their_direction(): void
    {
        $bet = new DemoTransactionRequest('w', DemoTransactionType::Bet, 10, DemoTransactionSource::System, 'x');
        $adjustment = new DemoTransactionRequest('w', DemoTransactionType::Adjustment, 10, DemoTransactionSource::Admin, 'x', DemoTransactionDirection::Debit);
        $undirected = new DemoTransactionRequest('w', DemoTransactionType::Adjustment, 10, DemoTransactionSource::Admin, 'x');

        $this->assertSame(DemoTransactionDirection::Debit, $bet->resolvedDirection());
        $this->assertSame(DemoTransactionDirection::Debit, $adjustment->resolvedDirection());
        $this->assertNull($undirected->resolvedDirection());
    }

    public function test_every_type_is_labelled_as_demo(): void
    {
        foreach (DemoTransactionType::cases() as $type) {
            $this->assertStringStartsWith('demo_', $type->value);
            $this->assertStringStartsWith('Demo ', $type->label());
        }
    }
}
