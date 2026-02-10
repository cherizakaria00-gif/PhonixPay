<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WithdrawSetting extends Model
{
    use HasFactory;

    protected $casts = [
        'user_data' => 'object'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawMethod()
    {
        return $this->belongsTo(WithdrawMethod::class);
    }

    public function nextWithdrawDate() {
          
        $date = Carbon::now();
        $method = $this->withdrawMethod;

        if($method->schedule_type == 'daily'){
            $date = $date->addDay();
        } 
        elseif($method->schedule_type == 'weekly'){
            $date = $date->addWeek();
        } 
        elseif($method->schedule_type == 'monthly'){
            $firstDayOfMonth = Carbon::now()->startOfMonth();
            $lastDayOfMonth = Carbon::now()->lastOfMonth();
            $middleDayOfMonth = $firstDayOfMonth->copy()->addDays($firstDayOfMonth->diffInDays($lastDayOfMonth) / 2);

            if($method->schedule == 'first_day'){
                $date = $firstDayOfMonth;
                if(Carbon::now() > $firstDayOfMonth){
                    $date = $firstDayOfMonth->addMonth();
                } 
            }
            elseif($method->schedule == 'fifteenth_day'){
                $date = $middleDayOfMonth;
                if(Carbon::now() > $middleDayOfMonth){
                    $date = $middleDayOfMonth->addMonth();
                }
            }
            elseif($method->schedule == 'last_day'){
                $date = $lastDayOfMonth;
            }

        }   

        return Carbon::parse($date)->toDateString();
    }

}
 