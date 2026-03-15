<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Constants\Status;
use App\Models\ApiPayment;
use Illuminate\Http\Request;

class PaymentController extends ApiMobileController
{
    public function intent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required|string|max:10',
            'description' => 'required|string|max:255',
        ]);

        $user = $request->user();

        $apiPayment = new ApiPayment();
        $apiPayment->user_id = $user->id;
        $apiPayment->type = 'live';
        $apiPayment->trx = getTrx();
        $apiPayment->identifier = 'mobile_' . strtolower(substr($apiPayment->trx, 0, 20));
        $apiPayment->ip = $request->ip();
        $apiPayment->amount = (float) $request->amount;
        $apiPayment->currency = strtoupper((string) $request->currency);
        $apiPayment->details = (string) $request->description;
        $apiPayment->site_name = (string) (gs('site_name') ?: 'FlujiPay');
        $apiPayment->checkout_theme = 'light';
        $apiPayment->ipn_url = route('home');
        $apiPayment->success_url = route('user.home');
        $apiPayment->cancel_url = route('user.home');
        $apiPayment->status = Status::PAYMENT_INITIATE;
        $apiPayment->customer = [
            'first_name' => (string) ($user->firstname ?: $user->name ?: 'Customer'),
            'last_name' => (string) ($user->lastname ?: 'Merchant'),
            'email' => (string) $user->email,
            'mobile' => (string) ($user->mobile ?: ''),
        ];
        $apiPayment->save();

        return $this->ok([
            'payment_url' => route('payment.checkout', ['payment_trx' => encrypt($apiPayment->trx)]),
            'reference' => $apiPayment->trx,
        ], 201);
    }
}
