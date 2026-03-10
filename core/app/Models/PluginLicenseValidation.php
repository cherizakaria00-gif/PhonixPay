<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginLicenseValidation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'context' => 'array',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(PluginLicense::class, 'plugin_license_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}
