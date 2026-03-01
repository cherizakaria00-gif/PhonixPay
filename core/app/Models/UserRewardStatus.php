<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRewardStatus extends Model
{
    protected $table = 'user_reward_status';

    protected $guarded = ['id'];

    protected $casts = [
        'level1_achieved_at' => 'datetime',
        'level2_achieved_at' => 'datetime',
        'level3_achieved_at' => 'datetime',
        'discount_active_until' => 'datetime',
        'current_level' => 'integer',
        'qualified_referrals_count' => 'integer',
        'revenue_share_bps' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
