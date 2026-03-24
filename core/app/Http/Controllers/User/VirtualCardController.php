<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\VirtualCard;
use App\Models\VirtualCardSetting;
use App\Models\VirtualCardTransaction;
use App\Services\VirtualCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class VirtualCardController extends Controller
{
    public function __construct(private readonly VirtualCardService $virtualCardService)
    {
        parent::__construct();
    }

    public function index()
    {
        $user = auth()->user();
        $pageTitle = 'Virtual Cards';

        $cards = VirtualCard::where('user_id', $user->id)
            ->with(['transactions' => fn ($q) => $q->limit(20)])
            ->orderByDesc('id')
            ->get();

        $maxCards = 3;
        if (Schema::hasTable('virtual_card_settings')) {
            $maxCards = (int) optional(VirtualCardSetting::query()->find(1))->max_cards_per_merchant ?: 3;
        }

        $remainingCardLimit = max($maxCards - $cards->count(), 0);
        $totalCardBalance = (float) $cards->sum('balance');
        $totalAddMoney = (float) VirtualCardTransaction::where('user_id', $user->id)->where('type', 'fund')->sum('amount');
        $totalTransactionsAmount = (float) VirtualCardTransaction::where('user_id', $user->id)->sum('amount');
        $activeCards = (int) $cards->filter(fn ($card) => in_array(strtolower((string) $card->status), ['active', 'succeed', 'success', 'live'], true))->count();
        $activeTickets = (int) SupportTicket::where('user_id', $user->id)->where('status', '!=', Status::TICKET_CLOSE)->count();

        $virtualCardSettings = Schema::hasTable('virtual_card_settings') ? VirtualCardSetting::query()->find(1) : null;
        $todayFunded = (float) VirtualCardTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'fund')
            ->whereDate('transacted_at', now()->toDateString())
            ->sum('amount');
        $monthFunded = (float) VirtualCardTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'fund')
            ->whereYear('transacted_at', now()->year)
            ->whereMonth('transacted_at', now()->month)
            ->sum('amount');

        $reloadDailyLimit = (float) data_get($virtualCardSettings, 'reload_daily_limit', 0);
        $reloadMonthlyLimit = (float) data_get($virtualCardSettings, 'reload_monthly_limit', 0);

        $addMoneyMeta = [
            'currency' => (string) data_get($virtualCardSettings, 'default_currency', 'USD'),
            'available_balance' => (float) $user->balance,
            'fixed_charge' => (float) data_get($virtualCardSettings, 'reload_fixed_charge', 0),
            'percent_charge' => (float) data_get($virtualCardSettings, 'reload_percent_charge', 0),
            'min_amount' => (float) data_get($virtualCardSettings, 'reload_min_amount', 0),
            'max_amount' => (float) data_get($virtualCardSettings, 'reload_max_amount', 0),
            'daily_limit' => $reloadDailyLimit,
            'monthly_limit' => $reloadMonthlyLimit,
            'remaining_daily_limit' => $reloadDailyLimit > 0 ? max($reloadDailyLimit - $todayFunded, 0) : 0,
            'remaining_monthly_limit' => $reloadMonthlyLimit > 0 ? max($reloadMonthlyLimit - $monthFunded, 0) : 0,
        ];

        return view('Template::user.virtual_cards.index', compact(
            'pageTitle',
            'user',
            'cards',
            'maxCards',
            'remainingCardLimit',
            'totalCardBalance',
            'totalAddMoney',
            'totalTransactionsAmount',
            'activeCards',
            'activeTickets',
            'addMoneyMeta'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'currency' => 'nullable|string|max:16',
            'name_on_card' => 'nullable|string|max:120',
            'card_label' => 'nullable|string|max:60',
            'card_color' => 'nullable|string|max:16',
        ]);

        try {
            $this->virtualCardService->createCard(auth()->user(), $request->only(['currency', 'name_on_card', 'card_label', 'card_color']));
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Virtual card created successfully'];
        return back()->withNotify($notify);
    }

    public function quickCreate()
    {
        $user = auth()->user();

        try {
            $this->virtualCardService->createCard($user, [
                'currency' => 'USD',
                'name_on_card' => trim((string) ($user->firstname . ' ' . $user->lastname)),
            ]);
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Virtual card created successfully'];
        return back()->withNotify($notify);
    }

    public function fund(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
        ]);

        $user = auth()->user();
        $card = VirtualCard::where('user_id', $user->id)->findOrFail($id);

        try {
            $this->virtualCardService->fundCardFromMerchantBalance($user, $card, (float) $request->amount);
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Card funded successfully'];
        return back()->withNotify($notify);
    }

    public function withdraw(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
        ]);

        $user = auth()->user();
        $card = VirtualCard::where('user_id', $user->id)->findOrFail($id);

        try {
            $this->virtualCardService->withdrawFromCardToMerchantBalance($user, $card, (float) $request->amount);
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Amount withdrawn to general balance successfully'];
        return back()->withNotify($notify);
    }

    public function sync($id)
    {
        $card = VirtualCard::where('user_id', auth()->id())->findOrFail($id);

        try {
            $synced = $this->virtualCardService->syncCardTransactions($card);
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', $synced . ' transaction(s) synced'];
        return back()->withNotify($notify);
    }
}
