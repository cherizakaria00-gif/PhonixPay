<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\RewardLevel;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function __construct(private readonly RewardService $rewardService)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $pageTitle = 'Rewards Program';

        if (!$this->rewardService->isSchemaReady()) {
            $notify[] = ['error', 'Rewards module is not installed yet. Run rewards migrations first.'];
            return back()->withNotify($notify);
        }

        $query = User::query()->with('rewardStatus');
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                if (ctype_digit($search)) {
                    $builder->orWhere('id', (int) $search);
                }

                $builder->orWhere('username', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('firstname', 'like', '%' . $search . '%')
                    ->orWhere('lastname', 'like', '%' . $search . '%');
            });
        }

        $merchants = $query
            ->orderByDesc('id')
            ->paginate(getPaginate())
            ->withQueryString();

        return view('admin.rewards.index', compact('pageTitle', 'merchants', 'search'));
    }

    public function show(Request $request, int $id)
    {
        if (!$this->rewardService->isSchemaReady()) {
            $notify[] = ['error', 'Rewards module is not installed yet. Run rewards migrations first.'];
            return back()->withNotify($notify);
        }

        $merchant = User::query()->findOrFail($id);
        $summary = $this->rewardService->getSummary($merchant);

        $referrals = Referral::query()
            ->where('referrer_user_id', $merchant->id)
            ->with('referred:id,firstname,lastname,username,email')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'referrals_page')
            ->withQueryString();

        $ledger = $this->rewardService->ledgerQuery($merchant, $request->input('type'))
            ->paginate(20, ['*'], 'ledger_page')
            ->withQueryString();

        $pageTitle = 'Rewards: ' . ($merchant->fullname ?: $merchant->username);

        return view('admin.rewards.show', compact('pageTitle', 'merchant', 'summary', 'referrals', 'ledger'));
    }

    public function adjust(Request $request, int $id)
    {
        if (!$this->rewardService->isSchemaReady()) {
            $notify[] = ['error', 'Rewards module is not installed yet. Run rewards migrations first.'];
            return back()->withNotify($notify);
        }

        $merchant = User::query()->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'description' => 'required|string|max:255',
        ]);

        $amountCents = (int) round(((float) $validated['amount']) * 100);
        $this->rewardService->createAdminAdjustment($merchant, $amountCents, $validated['description']);

        $notify[] = ['success', 'Rewards ledger adjusted successfully'];
        return back()->withNotify($notify);
    }

    public function revokeReferral(Request $request, int $id)
    {
        if (!$this->rewardService->isSchemaReady()) {
            $notify[] = ['error', 'Rewards module is not installed yet. Run rewards migrations first.'];
            return back()->withNotify($notify);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $referral = Referral::query()->findOrFail($id);
        $this->rewardService->revokeReferral($referral, $validated['reason'] ?? null);

        $notify[] = ['success', 'Referral revoked successfully'];
        return back()->withNotify($notify);
    }

    public function levels()
    {
        if (!$this->rewardService->isSchemaReady()) {
            $notify[] = ['error', 'Rewards module is not installed yet. Run rewards migrations first.'];
            return back()->withNotify($notify);
        }

        $pageTitle = 'Reward Levels';
        $levels = RewardLevel::query()->orderBy('level_number')->get();

        return view('admin.rewards.levels', compact('pageTitle', 'levels'));
    }

    public function updateLevel(Request $request, int $id)
    {
        if (!$this->rewardService->isSchemaReady()) {
            $notify[] = ['error', 'Rewards module is not installed yet. Run rewards migrations first.'];
            return back()->withNotify($notify);
        }

        $level = RewardLevel::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'required_qualified_referrals' => 'required|integer|min:1',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'discount_duration_months' => 'nullable|integer|min:0|max:24',
            'revenue_share_bps' => 'nullable|integer|min:0|max:10000',
            'badge' => 'nullable|boolean',
            'priority_support' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $benefits = [];

        if ($request->filled('discount_percent')) {
            $benefits['discount_percent'] = (int) $validated['discount_percent'];
        }

        if ($request->filled('discount_duration_months')) {
            $benefits['discount_duration_months'] = (int) $validated['discount_duration_months'];
        }

        if ($request->filled('revenue_share_bps')) {
            $benefits['revenue_share_bps'] = (int) $validated['revenue_share_bps'];
        }

        $benefits['badge'] = $request->boolean('badge');
        $benefits['priority_support'] = $request->boolean('priority_support');

        $level->name = $validated['name'];
        $level->required_qualified_referrals = (int) $validated['required_qualified_referrals'];
        $level->benefits = $benefits;
        $level->is_active = $request->boolean('is_active', true);
        $level->save();

        $notify[] = ['success', 'Reward level updated successfully'];
        return back()->withNotify($notify);
    }
}
