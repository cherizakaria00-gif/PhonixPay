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
            // If we already have a scheduled date, advance from that to avoid drift.
            if ($this->next_withdraw_date) {
                $date = Carbon::parse($this->next_withdraw_date)->startOfDay()->addDay();
            } else {
                $date = $date->addDay();
            }
        } 
        elseif($method->schedule_type == 'weekly'){
            $day = trim((string) ($method->schedule ?? ''));
            $map = [
                'Sunday' => Carbon::SUNDAY,
                'Monday' => Carbon::MONDAY,
                'Tuesday' => Carbon::TUESDAY,
                'Wednesday' => Carbon::WEDNESDAY,
                'Thursday' => Carbon::THURSDAY,
                'Friday' => Carbon::FRIDAY,
                'Saturday' => Carbon::SATURDAY,
            ];

            // Stable weekly scheduling:
            // - If a next date already exists, always add 7 days (keeps Wednesday fixed).
            // - Otherwise, pick the next occurrence of the selected day (never "today").
            if ($this->next_withdraw_date) {
                $date = Carbon::parse($this->next_withdraw_date)->startOfDay()->addDays(7);
            } elseif (isset($map[$day])) {
                $date = Carbon::now()->next($map[$day]);
            } else {
                $date = $date->addWeek();
            }
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
 
