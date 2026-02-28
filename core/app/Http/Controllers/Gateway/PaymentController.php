<?php

namespace App\Http\Controllers\Gateway;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\PaymentLink;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PlanService;
use App\Traits\ApiPaymentHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $apiPayment['message'],
                    ], 422);
                }
                return back()->withNotify(['error', $apiPayment['message']]);
            }
        }catch(\Exception $error){
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $error->getMessage(),
                ], 422);
            }
            $notify[] = ['error', $error->getMessage()];
            return back()->withNotify($notify);
        }

        if ($request->filled('payment_link_code')) {
            $request->validate([
                'customer_full_name' => 'required_without:customer_first_name,customer_last_name|string|max:200',
                'customer_first_name' => 'required_without:customer_full_name|string|max:100',
                'customer_last_name' => 'required_without:customer_full_name|string|max:100',
                'customer_email' => 'required|email|max:255',
                'customer_mobile' => 'required|string|max:50',
            ]);
        }

        $paymentLink = null;

        $fullName = trim((string) $request->input('customer_full_name'));
        $firstName = $request->input('customer_first_name');
        $lastName = $request->input('customer_last_name');

        if ($fullName) {
            $parts = preg_split('/\s+/', $fullName);
            $firstName = $parts ? array_shift($parts) : $fullName;
            $lastName = trim(implode(' ', $parts));
            if ($lastName === '') {
                $lastName = 'N/A';
            }
        } else {
            $fullName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        }

        $customerPayload = [
            'name' => $fullName ?: null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $request->input('customer_email'),
            'mobile' => $request->input('customer_mobile'),
        ];

        if (array_filter($customerPayload)) {
            $apiPayment->customer = [
                'name' => $customerPayload['name'] ?? null,
                'first_name' => $customerPayload['first_name'] ?? null,
                'last_name' => $customerPayload['last_name'] ?? null,
                'email' => $customerPayload['email'] ?? null,
                'mobile' => $customerPayload['mobile'] ?? null,
            ];
            $apiPayment->save();
        }

        if ($request->filled('payment_link_code')) {
            $paymentLink = PaymentLink::where('code', $request->payment_link_code)->first();
            if (!$paymentLink) {
                $notify[] = ['error', 'Invalid payment link'];
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid payment link',
                    ], 422);
                }
                return back()->withNotify($notify);
            }

            $paymentLink->markExpiredIfNeeded();
            if ($paymentLink->status != PaymentLink::STATUS_ACTIVE) {
                $notify[] = ['error', 'This payment link is not available'];
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This payment link is not available',
                    ], 422);
                }
                return back()->withNotify($notify);
            }

            $apiPayment->amount = $paymentLink->amount;
            $apiPayment->currency = $paymentLink->currency ?: $apiPayment->currency;
            $apiPayment->details = $paymentLink->description ?: $apiPayment->details;
            if (!$apiPayment->success_url) {
                $apiPayment->success_url = $paymentLink->redirect_url;
            }
            if (!$apiPayment->cancel_url) {
                $apiPayment->cancel_url = $paymentLink->redirect_url;
            }
            $apiPayment->save();

            if (Schema::hasTable('plans') && !app(PlanService::class)->isFeatureEnabled($apiPayment->user, 'payment_links')) {
                $message = 'Payment Links are not enabled for this merchant plan. Please upgrade the plan.';
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $message,
                    ], 422);
                }
                $notify[] = ['error', $message];
                return back()->withNotify($notify);
            }
        }

        $amount = $apiPayment->amount;

        $user = $apiPayment->user;
        /** @var PlanService $planService */
        $planService = app(PlanService::class);
        $checkUserPayment = $this->checkUserPayment($user);
			
        if(@$checkUserPayment['status'] == 'error'){
            foreach(@$checkUserPayment['message'] as $message){
                $notify[] = ['error', $message];
            }
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => implode(' ', @$checkUserPayment['message'] ?? []),
                ], 422);
            }
            return back()->withNotify(@$notify);
        }

		$gate = $this->paymentMethods(@$apiPayment->currency)->where('method_code', $request->method_code)->first();

        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid gateway',
                ], 422);
            }
            return back()->withNotify($notify);
        }
 
        if (($gate->min_amount * $gate->rate) > $amount || ($gate->max_amount * $gate->rate) < $amount) {
            $notify[] = ['error', 'Please follow payment limit'];
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please follow payment limit',
                ], 422);
            }
            return back()->withNotify($notify);
        }

        $amountBase = $amount / $gate->rate;
        $chargeInGateway = ($gate->fixed_charge * $gate->rate) + ($amount * $gate->percent_charge / 100);
        $charge = $chargeInGateway / $gate->rate;

        $feeData = $planService->calculateFees($user, (float) $amountBase);
        $paymentCharge = (float) $feeData['fee_amount'];
        $paymentChargeInGateway = $paymentCharge * $gate->rate;

        $totalCharge = $chargeInGateway + $paymentChargeInGateway;
        $payable = max(0, $amount - $totalCharge);

        $data = new Deposit();
        $data->user_id = $user->id;
        $data->method_code = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount = $amount/$gate->rate;
        $data->gateway_amount = $amount;
        $data->charge = $charge;
        $data->payment_charge = $paymentCharge;
        if (Schema::hasColumn('deposits', 'fee_amount')) {
            $data->fee_amount = $feeData['fee_amount'];
        }
        if (Schema::hasColumn('deposits', 'net_amount')) {
            $data->net_amount = $feeData['net_amount'];
        }
        if (Schema::hasColumn('deposits', 'payout_eligible_at')) {
            $data->payout_eligible_at = $planService->computePayoutEligibleAt($user, now());
        }
        $data->rate = $gate->rate;
        $data->final_amount = $payable;
        $data->btc_amount = 0;
        $data->btc_wallet = "";
        if ($paymentLink) {
            $data->payment_link_id = $paymentLink->id;
        }
        $data->trx = getTrx();
        $data->success_url = $apiPayment->success_url;
        $data->failed_url = $apiPayment->cancel_url;
        $data->save();

        $apiPayment->deposit_id = $data->id;
        $apiPayment->save();

        session()->put('Track', $data->trx);

        if ($request->expectsJson()) {
            $dirName = $data->gateway->alias;
            $new = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

            $processResponse = $new::process($data);
            $processResponse = json_decode($processResponse);

            if (isset($processResponse->error)) {
                return response()->json([
                    'status' => 'error',
                    'message' => $processResponse->message ?? 'Something went wrong',
                ], 422);
            }

            if (isset($processResponse->redirect)) {
                if (in_array($dirName, ['BictorysCheckout', 'BictorysDirect'], true)) {
                    $conversion = (array) ($processResponse->conversion ?? []);
                    $amountValue = data_get($conversion, 'converted_amount');
                    $amountCurrency = strtoupper((string) (data_get($conversion, 'converted_currency') ?: $data->method_currency));
                    $amountText = $amountValue !== null
                        ? showAmount((float) $amountValue, currencyFormat: false) . ' ' . $amountCurrency
                        : showAmount((float) $data->gateway_amount, currencyFormat: false) . ' ' . strtoupper((string) $data->method_currency);

                    $html = view('Template::payment.partials.gateway_redirect_preview', [
                        'gatewayName' => $data->gateway->name,
                        'redirectUrl' => $processResponse->redirect_url ?? route('deposit.confirm'),
                        'reference' => $data->trx,
                        'amountText' => $amountText,
                    ])->render();

                    return response()->json([
                        'status' => 'form',
                        'html' => $html,
                    ]);
                }

                return response()->json([
                    'status' => 'redirect',
                    'redirect_url' => $processResponse->redirect_url ?? route('deposit.confirm'),
                ]);
            }

            if (isset($processResponse->session) && isset($processResponse->StripeJSAcc)) {
                return response()->json([
                    'status' => 'stripe_checkout',
                    'session_id' => $processResponse->session->id ?? null,
                    'publishable_key' => $processResponse->StripeJSAcc->publishable_key ?? null,
                ]);
            }

            if ($dirName === 'StripeJs' && isset($processResponse->src) && isset($processResponse->url)) {
                return response()->json([
                    'status' => 'stripe_js',
                    'src' => $processResponse->src,
                    'url' => $processResponse->url,
                    'method' => $processResponse->method ?? 'post',
                    'val' => $processResponse->val ?? [],
                ]);
            }

            if ($dirName === 'Stripe') {
                $html = view('Template::payment.partials.stripe_embedded', [
                    'data' => $processResponse,
                    'deposit' => $data,
                ])->render();

                return response()->json([
                    'status' => 'form',
                    'html' => $html,
                ]);
            }

            return response()->json([
                'status' => 'redirect',
                'redirect_url' => route('deposit.confirm'),
            ]);
        }

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
            /** @var PlanService $planService */
            $planService = app(PlanService::class);
            $user = User::find($deposit->user_id);
            if (!$user) {
                return;
            }

            $deposit->status = Status::PAYMENT_SUCCESS;

            if (Schema::hasColumn('deposits', 'fee_amount') && (float) $deposit->fee_amount <= 0) {
                $feeData = $planService->calculateFees($user, (float) $deposit->amount);
                $deposit->fee_amount = $feeData['fee_amount'];
                $deposit->net_amount = $feeData['net_amount'];
                $deposit->payment_charge = $feeData['fee_amount'];
            }

            if (Schema::hasColumn('deposits', 'payout_eligible_at') && !$deposit->payout_eligible_at) {
                $deposit->payout_eligible_at = $planService->computePayoutEligibleAt($user, $deposit->created_at);
            }

            $deposit->save();

            $user->balance += $deposit->amount;
            $user->save();

            $apiPayment = $deposit->apiPayment;
            if ($apiPayment) {
                $apiPayment->status = Status::PAYMENT_SUCCESS;
                $apiPayment->save();
            }

            if ($deposit->payment_link_id) {
                $paymentLink = PaymentLink::find($deposit->payment_link_id);
                if ($paymentLink && $paymentLink->status != PaymentLink::STATUS_PAID) {
                    $paymentLink->status = PaymentLink::STATUS_PAID;
                    $paymentLink->deposit_id = $deposit->id;
                    $paymentLink->paid_at = now();
                    $paymentLink->save();
                }
            }

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

            if (Schema::hasColumn('users', 'monthly_tx_count') && Schema::hasColumn('users', 'monthly_tx_count_reset_at')) {
                $planService->incrementMonthlyUsage($user);
            }

            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $user->id;
            $adminNotification->title = 'Deposit successful via '.$deposit->gatewayCurrency()->name;
            $adminNotification->click_url = urlPath('admin.deposit.successful');
            $adminNotification->save(); 

            if ($apiPayment) {
                self::outerIpn($apiPayment);
            }

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
            ], $planService->getNotificationChannels($user));
        }
    }

    public static function refundUserData(Deposit $deposit, string $note = null): bool
    {
        if ($deposit->status != Status::PAYMENT_SUCCESS) {
            return false;
        }

        $user = User::find($deposit->user_id);
        if (!$user) {
            return false;
        }

        $refundAmount = $deposit->amount - $deposit->totalCharge;
        if ($refundAmount < 0) {
            $refundAmount = 0;
        }

        $user->balance -= $refundAmount;
        $user->save();

        $deposit->status = Status::PAYMENT_REFUNDED;
        if ($note) {
            $deposit->admin_feedback = $note;
        }
        $deposit->save();

        $transaction = new Transaction();
        $transaction->user_id = $deposit->user_id;
        $transaction->amount = $refundAmount;
        $transaction->post_balance = $user->balance;
        $transaction->charge = 0;
        $transaction->trx_type = '-';
        $transaction->details = 'Refund from ' . $deposit->gatewayCurrency()->name . ' (TRX: ' . $deposit->trx . ')';
        $transaction->trx = $deposit->trx;
        $transaction->remark = 'refund';
        $transaction->save();

        return true;
    }

}
