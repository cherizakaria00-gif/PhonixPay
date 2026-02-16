<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class StripeAccount extends Model
{
    protected $fillable = [
        'name',
        'publishable_key',
        'secret_key',
        'min_amount',
        'max_amount',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_amount' => 'decimal:8',
        'max_amount' => 'decimal:8',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            if ($this->is_active) {
                return '<span class="badge badge--success">' . trans('Enabled') . '</span>';
            }
            return '<span class="badge badge--warning">' . trans('Disabled') . '</span>';
        });
    }
}
