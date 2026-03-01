<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardLedger extends Model
{
    protected $table = 'rewards_ledger';

    public const TYPE_REFERRAL_BONUS = 'referral_bonus';
    public const TYPE_REVENUE_SHARE = 'revenue_share';
    public const TYPE_DISCOUNT_CREDIT = 'discount_credit';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_REVERSAL = 'reversal';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
