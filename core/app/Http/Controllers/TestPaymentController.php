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
        if (@$apiPayment['status'] == 'error') {
            $notify[] = ['error', $apiPayment['message'] ?? 'Invalid transaction request'];
            return back()->withNotify($notify);
        }

        $checkUserPayment = $this->checkUserPayment($apiPayment->user);
        if (@$checkUserPayment['status'] == 'error') {
            $notify[] = ['error', implode(' ', $checkUserPayment['message'] ?? [])];
            return back()->withNotify($notify);
        }

		$gatewayCurrency = $this->paymentMethods(@$apiPayment->currency, @$apiPayment->gateway_methods)->orderby('method_code')->get();
        $isTestMode = true;
        $checkoutAutoSelection = $this->buildCheckoutAutoSelection($request, $gatewayCurrency);
        $ipCountryCode = $checkoutAutoSelection['ip_country_code'];
        $preferredMethodCode = $checkoutAutoSelection['preferred_method_code'];

        return view('Template::payment.deposit', compact('pageTitle', 'gatewayCurrency', 'apiPayment', 'trx', 'isTestMode', 'ipCountryCode', 'preferredMethodCode'));
    }

    public function paymentSuccess(Request $request){

        $request->validate([
            'payment_trx' => 'required',
            'method_code' => 'required',
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
 
