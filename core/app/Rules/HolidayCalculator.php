<?php

namespace App\Lib;

use App\Models\Holiday;
use Carbon\Carbon;

class HolidayCalculator{
    
    public static function nextWorkingDay($withdrawMethod)
    {
        $setting = gs();
        $nextPossible = $withdrawMethod->nextWithdrawDate();
        $now = Carbon::parse($nextPossible);

        while (0==0) {
            if(!self::isHoliDay($nextPossible,$setting)){
                $next = $nextPossible;
                break;
            }

            $now = $now->addDay();
            $nextPossible = $now->toDateString();
        }

        return $next;
    }

    public static function isHoliDay($date,$setting){

        $isHoliday = true;
        $dayName = strtolower(date('l',strtotime($date)));
        $holiday = Holiday::where('day_off',date('Y-m-d',strtotime($date)))->count();
        $offDay = (array)$setting->off_days;

        if(!array_key_exists(ucfirst($dayName), $offDay)){
            if($holiday == 0){
                $isHoliday = false;
            }
        }

        return $isHoliday;
    }

}