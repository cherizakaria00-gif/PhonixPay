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
        $fromPlanName = $effectivePlan['name'] ?? null;
        $fromPlanPrice = (int) ($effectivePlan['price_monthly_cents'] ?? 0);

        if ((int) $currentPlanId === (int) $targetPlan->id) {
            $notify[] = ['error', 'You are already on this plan'];
            return back()->withNotify($notify);
        }

        $chargedAmount = 0.0;
        $appliedDiscountPercent = 0;

        try {
            DB::transaction(function () use ($targetPlan, &$chargedAmount, &$appliedDiscountPercent) {
                $merchant = User::lockForUpdate()->findOrFail(auth()->id());
                $charge = $this->planService->calculateSubscriptionCharge($merchant, (int) $targetPlan->price_monthly_cents);
                $priceAmount = round((int) $charge['final_cents'] / 100, 2);
                $appliedDiscountPercent = (int) ($charge['discount_percent'] ?? 0);

                PlanChangeRequest::where('user_id', $merchant->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'rejected',
                        'note' => 'Closed automatically after direct plan upgrade attempt',
                        'updated_at' => now(),
                    ]);

                if ($merchant->plan_status !== 'active') {
                    $merchant->plan_status = 'active';
                    $merchant->save();
                }

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
                    $transaction->details = $appliedDiscountPercent > 0
                        ? 'Subscription payment for ' . $targetPlan->name . ' plan with ' . $appliedDiscountPercent . '% rewards discount'
                        : 'Subscription payment for ' . $targetPlan->name . ' plan';
                    $transaction->trx = getTrx();
                    $transaction->remark = 'plan_upgrade';
                    $transaction->save();
                }

                $this->planService->assignPlan($merchant, $targetPlan, false);
                $merchant->plan_custom_overrides = null;
                $merchant->save();

                $chargedAmount = $priceAmount;
            });
        } catch (Throwable $e) {
            if ($e->getMessage() === 'INSUFFICIENT_BALANCE') {
                $notify[] = ['error', 'Insufficient balance to pay for this plan. Please fund your account first.'];
                return back()->withNotify($notify);
            }

            $notify[] = ['error', 'Could not process your plan upgrade. Please try again.'];
            return back()->withNotify($notify);
        }

        $freshUser = auth()->user()->fresh();
        $renewText = $freshUser?->plan_renews_at
            ? ' Next renewal: ' . showDateTime($freshUser->plan_renews_at, 'M d, Y')
            : '';

        if ((int) $targetPlan->price_monthly_cents > $fromPlanPrice && $freshUser) {
            $this->planService->sendPlanUpgradeNotification(
                $freshUser,
                $targetPlan->name,
                $fromPlanName,
                $freshUser->plan_renews_at
            );
        }

        $discountText = $appliedDiscountPercent > 0 ? ' (' . $appliedDiscountPercent . '% rewards discount applied)' : '';
        $notify[] = ['success', 'Plan updated successfully' . ($chargedAmount > 0 ? ' after payment of $' . number_format($chargedAmount, 2) . $discountText : '') . '. Billing cycle is monthly.' . $renewText];
        return back()->withNotify($notify);
    }
}
