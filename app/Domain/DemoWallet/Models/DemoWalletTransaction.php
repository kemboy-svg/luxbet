<?php

namespace App\Domain\DemoWallet\Models;

use App\Domain\DemoWallet\Enums\DemoTransactionDirection;
use App\Domain\DemoWallet\Enums\DemoTransactionSource;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One row in the append-only demo-credit ledger. Not a cash ledger and must
 * never be reused as one (ADR-002).
 *
 * @property string $id
 * @property string $wallet_id
 * @property string $reference
 * @property DemoTransactionType $type
 * @property DemoTransactionDirection $direction
 * @property int $amount_minor
 * @property int $balance_before_minor
 * @property int $balance_after_minor
 * @property DemoTransactionSource $source
 * @property string|null $external_reference
 * @property string|null $idempotency_key
 * @property string $description
 * @property string|null $created_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read DemoWallet $wallet
 */
class DemoWalletTransaction extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DemoTransactionType::class,
            'direction' => DemoTransactionDirection::class,
            'source' => DemoTransactionSource::class,
            'amount_minor' => 'integer',
            'balance_before_minor' => 'integer',
            'balance_after_minor' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('The demo ledger is append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('The demo ledger is append-only.');
        });
    }

    /**
     * @return BelongsTo<DemoWallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(DemoWallet::class, 'wallet_id');
    }

    /**
     * The signed change in credits: positive for credits, negative for debits.
     */
    public function signedAmount(): int
    {
        return $this->direction === DemoTransactionDirection::Credit ? $this->amount_minor : -$this->amount_minor;
    }
}
