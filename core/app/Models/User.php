<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\UserNotify;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, UserNotify;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token','ver_code','balance','kyc_data'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'kyc_data' => 'object',
        'ver_code_send_at' => 'datetime',
        'plan_started_at' => 'datetime',
        'plan_renews_at' => 'datetime',
        'discount_active_until' => 'datetime',
        'monthly_tx_count_reset_at' => 'datetime',
        'plan_custom_overrides' => 'array',
        'monthly_tx_count' => 'integer',
        'plan_id' => 'integer',
        'discount_percent' => 'integer',
        'priority_support_enabled' => 'boolean',
    ];


    public function loginLogs()
    {
        return $this->hasMany(UserLogin::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class)->orderBy('id','desc');
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class)->where('status','!=',Status::PAYMENT_INITIATE);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class)->where('status','!=',Status::PAYMENT_INITIATE);
    }

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function fullname(): Attribute
    {
        return new Attribute(
            get: fn () => $this->firstname . ' ' . $this->lastname,
        );
    }

    public function mobileNumber(): Attribute
    {
        return new Attribute(
            get: fn () => $this->dial_code . $this->mobile,
        );
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('status', Status::USER_ACTIVE)->where('ev',Status::VERIFIED)->where('sv',Status::VERIFIED);
    }

    public function scopeBanned($query)
    {
        return $query->where('status', Status::USER_BAN);
    }

    public function scopeEmailUnverified($query)
    {
        return $query->where('ev', Status::UNVERIFIED);
    }

    public function scopeMobileUnverified($query)
    {
        return $query->where('sv', Status::UNVERIFIED);
    }

    public function scopeKycUnverified($query)
    {
        return $query->where('kv', Status::KYC_UNVERIFIED);
    }

    public function scopeKycPending($query)
    {
        return $query->where('kv', Status::KYC_PENDING);
    }

    public function scopeEmailVerified($query)
    {
        return $query->where('ev', Status::VERIFIED);
    }

    public function scopeMobileVerified($query)
    {
        return $query->where('sv', Status::VERIFIED);
    }

    public function scopeWithBalance($query)
    {
        return $query->where('balance','>', 0);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function kycBadge(): Attribute
    {
        return new Attribute(function(){
            $html = '';
            if($this->kv == Status::KYC_VERIFIED){
                $html = '<span class="badge badge--success">'.trans('Verified').'</span>';
            }elseif($this->kv == Status::KYC_PENDING){
                $html = '<span class="badge badge--warning">'.trans('Pending').'</span>';
            }else{
                $html = '<span class="badge badge--danger">'.trans('Unverified').'</span>';
            }
            return @$html;
        });
    }

    public function withdrawSetting()
    {
        return $this->belongsTo(WithdrawSetting::class, 'id', 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function planChangeRequests()
    {
        return $this->hasMany(PlanChangeRequest::class);
    }

    public function referralCode()
    {
        return $this->hasOne(ReferralCode::class);
    }

    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_user_id');
    }

    public function referralRecord()
    {
        return $this->hasOne(Referral::class, 'referred_user_id');
    }

    public function rewardStatus()
    {
        return $this->hasOne(UserRewardStatus::class);
    }

    public function rewardsWallet()
    {
        return $this->hasOne(RewardsWallet::class);
    }

    public function rewardsLedger()
    {
        return $this->hasMany(RewardLedger::class)->orderByDesc('id');
    }
}
