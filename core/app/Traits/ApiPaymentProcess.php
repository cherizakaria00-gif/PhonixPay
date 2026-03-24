<?php

namespace App\Traits;

use App\Models\User;
use App\Constants\Status;
use App\Models\ApiPayment;
use App\Http\Controllers\Gateway\PaymentController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait ApiPaymentProcess{

	public function validation($request){
		return Validator::make($request->all(),[
            'identifier'         => 'required|string|max:20',
            'currency'           => 'required|string|max:20',
			'gateway_methods'    => 'array',
            'amount'             => 'required|numeric|gt:0',
            'details'            => 'required|string|max:100',
            'ipn_url'            => 'required|url',
            'cancel_url'         => 'required|url',
            'success_url'        => 'required|url',
            'public_key'         => 'required|string|max:255',
            'site_name'          => 'required|string|max:255',
            'site_logo'          => 'url',
            'checkout_theme'     => 'in:dark,light|string|max:5',
            'customer'  	     => 'required|array',
            'customer.first_name' => 'required',
            'customer.last_name'  => 'required',
            'customer.email'  	 => 'required|email',
            'customer.mobile'  	 => 'required',
        ], [
			'customer.first_name.required' => 'The customer first name field is required',
			'customer.last_name.required'  => 'The customer last name field is required',
			'customer.email.required'     => 'The customer email field is required',
			'customer.mobile.required'    => 'The customer mobile field is required',
		]);
	} 

	public function checkUser($publicKey){  

		if($this->paymentType == 'live'){
			return User::where('public_api_key', $publicKey)->first();
		}

		return User::where('test_public_api_key', $publicKey)->first();
	}
 
	public function paymentInitiate(Request $request){ 

		$validator = $this->validation($request);
	    if($validator->fails()) {
	        return [
	            'status'=> 'error',
	            'message' => $validator->errors()->all()
	        ] ;
	    }
	
		$currency = $request->currency;
        $isWooRequest = $this->isWooRequest($request);
        $requestedGateways = $isWooRequest ? ['BictorysDirect', 'BictorysCheckout'] : $request->gateway_methods;
	    $getPaymentMethods = $this->paymentMethods($currency, $requestedGateways);

	    if(!$getPaymentMethods->count()){
			return [
	            'status'=> 'error',
				'message' => ['Currency or gateway method not supported']
			];
	    }
 
	    $user = $this->checkUser($request->public_key);
	    if(!$user){
	        return [
	            'status'=> 'error',
	            'message' => ['Invalid api key']
	        ];
	    }

		if($this->paymentType == 'live'){
			$checkUserPayment = $this->checkUserPayment($user);

			if(@$checkUserPayment['status'] == 'error'){
				return $checkUserPayment;
			}
		}

		$customer = (object) $request->customer;
		$customer = [
			'first_name'=>@$customer->first_name,
			'last_name'=>@$customer->last_name,
			'email'=>@$customer->email,
			'mobile'=>@$customer->mobile,
		];

		$shippingInfo = (object) $request->shipping_info;
		$shippingInfo = [
			'address_one'=>@$shippingInfo->address_one,
			'address_two'=>@$shippingInfo->address_two,
			'area'=>@$shippingInfo->area,
			'city'=>@$shippingInfo->city,
			'sub_city'=>@$shippingInfo->sub_city,
			'state'=>@$shippingInfo->state,
			'postcode'=>@$shippingInfo->postcode,
			'country'=>@$shippingInfo->country,
			'others'=>@$shippingInfo->others,
		];

		$billingInfo = (object) $request->billing_info;
		$billingInfo = [
			'address_one'=>@$billingInfo->address_one,
			'address_two'=>@$billingInfo->address_two,
			'area'=>@$billingInfo->area,
			'city'=>@$billingInfo->city,
			'sub_city'=>@$billingInfo->sub_city,
			'state'=>@$billingInfo->state,
			'postcode'=>@$billingInfo->postcode,
			'country'=>@$billingInfo->country,
			'others'=>@$billingInfo->others,
		];

		$apiPayment = new ApiPayment();
	    $apiPayment->user_id = $user->id;

	    $apiPayment->currency = $currency;
		$apiPayment->gateway_methods = $requestedGateways;
		$apiPayment->identifier = $request->identifier;

	    $apiPayment->trx = getTrx();
	    $apiPayment->ip = getRealIP();

	    $apiPayment->amount = $request->amount;
	    $apiPayment->details = $request->details;

	    $apiPayment->ipn_url = $request->ipn_url;
	    $apiPayment->success_url = $request->success_url;
	    $apiPayment->cancel_url = $request->cancel_url;

	    $apiPayment->site_name = $request->site_name;
	    $apiPayment->site_logo = $request->site_logo;
	    $apiPayment->checkout_theme = $request->checkout_theme;

	    $apiPayment->customer = $customer;
	    $apiPayment->shipping_info = $shippingInfo;
	    $apiPayment->billing_info = $billingInfo;

	    $apiPayment->type = $this->paymentType;
	    $apiPayment->save();

	    $trx = $apiPayment->trx; 

        $shouldDirectCheckout = $this->shouldDirectCheckout($request);

        if ($shouldDirectCheckout) {
            $availableGateways = $this->paymentMethods($currency, $request->gateway_methods)->orderBy('method_code')->get();
            $availableGateways = $this->filterBictorysOnly($availableGateways);

            if ($availableGateways->count()) {
                $methodCode = $request->input('method_code');
                if ($methodCode && !$availableGateways->firstWhere('method_code', (int) $methodCode)) {
                    $methodCode = null;
                }

                if (!$methodCode) {
                    $checkoutAutoSelection = $this->buildCheckoutAutoSelection($request, $availableGateways);
                    $methodCode = $checkoutAutoSelection['preferred_method_code'] ?? null;
                }

                if (!$methodCode) {
                    $methodCode = $availableGateways->first()->method_code ?? null;
                }

                if ($methodCode) {
                    $proxyRequest = Request::create(route('deposit.insert'), 'POST', [
                        'method_code'  => $methodCode,
                        'payment_trx'  => encrypt($trx),
                    ]);

                    if ($request->hasSession()) {
                        $proxyRequest->setLaravelSession($request->session());
                    }

                    $response = app(PaymentController::class)->depositInsert($proxyRequest);

                    if ($this->shouldRenderLegacyPopup($request) && $response instanceof RedirectResponse) {
                        return view('Template::payment.quick_redirect', [
                            'pageTitle' => 'Redirecting to secure payment',
                            'redirectUrl' => $response->getTargetUrl(),
                        ]);
                    }

                    return $response;
                }
            }

            return [
                'status' => 'error',
                'message' => ['Bictorys card gateway is not available for this currency'],
            ];
        }

	    if($this->paymentType == 'live'){
	        $url = route('payment.checkout', ['payment_trx'=>encrypt($trx)]);
	    }
		else{ 
	        $url = route('test.payment.checkout', ['payment_trx'=>encrypt($trx)]);
	    }

	    return redirect($url);
	}

    protected function shouldDirectCheckout(Request $request): bool
    {
        return $request->boolean('skip_checkout') || $request->boolean('direct_checkout');
    }

    protected function filterBictorysOnly($gateways)
    {
        return $gateways->filter(function ($gateway) {
            $alias = strtolower(trim((string) ($gateway->gateway_alias ?? data_get($gateway, 'method.alias', ''))));
            return in_array($alias, ['bictoryscheckout', 'bictorysdirect'], true);
        })->values();
    }

    protected function shouldRenderLegacyPopup(Request $request): bool
    {
        if ($request->boolean('force_popup_redirect')) {
            return true;
        }

        return $this->isWooRequest($request);
    }

    protected function isWooRequest(Request $request): bool
    {
        $orderId = trim((string) $request->input('order_id', ''));
        $identifier = trim((string) $request->input('identifier', ''));

        if ($orderId !== '' && $identifier !== '' && $orderId === $identifier) {
            return true;
        }

        $ipnUrl = strtolower(trim((string) $request->input('ipn_url', '')));
        return $ipnUrl !== '' && (str_contains($ipnUrl, 'order-pay') || str_contains($ipnUrl, 'wc-api') || str_contains($ipnUrl, 'woocommerce'));
    }


    public function paymentCancel($trx){
	
	   try{
			$apiPayment = $this->getApiPayment($trx);

			if($apiPayment['status'] == 'error'){ 
				return back()->withNotify(['error', $apiPayment['message']]);
		   }
		}catch(\Exception $error){
			return $error->getMessage();
		}
	
		if($this->paymentType == 'live'){ 
			$apiPayment->status = Status::PAYMENT_CANCEL;
			$apiPayment->cancel_reason = 'Canceled by customer';
			$apiPayment->save();

			$deposit = @$apiPayment->deposit;
			if($deposit){
				$deposit->status = Status::PAYMENT_REJECT;
				$deposit->save();	
			}
		}

		return redirect($apiPayment->cancel_url);
    }

}


