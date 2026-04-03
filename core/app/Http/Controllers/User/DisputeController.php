<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DisputeController extends Controller
{
    public function __construct(private readonly DisputeService $disputeService)
    {
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('disputes')) {
            $notify[] = ['error', 'Disputes module is not migrated yet. Please contact admin.'];
            return back()->withNotify($notify);
        }

        $pageTitle = 'Disputes';
        $merchant = auth()->user();

        $query = Dispute::query()->where('merchant_id', $merchant->id)->with('deposit');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->status);
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->search);
            $query->where(function ($sub) use ($term) {
                $sub->where('dispute_id', 'like', "%{$term}%")
                    ->orWhere('transaction_id', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%");
            });
        }

        $disputes = $query->latest('id')->paginate(getPaginate());
        $statuses = array_merge(Dispute::ACTIVE_STATUSES, Dispute::TERMINAL_STATUSES);
        $metrics = $this->disputeService->merchantMetrics($merchant);

        return view('Template::user.disputes.index', compact('pageTitle', 'disputes', 'statuses', 'metrics'));
    }

    public function show(int $id)
    {
        if (!Schema::hasTable('disputes')) {
            abort(404);
        }

        $merchant = auth()->user();
        $dispute = Dispute::query()
            ->where('merchant_id', $merchant->id)
            ->with(['deposit.gateway', 'deposit.apiPayment', 'logs'])
            ->findOrFail($id);

        $pageTitle = 'Dispute Details - ' . $dispute->dispute_id;

        return view('Template::user.disputes.show', compact('pageTitle', 'dispute'));
    }

    public function open(Request $request)
    {
        if (!Schema::hasTable('disputes')) {
            $notify[] = ['error', 'Disputes module is not migrated yet. Please contact admin.'];
            return back()->withNotify($notify);
        }

        $merchant = auth()->user();

        $request->validate([
            'deposit_id' => 'required|integer|exists:deposits,id',
            'reason' => 'nullable|string|max:255',
            'merchant_notes' => 'nullable|string',
        ]);

        $deposit = Deposit::query()
            ->with(['user', 'gateway', 'apiPayment'])
            ->where('id', (int) $request->deposit_id)
            ->where('user_id', $merchant->id)
            ->firstOrFail();

        try {
            $this->disputeService->openForDeposit(
                $deposit,
                [
                    'reason' => $request->reason,
                    'merchant_notes' => $request->merchant_notes,
                ],
                actorType: 'merchant',
                actorId: (int) $merchant->id
            );
        } catch (RuntimeException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Dispute opened successfully'];
        return back()->withNotify($notify);
    }

    public function resolve(Request $request, int $id)
    {
        if (!Schema::hasTable('disputes')) {
            $notify[] = ['error', 'Disputes module is not migrated yet. Please contact admin.'];
            return back()->withNotify($notify);
        }

        $request->validate([
            'merchant_note' => 'nullable|string|max:3000',
        ]);

        $merchant = auth()->user();
        $dispute = Dispute::query()->where('merchant_id', $merchant->id)->findOrFail($id);

        try {
            $this->disputeService->setPendingResolutionByMerchant(
                $dispute,
                (int) $merchant->id,
                $request->merchant_note
            );
        } catch (RuntimeException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Resolution request submitted. Admin has 24h to validate before auto-refund.'];
        return back()->withNotify($notify);
    }
}
