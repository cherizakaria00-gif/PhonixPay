<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyConversionRate extends Model
{
    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'is_active',
        'source',
    ];

    protected $casts = [
        'rate' => 'float',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}

