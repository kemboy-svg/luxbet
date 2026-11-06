<?php

namespace App\Domain\DemoWallet\Models;

use App\Domain\DemoWallet\Enums\DemoWalletStatus;
use App\Models\User;
use App\Policies\DemoWalletPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A fictional demo-credit balance. Never a financial account: there is no
 * deposit, withdrawal, transfer, purchase or conversion operation.
 *
 * The balance changes only through App\Domain\DemoWallet\Actions\RecordDemoTransaction,
 * so nothing here is mass assignable.
 *
 * @property string $id
 * @property string $user_id
 * @property string $currency_code
 * @property int $balance_minor
 * @property DemoWalletStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Currency $currency
 */
#[UsePolicy(DemoWalletPolicy::class)]
class DemoWallet extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'balance_minor' => 0,
        'status' => 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance_minor' => 'integer',
            'status' => DemoWalletStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * @return HasMany<DemoWalletTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(DemoWalletTransaction::class, 'wallet_id');
    }

    public function isActive(): bool
    {
        return $this->status === DemoWalletStatus::Active;
    }
}
