<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\RewardLedger;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RewardController extends Controller
{
    public function __construct(private readonly RewardService $rewardService)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $pageTitle = 'Rewards';
        $user = auth()->user();

        $schemaReady = $this->rewardService->isSchemaReady();
        $summary = $this->rewardService->getSummary($user);

        $tab = strtolower((string) $request->input('tab', 'overview'));
        if (!in_array($tab, ['overview', 'refer', 'history'], true)) {
            $tab = 'overview';
        }

        $type = $request->input('type');
        $schemaError = null;

        if ($schemaReady) {
            $ledger = $this->rewardService->ledgerQuery($user, $type)
                ->paginate(getPaginate())
                ->withQueryString();

            $referrals = Referral::query()
                ->where('referrer_user_id', $user->id)
                ->with('referred:id,firstname,lastname,username,email')
                ->orderByDesc('id')
                ->paginate(10, ['*'], 'referrals_page')
                ->withQueryString();
        } else {
            $schemaError = 'Rewards module is not installed yet. Run: php artisan migrate --path=database/migrations/2026_03_01_000001_create_rewards_referral_program.php && php artisan db:seed --class=RewardLevelSeeder';
            $ledger = new LengthAwarePaginator([], 0, getPaginate(), 1, [
                'path' => $request->url(),
                'pageName' => 'page',
            ]);
            $referrals = new LengthAwarePaginator([], 0, 10, 1, [
                'path' => $request->url(),
                'pageName' => 'referrals_page',
            ]);
        }

        return view('Template::user.rewards.index', compact(
            'pageTitle',
            'summary',
            'tab',
            'ledger',
            'type',
            'referrals',
            'schemaError'
        ));
    }

    public function overview(Request $request)
    {
        if (!$this->rewardService->isSchemaReady()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rewards module is not installed yet',
            ], 503);
        }

        $summary = $this->rewardService->getSummary($request->user());

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_level' => $summary['current_level'],
                'qualified_referrals_count' => $summary['qualified_referrals_count'],
                'next_level_target' => $summary['next_level_target'],
                'progress_percent' => $summary['progress_percent'],
                'total_earned_cents' => $summary['total_earned_cents'],
                'withdrawable_balance_cents' => $summary['withdrawable_balance_cents'],
                'discount_active' => $summary['discount_active'],
                'discount_active_until' => optional($summary['status']->discount_active_until)->toDateTimeString(),
                'revenue_share_bps' => (int) $summary['status']->revenue_share_bps,
                'referral_link' => $summary['referral_link'],
                'referral_code' => $summary['referral_code']->code,
            ],
        ]);
    }

    public function ledger(Request $request)
    {
        if (!$this->rewardService->isSchemaReady()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rewards module is not installed yet',
            ], 503);
        }

        $validated = $request->validate([
            'type' => 'nullable|string|in:' . implode(',', [
                RewardLedger::TYPE_REFERRAL_BONUS,
                RewardLedger::TYPE_REVENUE_SHARE,
                RewardLedger::TYPE_DISCOUNT_CREDIT,
                RewardLedger::TYPE_ADJUSTMENT,
                RewardLedger::TYPE_REVERSAL,
            ]),
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $query = $this->rewardService->ledgerQuery($request->user(), $validated['type'] ?? null);
        $perPage = (int) ($validated['per_page'] ?? 20);

        $records = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $records,
        ]);
    }

    public function regenerateCode(Request $request)
    {
        if (!$this->rewardService->isSchemaReady()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rewards module is not installed yet',
                ], 503);
            }

            $notify[] = ['error', 'Rewards module is not installed yet'];
            return back()->withNotify($notify);
        }

        $code = $this->rewardService->regenerateReferralCode($request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Referral code regenerated successfully',
                'data' => [
                    'code' => $code->code,
                    'link' => $this->rewardService->getReferralLink($request->user()),
                ],
            ]);
        }

        $notify[] = ['success', 'Referral code regenerated successfully'];
        return back()->withNotify($notify);
    }
}
