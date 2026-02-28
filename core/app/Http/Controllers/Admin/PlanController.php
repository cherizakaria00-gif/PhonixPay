<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanChangeRequest;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function __construct(private readonly PlanService $planService)
    {
        parent::__construct();
    }

    public function index()
    {
        $pageTitle = 'Plans';
        $emptyMessage = 'No plans found';
        $plans = Plan::orderBy('sort_order')->orderBy('id')->paginate(getPaginate());

        return view('admin.plans.index', compact('pageTitle', 'emptyMessage', 'plans'));
    }

    public function create()
    {
        $pageTitle = 'Create Plan';
        $plan = new Plan([
            'currency' => 'USD',
            'is_active' => true,
            'payout_frequency' => 'weekly_7d',
            'support_channels' => ['email'],
            'notification_channels' => ['push'],
            'features' => ['payment_links' => false],
        ]);

        return view('admin.plans.form', compact('pageTitle', 'plan'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);

        $plan = new Plan();
        $plan->fill($validated);
        $plan->save();

        $notify[] = ['success', 'Plan created successfully'];
        return to_route('admin.plans.index')->withNotify($notify);
    }

    public function edit($id)
    {
        $plan = Plan::findOrFail($id);
        $pageTitle = 'Edit Plan - ' . $plan->name;

        return view('admin.plans.form', compact('pageTitle', 'plan'));
    }

    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);
        $validated = $this->validatePlan($request, $plan);

        $plan->fill($validated);
        $plan->save();

        $notify[] = ['success', 'Plan updated successfully'];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->is_active = !$plan->is_active;
        $plan->save();

        $notify[] = ['success', 'Plan status updated'];
        return back()->withNotify($notify);
    }

    public function delete($id)
    {
        $plan = Plan::findOrFail($id);

        if ($plan->is_default) {
            $notify[] = ['error', 'Default plans cannot be deleted'];
            return back()->withNotify($notify);
        }

        if (User::where('plan_id', $plan->id)->exists()) {
            $notify[] = ['error', 'This plan is assigned to merchants. Disable it instead.'];
            return back()->withNotify($notify);
        }

        $plan->delete();

        $notify[] = ['success', 'Plan deleted successfully'];
        return back()->withNotify($notify);
    }

    public function merchants(Request $request)
    {
        $pageTitle = 'Merchant Plans';
        $emptyMessage = 'No merchants found';

        $query = User::with('plan')
            ->withMax('payouts as last_payout_at', 'scheduled_for')
            ->orderByDesc('id');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $merchants = $query->paginate(getPaginate());
        $plans = Plan::active()->orderBy('sort_order')->get();

        $usage = [];
        foreach ($merchants as $merchant) {
            $usage[$merchant->id] = $this->planService->usageSummary($merchant);
        }

        return view('admin.plans.merchants', compact('pageTitle', 'emptyMessage', 'merchants', 'usage', 'plans'));
    }

    public function merchantDetail($id)
    {
        $merchant = User::with('plan')->findOrFail($id);
        $plans = Plan::active()->orderBy('sort_order')->get();
        $usage = $this->planService->usageSummary($merchant);
        $effectivePlan = $this->planService->getEffectivePlan($merchant);
        $lastPayout = $merchant->payouts()->latest('scheduled_for')->first();

        $pageTitle = 'Merchant Plan Detail - ' . ($merchant->username ?? $merchant->email);

        return view('admin.plans.merchant_detail', compact('pageTitle', 'merchant', 'plans', 'usage', 'effectivePlan', 'lastPayout'));
    }

    public function assignMerchantPlan(Request $request, $id)
    {
        $merchant = User::findOrFail($id);
        $request->validate([
            'plan_id' => ['required', Rule::exists('plans', 'id')],
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $this->planService->assignPlan($merchant, $plan, false);

        $merchant->plan_custom_overrides = null;
        $merchant->save();

        $notify[] = ['success', 'Plan assigned successfully'];
        return back()->withNotify($notify);
    }

    public function updateMerchantOverrides(Request $request, $id)
    {
        $merchant = User::findOrFail($id);

        $request->validate([
            'fee_percent' => 'nullable|numeric|between:0,100',
            'fee_fixed' => 'nullable|numeric|gte:0',
            'tx_limit' => 'nullable|integer|min:0',
            'payout_frequency' => 'nullable|in:weekly_7d,twice_weekly,every_2_days',
            'support_channels' => 'nullable|array',
            'support_channels.*' => 'in:email,whatsapp',
            'notification_channels' => 'nullable|array',
            'notification_channels.*' => 'in:email,sms,push',
            'payment_links_override' => 'nullable|in:default,enabled,disabled',
        ]);

        $features = null;
        if ($request->payment_links_override === 'enabled') {
            $features = ['payment_links' => true];
        } elseif ($request->payment_links_override === 'disabled') {
            $features = ['payment_links' => false];
        }

        $txLimit = $request->tx_limit;
        if ($txLimit !== null && $txLimit !== '' && (int) $txLimit === 0) {
            $txLimit = 'unlimited';
        }

        $overrides = [
            'fee_percent' => $request->fee_percent,
            'fee_fixed' => $request->fee_fixed,
            'tx_limit' => $txLimit,
            'payout_frequency' => $request->payout_frequency,
            'support_channels' => $request->support_channels,
            'notification_channels' => $request->notification_channels,
            'features' => $features,
        ];

        $clean = array_filter($overrides, function ($value) {
            return $value !== null && $value !== '';
        });

        $merchant->plan_custom_overrides = $clean ?: null;
        $merchant->save();

        $notify[] = ['success', 'Overrides updated successfully'];
        return back()->withNotify($notify);
    }

    public function requests()
    {
        $pageTitle = 'Plan Change Requests';
        $emptyMessage = 'No plan change request found';
        $requests = PlanChangeRequest::with(['user', 'fromPlan', 'toPlan'])
            ->orderByDesc('id')
            ->paginate(getPaginate());

        return view('admin.plans.requests', compact('pageTitle', 'emptyMessage', 'requests'));
    }

    public function approveRequest($id)
    {
        $changeRequest = PlanChangeRequest::with(['user', 'toPlan'])->findOrFail($id);

        if ($changeRequest->status !== 'pending') {
            $notify[] = ['error', 'This request is already processed'];
            return back()->withNotify($notify);
        }

        if (!$changeRequest->toPlan || !$changeRequest->toPlan->is_active) {
            $notify[] = ['error', 'Target plan is unavailable'];
            return back()->withNotify($notify);
        }

        if (!$changeRequest->user) {
            $notify[] = ['error', 'Merchant account not found'];
            return back()->withNotify($notify);
        }

        $this->planService->assignPlan($changeRequest->user, $changeRequest->toPlan, false);
        $changeRequest->user->plan_custom_overrides = null;
        $changeRequest->user->save();
        $changeRequest->status = 'approved';
        $changeRequest->save();

        $notify[] = ['success', 'Plan change request approved'];
        return back()->withNotify($notify);
    }

    public function rejectRequest(Request $request, $id)
    {
        $changeRequest = PlanChangeRequest::findOrFail($id);

        if ($changeRequest->status !== 'pending') {
            $notify[] = ['error', 'This request is already processed'];
            return back()->withNotify($notify);
        }

        $changeRequest->status = 'rejected';
        $changeRequest->note = $request->note;
        $changeRequest->save();

        if ($changeRequest->user) {
            $changeRequest->user->plan_status = 'active';
            $changeRequest->user->save();
        }

        $notify[] = ['success', 'Plan change request rejected'];
        return back()->withNotify($notify);
    }

    private function validatePlan(Request $request, ?Plan $plan = null): array
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', Rule::unique('plans', 'slug')->ignore($plan?->id)],
            'name' => 'required|string|max:150',
            'price_monthly_cents' => 'required|integer|min:0',
            'currency' => 'required|string|max:10',
            'tx_limit_monthly' => 'nullable|integer|min:1',
            'fee_percent' => 'required|numeric|between:0,100',
            'fee_fixed' => 'required|numeric|gte:0',
            'payout_frequency' => 'required|in:weekly_7d,twice_weekly,every_2_days',
            'payout_delay_days' => 'nullable|integer|min:0',
            'support_channels' => 'nullable|array',
            'support_channels.*' => 'in:email,whatsapp',
            'notification_channels' => 'nullable|array',
            'notification_channels.*' => 'in:email,sms,push',
            'payment_links' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = strtolower($validated['slug']);
        $validated['features'] = [
            'payment_links' => (bool) ($request->payment_links ?? false),
        ];
        $validated['is_active'] = (bool) ($request->is_active ?? false);
        $validated['sort_order'] = (int) ($request->sort_order ?? 0);

        if ($plan && $plan->is_default) {
            $validated['is_default'] = true;
        } elseif (!$plan) {
            $validated['is_default'] = (bool) ($request->is_default ?? false);
        }

        return $validated;
    }
}
