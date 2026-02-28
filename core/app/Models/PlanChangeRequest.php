<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanChangeRequest extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromPlan()
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    public function toPlan()
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
