<?php

namespace App\Http\Controllers;

use App\Models\ApiPayment;
use App\Models\PaymentLink;
use App\Traits\ApiPaymentHelpers;
use Illuminate\Http\Request;
use stdClass;

class PaymentLinkController extends Controller
{
    use ApiPaymentHelpers;

    public function show(Request $request, $code)
    {
        $paymentLink = PaymentLink::where('code', $code)->with('user')->firstOrFail();
        $paymentLink->markExpiredIfNeeded();
        $linkTitle = $paymentLink->displayTitle();
        $linkDescription = (string) ($paymentLink->description ?: $linkTitle);
        $seoContents = new stdClass();
        $seoContents->description = $linkDescription;
        $seoContents->keywords = [];
        $seoContents->social_title = $linkTitle;
        $seoContents->social_description = $linkDescription;
        $seoContents->meta_title = $linkTitle;

        if ($paymentLink->status == PaymentLink::STATUS_PAID && !$paymentLink->allowsMultiplePayments()) {
            $pageTitle = $linkTitle;
            $message = 'This payment link has already been paid.';
            return view('Template::payment.payment_link_status', compact('pageTitle', 'message', 'seoContents'));
        }

        if ($paymentLink->status == PaymentLink::STATUS_EXPIRED || $paymentLink->isExpired()) {
            $pageTitle = $linkTitle;
            $message = 'This payment link has expired.';
            return view('Template::payment.payment_link_status', compact('pageTitle', 'message', 'seoContents'));
        }

        $user = $paymentLink->user;
        $checkUserPayment = $this->checkUserPayment($user, $paymentLink->isPlanSubscription());
        if (@$checkUserPayment['status'] == 'error') {
            $pageTitle = $linkTitle;
            $message = $checkUserPayment['message'][0] ?? 'This payment link is not available.';
            return view('Template::payment.payment_link_status', compact('pageTitle', 'message', 'seoContents'));
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
        $checkoutAutoSelection = $this->buildCheckoutAutoSelection($request, $gatewayCurrency);
        $ipCountryCode = $checkoutAutoSelection['ip_country_code'];
        $preferredMethodCode = $checkoutAutoSelection['preferred_method_code'];

        if (!$gatewayCurrency->count()) {
            $pageTitle = $linkTitle;
            $message = 'No payment gateway is available for this payment.';
            return view('Template::payment.payment_link_status', compact('pageTitle', 'message', 'seoContents'));
        }

        $pageTitle = $linkTitle;
        $showCustomerForm = true;

        return view('Template::payment.deposit', compact('pageTitle', 'gatewayCurrency', 'apiPayment', 'trx', 'paymentLink', 'showCustomerForm', 'ipCountryCode', 'preferredMethodCode', 'seoContents'));
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
