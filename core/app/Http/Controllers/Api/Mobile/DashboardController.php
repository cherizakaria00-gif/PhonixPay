<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Constants\Status;
use App\Models\Deposit;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class DashboardController extends ApiMobileController
{
    public function stats(Request $request)
    {
        $request->validate([
            'time' => 'nullable|in:day,week,month,year',
        ]);

        $time = match ((string) $request->input('time', 'day')) {
            'year' => now()->startOfYear(),
            'month' => now()->startOfMonth(),
            'week' => now()->startOfWeek(),
            default => now()->startOfDay(),
        };

        $userId = $request->user()->id;

        $paymentSummary = [
            'total' => (float) Deposit::where('user_id', $userId)->where('created_at', '>=', $time)->sum('amount'),
            'total_pending' => (float) Deposit::where('user_id', $userId)->where('status', Status::PAYMENT_PENDING)->where('created_at', '>=', $time)->sum('amount'),
            'total_approved' => (float) Deposit::where('user_id', $userId)->approved()->where('created_at', '>=', $time)->sum('amount'),
            'total_rejected' => (float) Deposit::where('user_id', $userId)->rejected()->where('created_at', '>=', $time)->sum('amount'),
            'total_succeed' => (float) Deposit::where('user_id', $userId)->successful()->where('created_at', '>=', $time)->sum('amount'),
            'total_refunded' => (float) Deposit::where('user_id', $userId)->where('status', Status::PAYMENT_REFUNDED)->where('created_at', '>=', $time)->sum('amount'),
            'total_canceled' => (float) Deposit::where('user_id', $userId)->where('status', Status::PAYMENT_CANCEL)->where('created_at', '>=', $time)->sum('amount'),
        ];

        $withdrawSummary = [
            'total' => (float) Withdrawal::where('user_id', $userId)->where('created_at', '>=', $time)->sum('amount'),
            'total_pending' => (float) Withdrawal::where('user_id', $userId)->pending()->where('created_at', '>=', $time)->sum('amount'),
            'total_approved' => (float) Withdrawal::where('user_id', $userId)->approved()->where('created_at', '>=', $time)->sum('amount'),
            'total_rejected' => (float) Withdrawal::where('user_id', $userId)->rejected()->where('created_at', '>=', $time)->sum('amount'),
        ];

        return $this->ok([
            'payment_summary' => $paymentSummary,
            'withdraw_summary' => $withdrawSummary,
        ]);
    }
}
