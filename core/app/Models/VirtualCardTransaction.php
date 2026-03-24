<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualCardTransaction extends Model
{
    protected $casts = [
        'meta' => 'array',
        'amount' => 'decimal:8',
        'balance_after' => 'decimal:8',
        'transacted_at' => 'datetime',
    ];

    public function card()
    {
        return $this->belongsTo(VirtualCard::class, 'virtual_card_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
