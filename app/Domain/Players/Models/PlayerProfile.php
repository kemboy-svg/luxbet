<?php

namespace App\Domain\Players\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\PlayerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $username
 * @property string|null $country_code
 * @property string $preferred_locale
 * @property CarbonImmutable|null $date_of_birth
 * @property string|null $avatar_path
 * @property CarbonImmutable|null $age_confirmed_at
 * @property CarbonImmutable|null $terms_accepted_at
 * @property string|null $terms_version
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[UseFactory(PlayerProfileFactory::class)]
class PlayerProfile extends Model
{
    /** @use HasFactory<PlayerProfileFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'country_code',
        'preferred_locale',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'immutable_date',
            'age_confirmed_at' => 'immutable_datetime',
            'terms_accepted_at' => 'immutable_datetime',
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
     * Case-insensitive username lookup.
     *
     * @param  Builder<PlayerProfile>  $query
     * @return Builder<PlayerProfile>
     */
    public function scopeWhereUsername(Builder $query, string $username): Builder
    {
        return $query->whereRaw('lower(username) = ?', [mb_strtolower($username)]);
    }
}
