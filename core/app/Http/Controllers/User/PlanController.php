<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use App\Models\Plan;
use App\Models\PlanChangeRequest;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

        $appliedDiscountPercent = 0;
        $targetPriceCents = (int) $targetPlan->price_monthly_cents;

        if ($targetPriceCents > 0) {
            $charge = $this->planService->calculateSubscriptionCharge($user, $targetPriceCents);
            $appliedDiscountPercent = (int) ($charge['discount_percent'] ?? 0);
            $finalCents = (int) ($charge['final_cents'] ?? 0);

            if ($finalCents > 0) {
                $paymentLink = $this->createPlanSubscriptionPaymentLink($user, $targetPlan, $finalCents);
                $discountText = $appliedDiscountPercent > 0 ? ' (' . $appliedDiscountPercent . '% rewards discount applied)' : '';

                $notify[] = ['success', 'Payment link generated for ' . $targetPlan->name . ' plan. Complete checkout to activate your plan for 1 month' . $discountText . '.'];
                return redirect()->route('payment.link.show', $paymentLink->code)->withNotify($notify);
            }
        }

        try {
            DB::transaction(function () use ($targetPlan, &$appliedDiscountPercent) {
                $merchant = User::lockForUpdate()->with('plan')->findOrFail(auth()->id());

                if ($targetPlan->price_monthly_cents > 0) {
                    $charge = $this->planService->calculateSubscriptionCharge($merchant, (int) $targetPlan->price_monthly_cents);
                    $appliedDiscountPercent = (int) ($charge['discount_percent'] ?? 0);
                }

                PlanChangeRequest::where('user_id', $merchant->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'rejected',
                        'note' => 'Closed automatically after direct plan activation',
                        'updated_at' => now(),
                    ]);

                $this->planService->assignPlan($merchant, $targetPlan, false);
                $merchant->plan_custom_overrides = null;
                $merchant->plan_status = 'active';
                $merchant->save();
            });
        } catch (Throwable $e) {
            $notify[] = ['error', 'Could not activate the selected plan. Please try again.'];
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
        $notify[] = ['success', 'Plan activated successfully' . $discountText . '. Billing cycle is monthly.' . $renewText];
        return back()->withNotify($notify);
    }

    private function createPlanSubscriptionPaymentLink(User $merchant, Plan $plan, int $finalAmountCents): PaymentLink
    {
        $amount = round($finalAmountCents / 100, 2);

        $query = PaymentLink::query()
            ->where('user_id', $merchant->id)
            ->where('status', PaymentLink::STATUS_ACTIVE)
            ->where('amount', $amount)
            ->where(function ($builder) {
                $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if (Schema::hasColumn('payment_links', 'link_type')) {
            $query->where('link_type', PaymentLink::TYPE_PLAN_SUBSCRIPTION);
        }

        if (Schema::hasColumn('payment_links', 'plan_id')) {
            $query->where('plan_id', $plan->id);
        }

        $existingLink = $query->latest('id')->first();
        if ($existingLink) {
            return $existingLink;
        }

        $paymentLink = new PaymentLink();
        $paymentLink->user_id = $merchant->id;
        $paymentLink->code = $this->generatePaymentLinkCode();
        $paymentLink->amount = $amount;
        $paymentLink->currency = strtoupper((string) ($plan->currency ?: 'USD'));
        $paymentLink->description = 'Subscription payment - ' . $plan->name . ' plan (1 month)';
        $paymentLink->redirect_url = route('user.plan.billing');
        $paymentLink->expires_at = now()->addHours(24);
        $paymentLink->status = PaymentLink::STATUS_ACTIVE;

        if (Schema::hasColumn('payment_links', 'link_type')) {
            $paymentLink->link_type = PaymentLink::TYPE_PLAN_SUBSCRIPTION;
        }

        if (Schema::hasColumn('payment_links', 'plan_id')) {
            $paymentLink->plan_id = $plan->id;
        }

        $paymentLink->save();

        return $paymentLink;
    }

    private function generatePaymentLinkCode(): string
    {
        do {
            $code = Str::random(32);
        } while (PaymentLink::where('code', $code)->exists());

        return $code;
    }
}
