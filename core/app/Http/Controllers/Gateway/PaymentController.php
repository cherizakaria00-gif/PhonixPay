<?php

namespace App\Http\Controllers\Gateway;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\ApiPaymentHelpers;
use Illuminate\Http\Request;

class PaymentController extends Controller
{

    use ApiPaymentHelpers;

    public function depositInsert(Request $request)
    {
        $request->validate([
            'method_code' => 'required',
            'payment_trx' => 'required',
        ]);

        try{
            $apiPayment = $this->getApiPayment($request->payment_trx);

            if($apiPayment['status'] == 'error'){ 
                return back()->withNotify(['error', $apiPayment['message']]);
            }
        }catch(\Exception $error){
            $notify[] = ['error', $error->getMessage()];
            return back()->withNotify($notify);
        }

        $amount = $apiPayment->amount;
       
        $user = $apiPayment->user;
        $checkUserPayment = $this->checkUserPayment($user);
			
        if(@$checkUserPayment['status'] == 'error'){
            foreach(@$checkUserPayment['message'] as $message){
                $notify[] = ['error', $message];
            }
            return back()->withNotify(@$notify);
        }

		$gate = $this->paymentMethods(@$apiPayment->currency)->where('method_code', $request->method_code)->first();

        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }
 
        if (($gate->min_amount * $gate->rate) > $amount || ($gate->max_amount * $gate->rate) < $amount) {
            $notify[] = ['error', 'Please follow payment limit'];
            return back()->withNotify($notify);
        }

        $charge = ($gate->fixed_charge * $gate->rate) + ($amount * $gate->percent_charge / 100);
        $paymentCharge = ($user->payment_fixed_charge * $gate->rate) + ($amount * $user->payment_percent_charge / 100);

        $totalCharge = ($charge + $paymentCharge);
        $payable = ($amount - $totalCharge);

        $charge = $charge/$gate->rate;
        $paymentCharge = $paymentCharge/$gate->rate;

        $data = new Deposit();
        $data->user_id = $user->id;
        $data->method_code = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount = $amount/$gate->rate;
        $data->gateway_amount = $amount;
        $data->charge = $charge;
        $data->payment_charge = $paymentCharge;
        $data->rate = $gate->rate;
        $data->final_amount = $payable;
        $data->btc_amount = 0;
        $data->btc_wallet = "";
        $data->trx = getTrx();
        $data->success_url = $apiPayment->success_url;
        $data->failed_url = $apiPayment->cancel_url;
        $data->save();

        $apiPayment->deposit_id = $data->id;
        $apiPayment->save();

        session()->put('Track', $data->trx);
        return to_route('deposit.confirm');
    }


    public function appDepositConfirm($hash)
    {
        try {
            $id = decrypt($hash);
        } catch (\Exception $ex) {
            abort(404);
        }
        $data = Deposit::where('id', $id)->where('status', Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->firstOrFail();
        $user = User::findOrFail($data->user_id);
        auth()->login($user);
        session()->put('Track', $data->trx);
        return to_route('user.deposit.confirm');
    }


    public function depositConfirm()
    {
        $track = session()->get('Track');
        $deposit = Deposit::where('trx', $track)->where('status',Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->with('gateway')->firstOrFail();

        if ($deposit->method_code >= 1000) {
            return false;
        }


        $dirName = $deposit->gateway->alias;
        $new = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);


        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return back()->withNotify($notify);
        }
        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // for Stripe V3
        if(@$data->session){
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }

        $pageTitle = 'Payment Confirm';
        return view("Template::$data->view", compact('data', 'pageTitle', 'deposit'));
    }


    public static function userDataUpdate($deposit)
    {          
        if ($deposit->status == Status::PAYMENT_INITIATE || $deposit->status == Status::PAYMENT_PENDING) {
            $deposit->status = Status::PAYMENT_SUCCESS;
            $deposit->save();

            $user = User::find($deposit->user_id);
            $user->balance += $deposit->amount;
            $user->save();

            $apiPayment = $deposit->apiPayment;
            $apiPayment->status = Status::PAYMENT_SUCCESS;
            $apiPayment->save();

            $transaction = new Transaction();
            $transaction->user_id = $deposit->user_id;
            $transaction->amount = $deposit->amount;
            $transaction->post_balance = $user->balance;
            $transaction->charge = 0;
            $transaction->trx_type = '+';
            $transaction->details = 'Payment Via ' . $deposit->gatewayCurrency()->name;
            $transaction->trx = $deposit->trx;
            $transaction->remark = 'payment';
            $transaction->save();

            if($deposit->charge > 0){
                $user->balance -= $deposit->charge;
                $user->save();
    
                $minusTransaction = new Transaction();
                $minusTransaction->user_id = $deposit->user_id;
                $minusTransaction->amount = $deposit->charge;
                $minusTransaction->post_balance = $user->balance;
                $minusTransaction->charge = 0;
                $minusTransaction->trx_type = '-';
                $minusTransaction->details = 'Gateway charge';
                $minusTransaction->trx = $deposit->trx;
                $minusTransaction->remark = 'gateway_charge';
                $minusTransaction->save();
            }

            if($deposit->payment_charge > 0){
                $user->balance -= $deposit->payment_charge;
                $user->save();
    
                $minusTransaction = new Transaction();
                $minusTransaction->user_id = $deposit->user_id;
                $minusTransaction->amount = $deposit->payment_charge;
                $minusTransaction->post_balance = $user->balance;
                $minusTransaction->charge = 0;
                $minusTransaction->trx_type = '-';
                $minusTransaction->details = 'Payment charge';
                $minusTransaction->trx = $deposit->trx;
                $minusTransaction->remark = 'payment_charge';
                $minusTransaction->save();
            }

            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $user->id;
            $adminNotification->title = 'Deposit successful via '.$deposit->gatewayCurrency()->name;
            $adminNotification->click_url = urlPath('admin.deposit.successful');
            $adminNotification->save(); 

            self::outerIpn($apiPayment);

            notify($user, 'DEPOSIT_COMPLETE', [
                'method_name' => $deposit->gatewayCurrency()->name,
                'method_currency' => $deposit->method_currency,
                'method_amount' => showAmount($deposit->gateway_amount, currencyFormat:false),
                'amount' => showAmount($deposit->amount, currencyFormat:false),
                'charge' => showAmount($deposit->charge, currencyFormat:false),
                'payment_charge' => showAmount($deposit->payment_charge, currencyFormat:false),
                'rate' => showAmount($deposit->rate, currencyFormat:false),
                'trx' => $deposit->trx,
                'post_balance' => showAmount($user->balance, currencyFormat:false)
            ]);
        }
    }

}
