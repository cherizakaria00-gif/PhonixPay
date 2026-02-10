<?php

namespace App\Http\Controllers\Admin;

use App\Models\Deposit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentStatisticsController extends Controller{

    public function index()
    {
        $pageTitle = 'Payment Statistics';
        $totalPayment = Deposit::sum('amount');

        $widget['successful_payment'] = Deposit::successful()->sum('amount');
        $widget['initiated_payment'] = Deposit::initiated()->sum('amount');
        $widget['canceled_payment'] = Deposit::rejected()->sum('amount');

        $paymentByGateway = Deposit::successful()->selectRaw("sum(amount) as amount, method_code")
            ->with('gateway', function($gateway){
                return $gateway->select('code', 'name');
            })->groupBy('method_code')->orderBy('amount', 'desc')
        ->get(); 

        $paymentByCurrency = Deposit::successful()->selectRaw("SUM(amount) as amount, method_currency")
            ->groupBy('method_currency')->orderBy('amount', 'desc') 
        ->get();
 
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']; 
        $paymentByYear = Deposit::successful()->whereYear('created_at', '=', now()->format('Y'))
                ->selectRaw("DATE_FORMAT(created_at, '%b') as month, sum(amount) as total")
                ->groupBy('month')
                ->orderByRaw('MONTH(created_at)')
                ->pluck('total', 'month')
                ->toArray();

        $paymentThisYear = [];
        foreach ($months as $month) {
            $total = isset($paymentByYear[$month]) ? $paymentByYear[$month] : 0;
            $paymentThisYear[$month] = $total;
        }
 
        $firstPaymentYear = Deposit::selectRaw("DATE_FORMAT(created_at, '%Y') as date")->first();
        $recentPayments = Deposit::with('gateway', 'user')->orderBy('id', 'desc')->limit(3)->get();

        return view('admin.payment_statistics',compact('pageTitle', 'widget', 'totalPayment', 'paymentByGateway', 'paymentByCurrency', 'firstPaymentYear', 'recentPayments', 'paymentThisYear'));
    }

    public function statistics(Request $request)
    {   
        if ($request->time == 'year') {
            $time = now()->startOfYear();
            $prevTime = now()->startOfYear()->subYear();
        }
        elseif($request->time == 'month'){
            $time = now()->startOfMonth();
            $prevTime = now()->startOfMonth()->subMonth();
        }
        else{
            $time = now()->startOfWeek();
            $prevTime = now()->startOfWeek()->subWeek();
        } 

        $paymentStatus = $request->payment_status;
        
        $payments = Deposit::where('created_at', '>=', $time)
        ->selectRaw("SUM(amount) as amount, DATE_FORMAT(created_at, '%Y-%m-%d') as date")->groupBy('date');

        if(in_array($paymentStatus, ['initiated', 'successful', 'rejected'])){
            $payments = $payments->$paymentStatus();
        }

        $payments = $payments->get();
        $totalPayment = $payments->sum('amount');
        
        $payments = $payments->mapWithKeys(function($payment){
            return [
                $payment->date => (int)$payment->amount
            ];
        });

        $prevPayment = Deposit::where('created_at', '>=', $prevTime)->where('created_at','<',$time)->sum('amount');
        $paymentDiff = ($prevPayment ? $totalPayment/$prevPayment*100-100 : 0);

        if($paymentDiff > 0){
            $upDown = 'up';
        }else{
            $upDown = 'down';
        }
        $paymentDiff = abs($paymentDiff);

        return [
            'payments'=>$payments,
            'total_payment'=>$totalPayment,
            'payment_diff'=>round($paymentDiff,2),
            'up_down'=>$upDown
        ];
    }

    public function paymentCharge(Request $request)
    {
        $charges = Deposit::successful()->whereYear('created_at', $request->year)->whereMonth('created_at', $request->month)
        ->selectRaw("sum(charge) as amount, date(created_at) as date")->groupBy('date')
        ->get();

        $charges = $charges->mapWithKeys(function($charge){  
            return [
                $charge->date => (int) $charge->amount
            ];
        });

        return [
            'charges'=>$charges,
        ];
    }

}
