<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanChangeRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PlanController extends Controller
{
    public function __construct(private readonly PlanService $planService)
    {
        parent::__construct();
    }

    public function billing()
    {
        $pageTitle = 'Plan & Billing';
        $user = auth()->user();

        $currentPlan = $this->planService->getEffectivePlan($user);
        $usage = $this->planService->usageSummary($user);

        $plans = Plan::active()->orderBy('sort_order')->get();
        $currentPlanId = $user->plan_id ?: $plans->firstWhere('slug', 'starter')?->id;

        $pendingRequest = PlanChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with('toPlan')
            ->latest('id')
            ->first();

        return view('Template::user.plan_billing', compact('pageTitle', 'user', 'currentPlan', 'usage', 'plans', 'pendingRequest', 'currentPlanId'));
    }

    public function requestChange(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $targetPlan = Plan::active()->findOrFail($request->plan_id);
        $effectivePlan = $this->planService->getEffectivePlan($user);
        $currentPlanId = $effectivePlan['id'] ?? $user->plan_id;

        if ((int) $currentPlanId === (int) $targetPlan->id) {
            $notify[] = ['error', 'You are already on this plan'];
            return back()->withNotify($notify);
        }

        $existingPending = PlanChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            $notify[] = ['error', 'You already have a pending plan change request'];
            return back()->withNotify($notify);
        }

        $changeRequest = new PlanChangeRequest();
        $changeRequest->user_id = $user->id;
        $changeRequest->from_plan_id = $currentPlanId;
        $changeRequest->to_plan_id = $targetPlan->id;
        $changeRequest->status = 'pending';
        $changeRequest->note = 'Manual plan change requested by merchant';
        $changeRequest->save();

        $user->plan_status = 'pending';
        $user->save();

        $notify[] = ['success', 'Plan change request submitted. Awaiting admin approval.'];
        return back()->withNotify($notify);
    }

    public function change(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $targetPlan = Plan::active()->findOrFail($request->plan_id);
        $user = auth()->user();
        $effectivePlan = $this->planService->getEffectivePlan($user);
        $currentPlanId = $effectivePlan['id'] ?? $user->plan_id;

        if ((int) $currentPlanId === (int) $targetPlan->id) {
            $notify[] = ['error', 'You are already on this plan'];
            return back()->withNotify($notify);
        }

        $priceAmount = round($targetPlan->price_monthly_cents / 100, 2);

        try {
            DB::transaction(function () use ($targetPlan, $priceAmount) {
                $merchant = User::lockForUpdate()->findOrFail(auth()->id());

                if ($priceAmount > 0) {
                    if ((float) $merchant->balance < $priceAmount) {
                        throw new \RuntimeException('INSUFFICIENT_BALANCE');
                    }

                    $merchant->balance = (float) $merchant->balance - $priceAmount;
                    $merchant->save();

                    $transaction = new Transaction();
                    $transaction->user_id = $merchant->id;
                    $transaction->amount = $priceAmount;
                    $transaction->post_balance = $merchant->balance;
                    $transaction->charge = 0;
                    $transaction->trx_type = '-';
                    $transaction->details = 'Subscription payment for ' . $targetPlan->name . ' plan';
                    $transaction->trx = getTrx();
                    $transaction->remark = 'plan_upgrade';
                    $transaction->save();
                }

                $this->planService->assignPlan($merchant, $targetPlan, false);
                $merchant->plan_custom_overrides = null;
                $merchant->save();

                PlanChangeRequest::where('user_id', $merchant->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'approved',
                        'note' => 'Auto-approved after successful plan payment',
                        'updated_at' => now(),
                    ]);
            });
        } catch (Throwable $e) {
            if ($e->getMessage() === 'INSUFFICIENT_BALANCE') {
                $notify[] = ['error', 'Insufficient balance to pay for this plan. Please fund your account first.'];
                return back()->withNotify($notify);
            }

            $notify[] = ['error', 'Could not process your plan upgrade. Please try again.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Plan updated successfully' . ($priceAmount > 0 ? ' after payment of $' . number_format($priceAmount, 2) : '') . '.'];
        return back()->withNotify($notify);
    }
}
