<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiIntegration extends Model
{
    use HasFactory;

    public const OPTION_API_KEYS = 'api_keys';
    public const OPTION_PAYMENT_LINK = 'payment_link';
    public const OPTION_PLUGIN_SDK = 'plugin_sdk';

    public const STATUS_NOT_CONFIGURED = 'not_configured';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONNECTED = 'connected';
    public const STATUS_NEEDS_ATTENTION = 'needs_attention';

    protected $guarded = ['id'];

    protected $casts = [
        'option_payload' => 'array',
        'setup_completed_at' => 'datetime',
        'last_configured_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class, 'payment_link_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AiIntegrationEvent::class, 'ai_integration_id')->latest('id');
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            return match ($this->status) {
                self::STATUS_CONNECTED => '<span class="badge badge--success">' . trans('Connected') . '</span>',
                self::STATUS_DRAFT => '<span class="badge badge--warning">' . trans('Draft') . '</span>',
                self::STATUS_NEEDS_ATTENTION => '<span class="badge badge--danger">' . trans('Needs Attention') . '</span>',
                default => '<span class="badge badge--dark">' . trans('Not Configured') . '</span>',
            };
        });
    }
}
