<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisputeLog extends Model
{
    use HasFactory;

    protected $casts = [
        'context' => 'array',
    ];

    public function dispute()
    {
        return $this->belongsTo(Dispute::class);
    }
}
