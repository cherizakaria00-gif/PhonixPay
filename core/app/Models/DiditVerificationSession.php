<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiditVerificationSession extends Model
{
    use HasFactory;

    protected $casts = [
        'decision' => 'array',
        'opened_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_webhook_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
