<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiIntegrationEvent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'context' => 'array',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(AiIntegration::class, 'ai_integration_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}
