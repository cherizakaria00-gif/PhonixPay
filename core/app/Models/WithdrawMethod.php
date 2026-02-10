<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;
use App\Constants\Status;

class WithdrawMethod extends Model
{
    use GlobalStatus;

    protected $casts = [
        'user_data' => 'object',
    ];

    protected $appends = ['showSchedule'];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function withdrawSetting()
    {
        return $this->hasMany(WithdrawSetting::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ENABLE);
    }

    public function scopeDaily($query)
    {
        return $query->where('schedule_type', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('schedule_type', 'weekly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('schedule_type', 'monthly');
    }

    public function getShowScheduleAttribute() 
    {   
        $schedule = null;
        if($this->schedule_type == 'weekly'){
            $schedule = $this->schedule;
        } 
        elseif($this->schedule_type == 'monthly'){
            $schedule = monthlySchedule()[$this->schedule];
        }   

        return $schedule;
    }
}
