<?php

namespace App\Http\Controllers\Gateway\Stripe;

use App\Constants\Status;
use App\Models\Deposit;
use App\Http\Controllers\Gateway\PaymentController;
use App\Helpers\StripeAccountHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Token;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class ProcessController extends Controller
{

    /*
     * Stripe Gateway
     */
    public static function process($deposit)
    {

        $alias = $deposit->gateway->alias;

        $send['track'] = $deposit->trx;
        $send['view'] = 'payment.'.$alias;
        $send['method'] = 'post';
        $send['url'] = route('ipn.'.$alias);
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
        $request->validate([
            'cardNumber' => 'required',
            'cardExpiry' => 'required',
            'cardCVC' => 'required',
        ]);

        try {
            // Select the appropriate Stripe account for this deposit
            $stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);

            if ($stripeAccount && !empty($stripeAccount->secret_key)) {
                // Set the Stripe API key for this account
                Stripe::setApiKey($stripeAccount->secret_key);
                // Save the selected account ID to the deposit
                $deposit->stripe_account_id = $stripeAccount->id;
                $deposit->save();
            } else {
                // Fallback to legacy gateway settings
                $StripeAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);
                if (empty($StripeAcc->secret_key)) {
                    Log::error('No Stripe credentials available', ['deposit_id' => $deposit->id]);
                    $notify[] = ['error', 'Payment gateway configuration error. Please try again later.'];
                    return back()->withNotify($notify);
                }
                Stripe::setApiKey($StripeAcc->secret_key);
            }

            Stripe::setApiVersion("2020-03-02");

            $cc = $request->cardNumber;
            $exp = $request->cardExpiry;
            $cvc = $request->cardCVC;

            $exp = explode("/", $_POST['cardExpiry']);
            if (!@$exp[1]) {
                $notify[] = ['error', 'Invalid expiry date provided'];
                return back()->withNotify($notify);
            }
            $emo = trim($exp[0]);
            $eyr = trim($exp[1]);
            $cents = round($deposit->final_amount, 2) * 100;

            try {
                $token = Token::create(array(
                    "card" => array(
                        "number" => "$cc",
                        "exp_month" => $emo,
                        "exp_year" => $eyr,
                        "cvc" => "$cvc"
                    )
                ));
                try {
                    $charge = Charge::create(array(
                        'card' => $token['id'],
                        'currency' => strtolower($deposit->method_currency),
                        'amount' => $cents,
                        'description' => 'Deposit ' . $deposit->trx,
                        'metadata' => [
                            'deposit_id' => $deposit->id,
                            'trx' => $deposit->trx,
                            'stripe_account_id' => $stripeAccount->id,
                        ],
                    ));

                    if (Schema::hasColumn('deposits', 'stripe_charge_id')) {
                        $deposit->stripe_charge_id = $charge->id;
                    }
                    $deposit->save();

                    if ($charge['status'] == 'succeeded') {
                        PaymentController::userDataUpdate($deposit);
                        $notify[] = ['success', 'Payment captured successfully'];
                        return redirect($deposit->success_url)->withNotify($notify);
                    }
                } catch (\Exception $e) {
                    Log::error('Stripe charge creation error', [
                        'deposit_id' => $deposit->id,
                        'error' => $e->getMessage(),
                    ]);
                    $notify[] = ['error', $e->getMessage()];
                }
            } catch (\Exception $e) {
                Log::error('Stripe token creation error', [
                    'deposit_id' => $deposit->id,
                    'error' => $e->getMessage(),
                ]);
                $notify[] = ['error', $e->getMessage()];
            }
        } catch (\Exception $e) {
            Log::error('Stripe payment processing error', [
                'deposit_id' => $deposit->id,
                'error' => $e->getMessage(),
            ]);
            $notify[] = ['error', 'Payment error: ' . $e->getMessage()];
        }

        return back()->withNotify($notify);
    }
}
