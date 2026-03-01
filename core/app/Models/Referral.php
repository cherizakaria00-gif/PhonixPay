<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_REVOKED = 'revoked';

    protected $guarded = ['id'];

    protected $casts = [
        'registered_at' => 'datetime',
        'qualified_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function code()
    {
        return $this->belongsTo(ReferralCode::class, 'referral_code_id');
    }

    public function firstSuccessfulDeposit()
    {
        return $this->belongsTo(Deposit::class, 'first_successful_deposit_id');
    }
}
