<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualCard extends Model
{
    protected $casts = [
        'meta' => 'array',
        'balance' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(VirtualCardTransaction::class)->orderByDesc('transacted_at')->orderByDesc('id');
    }
}
