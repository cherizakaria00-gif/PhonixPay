<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Constants\Status;
use App\Models\AdminNotification;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PayoutController extends ApiMobileController
{
    public function requestPayout(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
        ]);

        $user = $request->user();
        $withdrawSetting = $user->withdrawSetting;

        if (!$withdrawSetting || !$withdrawSetting->withdrawMethod || (int) $withdrawSetting->withdrawMethod->status !== Status::ENABLE) {
            return $this->ok([
                'success' => false,
                'message' => 'Please setup an active payout method first.',
            ], 422);
        }

        if (Withdrawal::query()->where('user_id', $user->id)->pending()->exists()) {
            return $this->ok([
                'success' => false,
                'message' => 'A payout is already pending approval.',
            ], 422);
        }

        $nextAllowedPayoutAt = $this->resolveNextAllowedPayoutAt($user, $withdrawSetting);
        if ($nextAllowedPayoutAt && now()->lt($nextAllowedPayoutAt)) {
            return $this->ok([
                'success' => false,
                'message' => 'Next payout request will be available on ' . $nextAllowedPayoutAt->format('M d, Y') . '.',
            ], 422);
        }

        $method = $withdrawSetting->withdrawMethod;
        $amount = (float) $request->amount;

        if ($amount < (float) $method->min_limit) {
            return $this->ok([
                'success' => false,
                'message' => 'Requested amount is smaller than minimum amount.',
            ], 422);
        }

        $availableBalance = (float) $user->balance * 0.7;
        if ($amount > $availableBalance) {
            return $this->ok([
                'success' => false,
                'message' => 'Insufficient payout balance.',
            ], 422);
        }

        if ($amount > (float) $method->max_limit) {
            return $this->ok([
                'success' => false,
                'message' => 'Requested amount is larger than maximum amount.',
            ], 422);
        }

        $charge = (float) $method->fixed_charge + ($amount * (float) $method->percent_charge / 100);
        $afterCharge = $amount - $charge;
        $finalAmount = $afterCharge * (float) $method->rate;

        $withdraw = new Withdrawal();
        $withdraw->method_id = $method->id;
        $withdraw->user_id = $user->id;
        $withdraw->amount = $amount;
        $withdraw->currency = $method->currency;
        $withdraw->rate = (float) $method->rate;
        $withdraw->charge = $charge;
        $withdraw->final_amount = $finalAmount;
        $withdraw->after_charge = $afterCharge;
        $withdraw->trx = getTrx();
        $withdraw->status = Status::PAYMENT_PENDING;
        $withdraw->withdraw_information = $withdrawSetting->user_data;
        if (Schema::hasColumn('withdrawals', 'payout_date')) {
            $withdraw->payout_date = $nextAllowedPayoutAt
                ? $nextAllowedPayoutAt->toDateString()
                : $withdrawSetting->next_withdraw_date;
        }
        $withdraw->save();

        $user->balance = (float) $user->balance - $amount;
        $user->save();

        $withdrawSetting->amount = $amount;
        if (method_exists($withdrawSetting, 'nextWithdrawDate')) {
            $withdrawSetting->next_withdraw_date = $withdrawSetting->nextWithdrawDate();
        }
        $withdrawSetting->save();

        if (Schema::hasColumn('users', 'manual_next_payout_at') && $user->manual_next_payout_at) {
            $user->manual_next_payout_at = null;
            $user->save();
        }

        $transaction = new Transaction();
        $transaction->user_id = $withdraw->user_id;
        $transaction->amount = $withdraw->amount;
        $transaction->post_balance = $user->balance;
        $transaction->charge = $withdraw->charge;
        $transaction->trx_type = '-';
        $transaction->details = showAmount($withdraw->final_amount, currencyFormat: false) . ' ' . $withdraw->currency . ' Withdraw Via ' . $withdraw->method->name;
        $transaction->trx = $withdraw->trx;
        $transaction->remark = 'withdraw';
        $transaction->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'New withdraw request from ' . $user->username;
        $adminNotification->click_url = urlPath('admin.withdraw.data.details', $withdraw->id);
        $adminNotification->save();

        return $this->ok([
            'success' => true,
            'message' => 'Your payout request has been received. Please wait for confirmation.',
        ]);
    }

    private function maxDate(?Carbon $first, ?Carbon $second): ?Carbon
    {
        if (!$first) {
            return $second;
        }
        if (!$second) {
            return $first;
        }

        return $first->greaterThan($second) ? $first : $second;
    }

    private function resolveNextAllowedPayoutAt($user, $withdrawSetting): ?Carbon
    {
        if (Schema::hasColumn('users', 'manual_next_payout_at') && $user->manual_next_payout_at) {
            return Carbon::parse($user->manual_next_payout_at)->startOfDay();
        }

        $nextPlanPayoutAt = app(\App\Services\PlanService::class)->nextPayoutRequestAvailableAt($user);
        $nextMethodPayoutAt = $withdrawSetting->next_withdraw_date ? Carbon::parse($withdrawSetting->next_withdraw_date)->startOfDay() : null;

        return $this->maxDate($nextPlanPayoutAt, $nextMethodPayoutAt);
    }
}
