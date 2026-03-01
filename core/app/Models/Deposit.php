<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\ExportData;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Models\StripeAccount;

class Deposit extends Model
{
    use ExportData;

    protected $casts = [
        'detail' => 'object',
        'payout_eligible_at' => 'datetime',
        'fee_amount' => 'float',
        'net_amount' => 'float',
        'refunded_at' => 'datetime',
        'gross_amount_cents' => 'integer',
    ];

    protected $hidden = ['detail'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function apiPayment()
    {
        return $this->hasOne(ApiPayment::class, 'deposit_id');
    }

    public function totalCharge(): Attribute
    {   
        return new Attribute(function(){
            return ($this->charge + $this->payment_charge);
        });
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class, 'method_code', 'code');
    }

    public function stripeAccount()
    {
        return $this->belongsTo(StripeAccount::class);
    }

    public function paymentLink()
    {
        return $this->belongsTo(PaymentLink::class);
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function methodName(){
        if ($this->method_code < 5000) {
            $methodName = @$this->gatewayCurrency()->name;
        }else{
            $methodName = 'Google Pay';
        }
        return $methodName;
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function(){
            $html = '';
            if($this->status == Status::PAYMENT_PENDING){
                $html = '<span class="badge badge--warning">'.trans('Pending').'</span>';
            }
            elseif($this->status == Status::PAYMENT_SUCCESS && $this->method_code >= 1000 && $this->method_code <= 5000){
                $html = '<span><span class="badge badge--success">'.trans('Approved').'</span><br>'.diffForHumans($this->updated_at).'</span>';
            }
            elseif($this->status == Status::PAYMENT_SUCCESS && ($this->method_code < 1000 || $this->method_code >= 5000)){
                $html = '<span class="badge badge--success">'.trans('Succeed').'</span>';
            }
            elseif($this->status == Status::PAYMENT_REFUNDED){
                $html = '<span class="badge badge--warning">'.trans('Refunded').'</span>';
            }
            elseif($this->status == Status::PAYMENT_REJECT){
                $html = '<span><span class="badge badge--danger">'.trans('Rejected').'</span><br>'.diffForHumans($this->updated_at).'</span>';
            }else{
                $html = '<span class="badge badge--dark">'.trans('Initiated').'</span>';
            }
            return $html;
        });
    }

    // scope
    public function gatewayCurrency()
    {
        return GatewayCurrency::where('method_code', $this->method_code)->where('currency', $this->method_currency)->first();
    }

    public function baseCurrency()
    {
        return @$this->gateway->crypto == Status::ENABLE ? 'USD' : $this->method_currency;
    }

    public function scopePending($query)
    {
        return $query->where('method_code','>=',1000)->where('status', Status::PAYMENT_PENDING);
    }

    public function scopeRejected($query)
    {
        return $query->where('method_code','>=',1000)->where('status', Status::PAYMENT_REJECT);
    }

    public function scopeApproved($query)
    {
        return $query->where('method_code','>=',1000)->where('method_code','<',5000)->where('status', Status::PAYMENT_SUCCESS);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', Status::PAYMENT_SUCCESS);
    }

    public function scopeInitiated($query)
    {
        return $query->where('status', Status::PAYMENT_INITIATE);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', Status::PAYMENT_REFUNDED);
    }
}
