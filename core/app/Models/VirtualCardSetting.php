<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualCardSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'endpoints' => 'array',
    ];

    public static function config(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'enabled' => false,
                'provider' => 'strowallet',
                'max_cards_per_merchant' => 3,
                'default_currency' => 'USD',
            ]
        );
    }
}
