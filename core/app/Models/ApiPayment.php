<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiPayment extends Model{

    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'customer' => 'object',
        'shipping_info' => 'object',
        'billing_info' => 'object',
        'gateway_methods' => 'array',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class, 'deposit_id');
    }

    public function scopeLive($query)
    {
        return $query->where('type', 'live');
    }
}
