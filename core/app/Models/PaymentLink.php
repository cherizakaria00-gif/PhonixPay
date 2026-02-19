<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class PaymentLink extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 0;
    const STATUS_PAID = 1;
    const STATUS_EXPIRED = 2;
    const STATUS_DISABLED = 3;

    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markExpiredIfNeeded(): void
    {
        if ($this->status == self::STATUS_ACTIVE && $this->isExpired()) {
            $this->status = self::STATUS_EXPIRED;
            $this->save();
        }
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            if ($this->status == self::STATUS_PAID) {
                return '<span class="badge badge--success">' . trans('Paid') . '</span>';
            }

            if ($this->status == self::STATUS_EXPIRED || $this->isExpired()) {
                return '<span class="badge badge--danger">' . trans('Expired') . '</span>';
            }

            if ($this->status == self::STATUS_DISABLED) {
                return '<span class="badge badge--dark">' . trans('Disabled') . '</span>';
            }

            return '<span class="badge badge--primary">' . trans('Active') . '</span>';
        });
    }
}
