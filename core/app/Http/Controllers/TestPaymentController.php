<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiPaymentProcess;
use App\Traits\ApiPaymentHelpers;
use Illuminate\Http\Request;
use App\Constants\Status;

class TestPaymentController extends Controller{

    use ApiPaymentProcess, ApiPaymentHelpers;

    protected $paymentType = 'test';

    public function paymentCheckout(Request $request){
        
        $pageTitle = "Payment Checkout";
		$trx = $request->payment_trx;

		$apiPayment = $this->getApiPayment($trx);  	

        return view('Template::payment.test_deposit',compact('pageTitle', 'apiPayment', 'trx'));
    }

    public function paymentSuccess(Request $request){

        $request->validate([
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

        $apiPayment->status = Status::PAYMENT_SUCCESS;
        $apiPayment->save();

        self::outerIpn($apiPayment);
        return redirect(paymentRedirectUrl($apiPayment));
    }

}
 