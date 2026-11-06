<?php

namespace App\Domain\Compliance\Models;

use App\Domain\Compliance\Enums\EnvironmentMode;
use App\Domain\Compliance\Exceptions\RealMoneyLockedException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Singleton record describing the real-money launch approval state.
 *
 * Read-only from the application's point of view: there is no fillable
 * attribute, and saving with real_money_enabled=true or deleting the record
 * throws. The database also enforces real_money_enabled = false.
 *
 * @property int $id
 * @property bool $real_money_enabled
 * @property EnvironmentMode $environment_mode
 * @property array<int, string>|null $approved_jurisdictions
 * @property string|null $approval_reference
 * @property string|null $approval_document_path
 * @property string|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $review_due_at
 * @property string|null $change_reason
 * @property int $version
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class LaunchControl extends Model
{
    public const SINGLETON_ID = 1;

    protected $table = 'launch_control';

    /**
     * Nothing is mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'real_money_enabled' => false,
        'environment_mode' => 'demo',
        'version' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'real_money_enabled' => 'boolean',
            'environment_mode' => EnvironmentMode::class,
            'approved_jurisdictions' => 'array',
            'approved_at' => 'immutable_datetime',
            'review_due_at' => 'immutable_datetime',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (LaunchControl $record): void {
            if ($record->real_money_enabled) {
                throw RealMoneyLockedException::enabling();
            }
        });

        static::deleting(function (): void {
            throw RealMoneyLockedException::deleting();
        });
    }

    public static function current(): ?self
    {
        return static::query()->find(self::SINGLETON_ID);
    }
}
