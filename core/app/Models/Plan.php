<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'support_channels' => 'array',
        'notification_channels' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'tx_limit_monthly' => 'integer',
        'price_monthly_cents' => 'integer',
        'fee_percent' => 'float',
        'fee_fixed' => 'float',
        'payout_delay_days' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function changeRequestsFrom()
    {
        return $this->hasMany(PlanChangeRequest::class, 'from_plan_id');
    }

    public function changeRequestsTo()
    {
        return $this->hasMany(PlanChangeRequest::class, 'to_plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function starter(): ?self
    {
        return static::where('slug', 'starter')->first();
    }

    public function isUnlimited(): bool
    {
        return $this->tx_limit_monthly === null;
    }
}
