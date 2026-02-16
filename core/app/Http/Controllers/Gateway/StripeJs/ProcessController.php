<?php

namespace App\Http\Controllers\Gateway\StripeJs;

use App\Constants\Status;
use App\Models\Deposit;
use App\Http\Controllers\Gateway\PaymentController;
use App\Helpers\StripeAccountHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Session;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Stripe;


class ProcessController extends Controller
{

    public static function process($deposit)
    {
        $StripeJSAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);
        $stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);
        if ($stripeAccount && !empty($stripeAccount->publishable_key)) {
            $val['key'] = $stripeAccount->publishable_key;
            $deposit->stripe_account_id = $stripeAccount->id;
            $deposit->save();
        } else {
            $val['key'] = $StripeJSAcc->publishable_key;
        }
        
        $val['description'] = "Payment with Stripe";
        $val['amount'] = round($deposit->final_amount,2) * 100;
        $val['currency'] = $deposit->method_currency;
        $send['val'] = $val;


        $alias = $deposit->gateway->alias;

        $send['src'] = "https://checkout.stripe.com/checkout.js";
        $send['view'] = 'payment.' . $alias;
        $send['method'] = 'post';
        $send['url'] = route('ipn.'.$deposit->gateway->alias);
        return json_encode($send);
    }

    public function ipn(Request $request)
    {

        $track = Session::get('Track');
        $deposit = Deposit::where('trx', $track)->orderBy('id', 'DESC')->first();
        if ($deposit->status == Status::PAYMENT_SUCCESS) {
            $notify[] = ['error', 'Invalid request.'];
            return redirect($deposit->failed_url)->withNotify($notify);
        }

        try {
            // Select the appropriate Stripe account for this deposit
            $stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);

            if ($stripeAccount && !empty($stripeAccount->secret_key)) {
                // Set the Stripe API key for the selected account
                Stripe::setApiKey($stripeAccount->secret_key);
                // Save the selected account ID to the deposit
                $deposit->stripe_account_id = $stripeAccount->id;
                $deposit->save();
            } else {
                // Fallback to legacy gateway settings
                $StripeJSAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);
                if (empty($StripeJSAcc->secret_key)) {
                    $notify[] = ['error', 'Payment gateway configuration error. Please try again later.'];
                    return back()->withNotify($notify);
                }
                Stripe::setApiKey($StripeJSAcc->secret_key);
            }

            Stripe::setApiVersion("2020-03-02");

            $customer = Customer::create([
                'email' => $request->stripeEmail,
                'source' => $request->stripeToken,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stripe customer creation error', [
                'deposit_id' => $deposit->id,
                'error' => $e->getMessage(),
            ]);
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        try {
            $charge = Charge::create([
                'customer' => $customer->id,
                'description' => 'Payment with Stripe',
                'amount' => round($deposit->final_amount, 2) * 100,
                'currency' => strtolower($deposit->method_currency),
                'metadata' => [
                    'deposit_id' => $deposit->id,
                    'trx' => $deposit->trx,
                ],
            ]);
            if (Schema::hasColumn('deposits', 'stripe_charge_id')) {
                $deposit->stripe_charge_id = $charge->id;
            }
            $deposit->save();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stripe charge creation error', [
                'deposit_id' => $deposit->id,
                'error' => $e->getMessage(),
            ]);
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }


        if ($charge['status'] == 'succeeded') {
            PaymentController::userDataUpdate($deposit);
            $notify[] = ['success', 'Payment captured successfully'];
            return redirect($deposit->success_url)->withNotify($notify);
        } else {
            $notify[] = ['error', 'Failed to process'];
            return back()->withNotify($notify);
        }
    }
}
