<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PluginLicense extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_REVOKED = 'revoked';

    protected $guarded = ['id'];

    protected $casts = [
        'last_validated_at' => 'datetime',
        'expires_at' => 'datetime',
        'domain_change_requested_at' => 'datetime',
        'revoked_at' => 'datetime',
        'activations_count' => 'integer',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(PluginLicenseValidation::class, 'plugin_license_id')->latest('id');
    }

    public function latestValidation(): HasOne
    {
        return $this->hasOne(PluginLicenseValidation::class, 'plugin_license_id')->latestOfMany('id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            if ($this->status === self::STATUS_ACTIVE) {
                return '<span class="badge badge--success">' . trans('Active') . '</span>';
            }

            if ($this->status === self::STATUS_INACTIVE) {
                return '<span class="badge badge--warning">' . trans('Inactive') . '</span>';
            }

            return '<span class="badge badge--danger">' . trans('Revoked') . '</span>';
        });
    }
}
