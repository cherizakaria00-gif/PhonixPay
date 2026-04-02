<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Dispute;
use App\Models\User;
use App\Services\DisputeService;
use Carbon\Carbon;
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
            $notify[] = ['error', 'Disputes module is not migrated yet. Please run database migration first.'];
            return back()->withNotify($notify);
        }

        $pageTitle = 'Dispute Management';

        $query = Dispute::query()->with(['merchant', 'deposit']);

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', (int) $request->merchant_id);
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->status);
        }

        if ($request->filled('provider')) {
            $query->where('provider', 'like', '%' . trim((string) $request->provider) . '%');
        }

        if ($request->filled('date')) {
            $parts = preg_split('/\s*-\s*/', (string) $request->date);
            if (count($parts) === 2) {
                try {
                    $start = Carbon::parse(trim((string) $parts[0]))->startOfDay();
                    $end = Carbon::parse(trim((string) $parts[1]))->endOfDay();
                    $query->whereBetween('created_at', [$start, $end]);
                } catch (\Throwable $e) {
                    // Ignore invalid date filter.
                }
            }
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->search);
            $query->where(function ($sub) use ($term) {
                $sub->where('dispute_id', 'like', "%{$term}%")
                    ->orWhere('transaction_id', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%")
                    ->orWhere('provider_reference', 'like', "%{$term}%");
            });
        }

        $disputes = $query->latest('id')->paginate(getPaginate());

        $merchants = User::query()->select('id', 'username', 'firstname', 'lastname')->orderBy('username')->get();
        $statuses = array_merge(Dispute::ACTIVE_STATUSES, Dispute::TERMINAL_STATUSES);

        $summary = [
            'total_disputes' => Dispute::query()->count(),
            'open_disputes' => Dispute::query()->whereIn('status', Dispute::ACTIVE_STATUSES)->count(),
            'fees_collected' => (float) Dispute::query()->whereNotNull('dispute_fee_charged_at')->sum('dispute_fee'),
            'refunded_volume' => (float) Dispute::query()->whereNotNull('transaction_amount_debited_at')->sum('refunded_amount'),
        ];

        $merchantRiskRows = User::query()
            ->whereHas('disputes')
            ->withCount([
                'deposits as total_transactions' => function ($q) {
                    $q->successful();
                },
                'disputes as total_disputes',
                'disputes as open_disputes' => function ($q) {
                    $q->whereIn('status', Dispute::ACTIVE_STATUSES);
                },
            ])
            ->withSum([
                'deposits as total_volume' => function ($q) {
                    $q->successful();
                },
            ], 'amount')
            ->withSum('disputes as disputed_volume', 'amount')
            ->withSum('disputes as dispute_fees_collected', 'dispute_fee')
            ->orderByDesc('total_disputes')
            ->limit(20)
            ->get();

        return view('admin.disputes.index', compact(
            'pageTitle',
            'disputes',
            'merchants',
            'statuses',
            'summary',
            'merchantRiskRows'
        ));
    }

    public function show(int $id)
    {
        if (!Schema::hasTable('disputes')) {
            abort(404);
        }

        $dispute = Dispute::with(['merchant', 'deposit.gateway', 'logs'])->findOrFail($id);
        $pageTitle = 'Dispute Details - ' . $dispute->dispute_id;

        $merchantMetrics = $this->disputeService->merchantMetrics($dispute->merchant);

        return view('admin.disputes.show', compact('pageTitle', 'dispute', 'merchantMetrics'));
    }

    public function open(Request $request)
    {
        if (!Schema::hasTable('disputes')) {
            $notify[] = ['error', 'Disputes module is not migrated yet. Please run database migration first.'];
            return back()->withNotify($notify);
        }

        $request->validate([
            'deposit_id' => 'required|integer|exists:deposits,id',
            'reason' => 'nullable|string|max:255',
            'provider_email' => 'nullable|email|max:255',
            'admin_notes' => 'nullable|string',
        ]);

        $deposit = Deposit::query()->with(['user', 'gateway', 'apiPayment'])->findOrFail((int) $request->deposit_id);

        try {
            $this->disputeService->openForDeposit(
                $deposit,
                [
                    'reason' => $request->reason,
                    'provider_email' => $request->provider_email,
                    'admin_notes' => $request->admin_notes,
                ],
                actorType: 'admin',
                actorId: (int) auth('admin')->id()
            );
        } catch (RuntimeException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Dispute opened successfully'];
        return back()->withNotify($notify);
    }

    public function updateStatus(Request $request, int $id)
    {
        if (!Schema::hasTable('disputes')) {
            $notify[] = ['error', 'Disputes module is not migrated yet. Please run database migration first.'];
            return back()->withNotify($notify);
        }

        $request->validate([
            'status' => 'required|string|max:40',
            'admin_notes' => 'nullable|string',
            'merchant_notes' => 'nullable|string',
        ]);

        $dispute = Dispute::query()->with(['merchant', 'deposit'])->findOrFail($id);

        try {
            $this->disputeService->updateStatusByAdmin(
                $dispute,
                (string) $request->status,
                (int) auth('admin')->id(),
                [
                    'admin_notes' => $request->admin_notes,
                    'merchant_notes' => $request->merchant_notes,
                ]
            );
        } catch (RuntimeException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Dispute status updated'];
        return back()->withNotify($notify);
    }

    public function saveNotes(Request $request, int $id)
    {
        if (!Schema::hasTable('disputes')) {
            $notify[] = ['error', 'Disputes module is not migrated yet. Please run database migration first.'];
            return back()->withNotify($notify);
        }

        $request->validate([
            'admin_notes' => 'nullable|string',
            'merchant_notes' => 'nullable|string',
        ]);

        $dispute = Dispute::query()->findOrFail($id);
        $dispute->admin_notes = trim((string) $request->admin_notes) ?: null;
        $dispute->merchant_notes = trim((string) $request->merchant_notes) ?: null;
        $dispute->save();

        $notify[] = ['success', 'Notes saved'];
        return back()->withNotify($notify);
    }

    public function notifyProvider(Request $request, int $id)
    {
        if (!Schema::hasTable('disputes')) {
            $notify[] = ['error', 'Disputes module is not migrated yet. Please run database migration first.'];
            return back()->withNotify($notify);
        }

        $request->validate([
            'provider_email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:190',
            'message' => 'required|string|max:5000',
        ]);

        $dispute = Dispute::query()->findOrFail($id);

        try {
            $this->disputeService->sendProviderEmail(
                $dispute,
                (int) auth('admin')->id(),
                (string) $request->provider_email,
                (string) $request->message,
                (string) $request->subject
            );
        } catch (\Throwable $e) {
            $notify[] = ['error', 'Provider email failed: ' . $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Provider email sent'];
        return back()->withNotify($notify);
    }
}
