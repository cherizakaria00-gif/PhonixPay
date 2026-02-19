<?php

namespace App\Http\Controllers;

use App\Models\ApiPayment;
use App\Models\PaymentLink;
use App\Traits\ApiPaymentHelpers;

class PaymentLinkController extends Controller
{
    use ApiPaymentHelpers;

    public function show($code)
    {
        $paymentLink = PaymentLink::where('code', $code)->with('user')->firstOrFail();
        $paymentLink->markExpiredIfNeeded();

        if ($paymentLink->status == PaymentLink::STATUS_PAID) {
            $pageTitle = 'Payment Link';
            $message = 'This payment link has already been paid.';
            return view('Template::payment.payment_link_status', compact('pageTitle', 'message'));
        }

        if ($paymentLink->status == PaymentLink::STATUS_EXPIRED || $paymentLink->isExpired()) {
            $pageTitle = 'Payment Link';
            $message = 'This payment link has expired.';
            return view('Template::payment.payment_link_status', compact('pageTitle', 'message'));
        }

        $user = $paymentLink->user;
        $checkUserPayment = $this->checkUserPayment($user);
        if (@$checkUserPayment['status'] == 'error') {
            $pageTitle = 'Payment Link';
            $message = $checkUserPayment['message'][0] ?? 'This payment link is not available.';
            return view('Template::payment.payment_link_status', compact('pageTitle', 'message'));
        }

        $apiPayment = new ApiPayment();
        $apiPayment->user_id = $user->id;
        $apiPayment->currency = $paymentLink->currency;
        $apiPayment->gateway_methods = null;
        $apiPayment->identifier = 'payment_link_' . $paymentLink->id;
        $apiPayment->trx = getTrx();
        $apiPayment->ip = getRealIP();
        $apiPayment->amount = $paymentLink->amount;
        $apiPayment->details = $paymentLink->description ?? 'Payment Link';
        $apiPayment->ipn_url = route('payment.link.ipn', $paymentLink->code);
        $apiPayment->success_url = route('payment.link.redirect', ['code' => $paymentLink->code, 'status' => 'success'], false);
        $apiPayment->cancel_url = route('payment.link.redirect', ['code' => $paymentLink->code, 'status' => 'cancel'], false);
        $apiPayment->site_name = gs('site_name');
        $apiPayment->site_logo = null;
        $apiPayment->checkout_theme = 'light';
        $apiPayment->type = 'live';
        $apiPayment->save();

        $trx = encrypt($apiPayment->trx);
        $gatewayCurrency = $this->paymentMethods($apiPayment->currency, $apiPayment->gateway_methods)->orderby('method_code')->get();

        if (!$gatewayCurrency->count()) {
            $pageTitle = 'Payment Link';
            $message = 'No payment gateway is available for this payment.';
            return view('Template::payment.payment_link_status', compact('pageTitle', 'message'));
        }

        $pageTitle = 'Payment Link';
        $showCustomerForm = true;

        return view('Template::payment.deposit', compact('pageTitle', 'gatewayCurrency', 'apiPayment', 'trx', 'paymentLink', 'showCustomerForm'));
    }

    public function ipn($code)
    {
        return response()->json(['status' => 'ok']);
    }

    public function redirect($code)
    {
        $paymentLink = PaymentLink::where('code', $code)->firstOrFail();
        $target = $paymentLink->redirect_url ?: route('home');
        $status = request('status');

        if ($status) {
            $separator = str_contains($target, '?') ? '&' : '?';
            $target .= $separator . 'status=' . urlencode($status);
        }

        return redirect($target);
    }
}
