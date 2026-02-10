<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function pending($userId = null)
    {
        $pageTitle = 'Pending Withdrawals';
        return $this->withdrawalData('pending', false, userId:$userId, pageTitle:$pageTitle);
    }

    public function approved($userId = null)
    {
        $pageTitle = 'Approved Withdrawals';
        return $this->withdrawalData('approved', false, userId:$userId, pageTitle:$pageTitle);
    }

    public function rejected($userId = null)
    {
        $pageTitle = 'Rejected Withdrawals';
        return $this->withdrawalData('rejected', false, userId:$userId, pageTitle:$pageTitle);
    }

    public function all($userId = null)
    {
        $pageTitle = 'All Withdrawals';
        return $this->withdrawalData(null, true, userId:$userId, pageTitle:$pageTitle);
    }

    protected function withdrawalData($scope = null, $summary = false,$userId = null, $pageTitle){
        if ($scope) {
            $withdrawals = Withdrawal::$scope();
        }else{
            $withdrawals = Withdrawal::where('status','!=',Status::PAYMENT_INITIATE);
        }

        if ($userId) {
            $withdrawals = $withdrawals->where('user_id',$userId);
        }

        $withdrawals = $withdrawals->searchable(['trx','user:username'])->dateFilter();

        $request = request();
        if ($request->method) {
            $withdrawals = $withdrawals->where('method_id',$request->method);
        }
        if (!$summary) {
            if($request->export_type){
                return $withdrawals->export();
            }
            $withdrawals = $withdrawals->with(['user','method'])->orderBy('id','desc')->paginate(getPaginate());
            return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals'));
        }else{

            $successful = clone $withdrawals;
            $pending = clone $withdrawals;
            $rejected = clone $withdrawals;

            $successful = $successful->where('status',Status::PAYMENT_SUCCESS)->sum('amount');
            $pending = $pending->where('status',Status::PAYMENT_PENDING)->sum('amount');
            $rejected = $rejected->where('status',Status::PAYMENT_REJECT)->sum('amount');

            if($request->export_type){
                return $withdrawals->export();
            }

            $withdrawals = $withdrawals->with(['user','method'])->orderBy('id','desc')->paginate(getPaginate());
            return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals', 'successful', 'pending', 'rejected'));
        }
    }

    public function details($id)
    {
        $withdrawal = Withdrawal::where('id',$id)->where('status', '!=', Status::PAYMENT_INITIATE)->with(['user','method'])->firstOrFail();
        $pageTitle = $withdrawal->user->username.' Withdraw Requested ' . showAmount($withdrawal->amount);
        $details = $withdrawal->withdraw_information ? json_encode($withdrawal->withdraw_information) : null;

        return view('admin.withdraw.detail', compact('pageTitle', 'withdrawal','details'));
    }

    public function approve(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $withdraw = Withdrawal::where('id',$request->id)->where('status',Status::PAYMENT_PENDING)->with('user')->firstOrFail();
        $withdraw->status = Status::PAYMENT_SUCCESS;
        $withdraw->admin_feedback = $request->details;
        $withdraw->save();

        notify($withdraw->user, 'WITHDRAW_APPROVE', [
            'method_name' => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount' => showAmount($withdraw->final_amount,currencyFormat:false),
            'amount' => showAmount($withdraw->amount,currencyFormat:false),
            'charge' => showAmount($withdraw->charge,currencyFormat:false),
            'rate' => showAmount($withdraw->rate,currencyFormat:false),
            'trx' => $withdraw->trx,
            'admin_details' => $request->details
        ]);

        $notify[] = ['success', 'Withdrawal approved successfully'];
        return to_route('admin.withdraw.data.pending')->withNotify($notify);
    }


    public function reject(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $withdraw = Withdrawal::where('id',$request->id)->where('status',Status::PAYMENT_PENDING)->with('user')->firstOrFail();

        $withdraw->status = Status::PAYMENT_REJECT;
        $withdraw->admin_feedback = $request->details;
        $withdraw->save();

        $user = $withdraw->user;
        $user->balance += $withdraw->amount;
        $user->save();

        $transaction = new Transaction();
        $transaction->user_id = $withdraw->user_id;
        $transaction->amount = $withdraw->amount;
        $transaction->post_balance = $user->balance;
        $transaction->charge = 0;
        $transaction->trx_type = '+';
        $transaction->remark = 'withdraw_reject';
        $transaction->details = 'Refunded for withdrawal rejection';
        $transaction->trx = $withdraw->trx;
        $transaction->save();

        notify($user, 'WITHDRAW_REJECT', [
            'method_name' => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount' => showAmount($withdraw->final_amount,currencyFormat:false),
            'amount' => showAmount($withdraw->amount,currencyFormat:false),
            'charge' => showAmount($withdraw->charge,currencyFormat:false),
            'rate' => showAmount($withdraw->rate,currencyFormat:false),
            'trx' => $withdraw->trx,
            'post_balance' => showAmount($user->balance,currencyFormat:false),
            'admin_details' => $request->details
        ]);

        $notify[] = ['success', 'Withdrawal rejected successfully'];
        return to_route('admin.withdraw.data.pending')->withNotify($notify);
    }

}
