<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BictorysWebhookEvent extends Model
{
    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    protected $fillable = [
        'event_uid',
        'provider',
        'event_id',
        'gateway_alias',
        'charge_id',
        'payment_reference',
        'deposit_id',
        'attempts',
        'status',
        'payload_hash',
        'payload',
        'last_error',
        'processed_at',
    ];
}
