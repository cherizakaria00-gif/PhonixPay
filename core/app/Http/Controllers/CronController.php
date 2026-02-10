<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Lib\CurlRequest;
use App\Lib\HolidayCalculator;
use App\Models\AdminNotification;
use App\Models\CronJob;
use App\Models\CronJobLog;
use App\Models\Holiday;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\WithdrawSetting;
use Carbon\Carbon;

class CronController extends Controller
{
    public function cron()
    {   
        $general            = gs();
        $general->last_cron = now();
        $general->save();

        $crons = CronJob::with('schedule');

        if (request()->alias) {
            $crons->where('alias', request()->alias);
        } else {
            $crons->where('next_run', '<', now())->where('is_running', Status::YES);
        }
        $crons = $crons->get();
        foreach ($crons as $cron) {
            $cronLog              = new CronJobLog();
            $cronLog->cron_job_id = $cron->id;
            $cronLog->start_at    = now();
            if ($cron->is_default) {
                $controller = new $cron->action[0];
                try {
                    $method = $cron->action[1];
                    $controller->$method();
                } catch (\Exception $e) {
                    $cronLog->error = $e->getMessage();
                }
            } else {
                try {
                    CurlRequest::curlContent($cron->url);
                } catch (\Exception $e) {
                    $cronLog->error = $e->getMessage();
                }
            }
            $cron->last_run = now();
            $cron->next_run = now()->addSeconds($cron->schedule->interval);
            $cron->save();

            $cronLog->end_at = $cron->last_run;

            $startTime         = Carbon::parse($cronLog->start_at);
            $endTime           = Carbon::parse($cronLog->end_at);
            $diffInSeconds     = $startTime->diffInSeconds($endTime);
            $cronLog->duration = $diffInSeconds;
            $cronLog->save();
        }
        if (request()->target == 'all') {
            $notify[] = ['success', 'Cron executed successfully'];
            return back()->withNotify($notify);
        }
        if (request()->alias) {
            $notify[] = ['success', keyToTitle(request()->alias) . ' executed successfully'];
            return back()->withNotify($notify);
        }
    }

    private function query($scope){
        return WithdrawSetting::whereDate('next_withdraw_date', '<=', Carbon::today())
            ->whereHas('withdrawMethod', function ($method) use ($scope){
                $method->$scope()->active();
            })
            ->whereHas('user', function ($user){
                $user->active()->kycVerified();
            })
        ->with('user', 'withdrawMethod')->take(30)->get();  
    }

    private function daily(){  
        $withdrawSetting = $this->query('daily');
        $this->withdraw($withdrawSetting);
    }

    private function weekly(){ 
        $withdrawSetting = $this->query('weekly');
        $this->withdraw($withdrawSetting);
    }

    private function monthly(){ 
        $withdrawSetting = $this->query('monthly');
        $this->withdraw($withdrawSetting);
    }

    private function withdraw($withdrawSetting){ 
        foreach($withdrawSetting as $setting){

            $general = gs();
            $day     = Carbon::today()->format('l');
            $offDays = @$general->off_days ?? [];
            $holiday = Holiday::whereDate('day_off', Carbon::today())->first();
    
            if (array_key_exists($day, $offDays) || $holiday) {
                $setting->next_withdraw_date = HolidayCalculator::nextWorkingDay($setting);
                $setting->save();

                echo "Holiday...<br/>";
                return false;
            }
    
            $user = $setting->user;
            $amount = $setting->amount;
            $method = $setting->withdrawMethod;

            if($amount > $user->balance){ 
                notify($user, 'INSUFFICIENT_WITHDRAW_BALANCE', [
                    'current_balance' => showAmount($user->balance, currencyFormat:false),
                    'withdraw_amount' => showAmount($amount, currencyFormat:false),
                    'withdraw_method' => $method->name,
                    'withdraw_schedule' => $method->schedule_type,
                ]);
                continue;    
            }
    
            $charge = $method->fixed_charge + ($amount * $method->percent_charge / 100);
            $afterCharge = $amount - $charge;
            $finalAmount = $afterCharge * $method->rate;
    
            $withdraw = new Withdrawal();
            $withdraw->method_id = $method->id;
            $withdraw->user_id = $user->id;
            $withdraw->amount = $amount;
            $withdraw->currency = $method->currency;
            $withdraw->rate = $method->rate;
            $withdraw->charge = $charge;
            $withdraw->final_amount = $finalAmount;
            $withdraw->after_charge = $afterCharge;
            $withdraw->trx = getTrx();
            $withdraw->status = Status::PAYMENT_PENDING;
            $withdraw->withdraw_information = $setting->user_data;
            $withdraw->save();
    
            $user->balance -= $amount;
            $user->save(); 

            $setting->next_withdraw_date = HolidayCalculator::nextWorkingDay($setting);
            $setting->save();

            $transaction = new Transaction();
            $transaction->user_id = $withdraw->user_id;
            $transaction->amount = $withdraw->amount;
            $transaction->post_balance = $user->balance;
            $transaction->charge = $withdraw->charge;
            $transaction->trx_type = '-';
            $transaction->details = showAmount($withdraw->final_amount, currencyFormat:false) . ' ' . $withdraw->currency . ' Withdraw Via ' . $withdraw->method->name;
            $transaction->trx = $withdraw->trx;
            $transaction->remark = 'withdraw';
            $transaction->save();

            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $user->id;
            $adminNotification->title = 'New withdraw request from '.$user->username;
            $adminNotification->click_url = urlPath('admin.withdraw.details',$withdraw->id);
            $adminNotification->save();

            notify($user, 'WITHDRAW_REQUEST', [
                'method_name' => $withdraw->method->name,
                'method_currency' => $withdraw->currency,
                'method_amount' => showAmount($withdraw->final_amount, currencyFormat:false),
                'amount' => showAmount($withdraw->amount, currencyFormat:false),
                'charge' => showAmount($withdraw->charge, currencyFormat:false),
                'rate' => showAmount($withdraw->rate, currencyFormat:false),
                'trx' => $withdraw->trx,
                'post_balance' => showAmount($user->balance, currencyFormat:false),
            ]);
        }
    }

    public function withdrawRequest(){ 
        $this->daily();
        $this->weekly(); 
        $this->monthly();
    }
}
