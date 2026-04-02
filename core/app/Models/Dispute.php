<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_PROVIDER_NOTIFIED = 'provider_notified';
    public const STATUS_PENDING_MERCHANT_ACTION = 'pending_merchant_action';
    public const STATUS_PENDING_RESOLUTION = 'pending_resolution';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    public const ACTIVE_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_PROVIDER_NOTIFIED,
        self::STATUS_PENDING_MERCHANT_ACTION,
        self::STATUS_PENDING_RESOLUTION,
    ];

    public const TERMINAL_STATUSES = [
        self::STATUS_RESOLVED,
        self::STATUS_REFUNDED,
        self::STATUS_EXPIRED,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'dispute_fee' => 'decimal:8',
        'refunded_amount' => 'decimal:8',
        'provider_email_sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'resolution_deadline' => 'datetime',
        'resolved_at' => 'datetime',
        'refunded_at' => 'datetime',
        'expired_at' => 'datetime',
        'closed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'dispute_fee_charged_at' => 'datetime',
        'transaction_amount_debited_at' => 'datetime',
        'meta' => 'array',
    ];

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }

    public function logs()
    {
        return $this->hasMany(DisputeLog::class)->latest('id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeTerminal($query)
    {
        return $query->whereIn('status', self::TERMINAL_STATUSES);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function canChargeFee(): bool
    {
        return $this->dispute_fee_charged_at === null && $this->status !== self::STATUS_CANCELLED;
    }
}
