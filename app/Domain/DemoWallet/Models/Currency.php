<?php

namespace App\Domain\DemoWallet\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Display and demo configuration only. Listing a currency does not mean
 * payments or gambling in it are authorised.
 *
 * @property string $code
 * @property string $name
 * @property int $minor_unit
 * @property bool $is_enabled
 * @property bool $is_demo_unit
 */
class Currency extends Model
{
    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minor_unit' => 'integer',
            'is_enabled' => 'boolean',
            'is_demo_unit' => 'boolean',
        ];
    }
}
