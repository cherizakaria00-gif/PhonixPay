<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiPaymentProcess;
use App\Traits\ApiPaymentHelpers;
use Illuminate\Http\Request;

class LivePaymentController extends Controller{

    use ApiPaymentProcess, ApiPaymentHelpers;

    protected $paymentType = 'live';

    public function paymentCheckout(Request $request){

        $pageTitle = "Payment Checkout";
		$trx = $request->payment_trx;

		$apiPayment = $this->getApiPayment($trx); 
		$gatewayCurrency = $this->paymentMethods(@$apiPayment->currency, @$apiPayment->gateway_methods)->orderby('method_code')->get();

        return view('Template::payment.deposit',compact('pageTitle', 'gatewayCurrency', 'apiPayment', 'trx'));
    }

}
 