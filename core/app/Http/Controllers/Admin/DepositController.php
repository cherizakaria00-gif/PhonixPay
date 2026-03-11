<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Services\BictorysRefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DepositController extends Controller
{
    public function pending($userId = null)
    {
        $pageTitle = 'Pending Payments';
        return $this->depositData('pending', false, userId:$userId, pageTitle:$pageTitle);
    }

    public function approved($userId = null)
    {
        $pageTitle = 'Approved Payments';
        return $this->depositData('approved', false, userId:$userId, pageTitle:$pageTitle);
    }

    public function successful($userId = null)
    {
        $pageTitle = 'Successful Payments';
        return $this->depositData('successful', false, userId:$userId, pageTitle:$pageTitle);
    }

    public function rejected($userId = null)
    {
        $pageTitle = 'Canceled Payments';
        return $this->depositData('rejected', false, userId:$userId, pageTitle:$pageTitle);
    }

    public function initiated($userId = null)
    {
        $pageTitle = 'Initiated Payments';
        return $this->depositData('initiated', false, userId:$userId, pageTitle:$pageTitle);
    }

    public function refunded($userId = null)
    {
        $pageTitle = 'Refunded Payments';
        return $this->depositData('refunded', false, userId:$userId, pageTitle:$pageTitle);
    }

    public function deposit($userId = null)
    {
        $pageTitle = 'Payment History';
        return $this->depositData($scope = null, $summary = true,userId:$userId, pageTitle:$pageTitle);
    }

    protected function depositData($scope = null,$summary = false,$userId = null, $pageTitle)
    {  
        if ($scope) {
            $deposits = Deposit::$scope()->with(['user', 'gateway', 'stripeAccount', 'apiPayment']);
        }else{
            $deposits = Deposit::with(['user', 'gateway', 'stripeAccount', 'apiPayment']);
        }

        if ($userId) {
            $deposits = $deposits->where('user_id',$userId);
        }

        $deposits = $deposits->searchable(['trx','user:username'])->dateFilter();

        $request = request();

        if ($request->method) {
            if ($request->method != Status::GOOGLE_PAY) {
                $method = Gateway::where('alias',$request->method)->firstOrFail();
                $deposits = $deposits->where('method_code',$method->code);
            }else{
                $deposits = $deposits->where('method_code',Status::GOOGLE_PAY);
            }
        }

        if (!$summary) {
            if($request->export_type){
                return $deposits->export();
            }
            $deposits = $deposits->orderBy('id','desc')->paginate(getPaginate());
            return view('admin.deposit.log', compact('pageTitle', 'deposits'));
        }else{
            $successful = clone $deposits;
            $pending = clone $deposits;
            $rejected = clone $deposits;
            $initiated = clone $deposits;

            $successful = $successful->where('status',Status::PAYMENT_SUCCESS)->sum('amount');
            $pending = $pending->where('status',Status::PAYMENT_PENDING)->sum('amount');
            $rejected = $rejected->where('status',Status::PAYMENT_REJECT)->sum('amount');
            $initiated = $initiated->where('status',Status::PAYMENT_INITIATE)->sum('amount');

            if($request->export_type){
                return $deposits->export();
            }
            $deposits = $deposits->orderBy('id','desc')->paginate(getPaginate());
            return view('admin.deposit.log', compact('pageTitle', 'deposits','successful','rejected','initiated', 'pending'));
        }
    }

    public function details($id)
    {
        $deposit = Deposit::where('id', $id)->with(['user', 'gateway', 'stripeAccount', 'apiPayment'])->firstOrFail();
        $pageTitle = $deposit->user->username.' requested ' . showAmount($deposit->amount);
        $details = ($deposit->detail != null) ? json_encode($deposit->detail) : null;
        return view('admin.deposit.detail', compact('pageTitle', 'deposit','details'));
    }

    public function refund($id)
    {
        $deposit = Deposit::where('id', $id)->with(['user', 'gateway', 'stripeAccount', 'apiPayment'])->firstOrFail();

        if ($deposit->status == Status::PAYMENT_REFUNDED) {
            $notify[] = ['error', 'This payment is already refunded'];
            return back()->withNotify($notify);
        }

        if ($deposit->status != Status::PAYMENT_SUCCESS) {
            $notify[] = ['error', 'Only successful payments can be refunded'];
            return back()->withNotify($notify);
        }

        $isStripeGateway = $this->isStripeGateway($deposit);
        $isBictorysGateway = $this->isBictorysGateway($deposit);

        if (!$isStripeGateway && !$isBictorysGateway) {
            $notify[] = ['error', 'Refund is available only for Stripe and Bictorys payments'];
            return back()->withNotify($notify);
        }

        if ($isBictorysGateway) {
            $providerRefund = app(BictorysRefundService::class)->refundDeposit($deposit, 'Refunded by admin');
            if (!($providerRefund['success'] ?? false)) {
                Log::warning('Admin refund blocked: Bictorys provider refund failed', [
                    'deposit_id' => $deposit->id,
                    'trx' => $deposit->trx,
                    'gateway_alias' => $deposit->gateway->alias ?? null,
                    'provider_result' => $providerRefund,
                ]);
                $notify[] = ['error', $providerRefund['message'] ?? 'Bictorys refund failed'];
                return back()->withNotify($notify);
            }

            $detail = is_array($deposit->detail) ? $deposit->detail : (array) $deposit->detail;
            data_set($detail, 'bictorys.refund.endpoint', $providerRefund['endpoint'] ?? null);
            data_set($detail, 'bictorys.refund.reference', $providerRefund['refund_reference'] ?? null);
            data_set($detail, 'bictorys.refund.response', $providerRefund['response'] ?? []);
            data_set($detail, 'bictorys.refund.refunded_at', now()->toIso8601String());
            $deposit->detail = $detail;
            $deposit->save();
        }

        $refunded = PaymentController::refundUserData($deposit, 'Refunded by admin');
        if (!$refunded) {
            $notify[] = ['error', 'Local refund update failed'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Refund recorded successfully'];
        return back()->withNotify($notify);
    }

    protected function isStripeGateway(Deposit $deposit): bool
    {
        $alias = strtolower((string) ($deposit->gateway->alias ?? ''));
        $name = strtolower((string) ($deposit->gateway->name ?? ''));

        return str_contains($alias, 'stripe') || str_contains($name, 'stripe');
    }

    protected function isBictorysGateway(Deposit $deposit): bool
    {
        $alias = strtolower((string) ($deposit->gateway->alias ?? ''));
        $name = strtolower((string) ($deposit->gateway->name ?? ''));

        return str_contains($alias, 'bictorys') || str_contains($name, 'bictorys');
    }


    public function approve($id)
    {
        $deposit = Deposit::where('id',$id)->where('status',Status::PAYMENT_PENDING)->firstOrFail();

        PaymentController::userDataUpdate($deposit,true);

        $notify[] = ['success', 'Payment request approved successfully'];

        return to_route('admin.deposit.pending')->withNotify($notify);
    }

    public function reject(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'message' => 'required|string|max:255'
        ]);
        $deposit = Deposit::where('id',$request->id)->where('status',Status::PAYMENT_PENDING)->firstOrFail();

        $deposit->admin_feedback = $request->message;
        $deposit->status = Status::PAYMENT_REJECT;
        $deposit->save();

        notify($deposit->user, 'DEPOSIT_REJECT', [
            'method_name' => $deposit->methodName(),
            'method_currency' => $deposit->method_currency,
            'method_amount' => showAmount($deposit->final_amount,currencyFormat:false),
            'amount' => showAmount($deposit->amount,currencyFormat:false),
            'charge' => showAmount($deposit->charge,currencyFormat:false),
            'rate' => showAmount($deposit->rate,currencyFormat:false),
            'trx' => $deposit->trx,
            'rejection_message' => $request->message
        ]);

        $notify[] = ['success', 'Payment request rejected successfully'];
        return  to_route('admin.deposit.pending')->withNotify($notify);

    }
}
