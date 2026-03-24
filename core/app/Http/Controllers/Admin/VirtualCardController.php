<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VirtualCard;
use App\Models\VirtualCardSetting;
use App\Models\VirtualCardTransaction;
use App\Services\StrowalletService;
use App\Services\VirtualCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class VirtualCardController extends Controller
{
    public function __construct(private readonly VirtualCardService $virtualCardService)
    {
    }

    public function index(Request $request)
    {
        $pageTitle = 'Virtual Cards';
        $emptyMessage = 'No virtual cards found';
        $query = VirtualCard::with('user')->orderByDesc('id');

        if ($request->search) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('provider_card_id', 'like', "%$search%")
                    ->orWhere('last4', 'like', "%$search%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('username', 'like', "%$search%")
                           ->orWhere('email', 'like', "%$search%");
                    });
            });
        }

        $cards = $query->paginate(getPaginate());
        $settings = Schema::hasTable('virtual_card_settings') ? VirtualCardSetting::config() : null;
        $defaultEndpoints = (array) config('strowallet.endpoints', []);
        $resolvedEndpoints = [];
        foreach ($defaultEndpoints as $key => $defaultValue) {
            $saved = data_get($settings, "endpoints.$key");
            $resolvedEndpoints[$key] = filled($saved) ? (string) $saved : (string) $defaultValue;
        }

        $stats = [
            'total_cards' => VirtualCard::count(),
            'active_cards' => VirtualCard::query()->whereIn('status', ['active', 'success', 'succeed', 'live'])->count(),
            'total_balance' => (float) VirtualCard::sum('balance'),
            'total_funded' => (float) VirtualCardTransaction::query()->where('type', 'fund')->sum('amount'),
        ];

        $merchants = User::query()->select('id', 'firstname', 'lastname', 'username', 'email')->orderByDesc('id')->limit(300)->get();

        return view('admin.virtual_cards.index', compact('pageTitle', 'cards', 'emptyMessage', 'settings', 'stats', 'merchants', 'resolvedEndpoints'));
    }

    public function show($id)
    {
        $card = VirtualCard::with(['user', 'transactions'])->findOrFail($id);
        $pageTitle = 'Virtual Card Detail';
        $mastercardData = data_get($card->meta, 'mastercard_details');

        return view('admin.virtual_cards.show', compact('pageTitle', 'card', 'mastercardData'));
    }

    public function createForMerchant(Request $request, $userId)
    {
        $request->validate([
            'currency' => 'nullable|string|max:16',
            'name_on_card' => 'nullable|string|max:120',
            'card_label' => 'nullable|string|max:60',
            'card_color' => 'nullable|string|max:16',
        ]);

        $merchant = User::findOrFail($userId);

        try {
            $this->virtualCardService->createCard($merchant, $request->only(['currency', 'name_on_card', 'card_label', 'card_color']));
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Virtual card created for merchant'];
        return back()->withNotify($notify);
    }

    public function fund(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
        ]);

        $card = VirtualCard::with('user')->findOrFail($id);

        try {
            $this->virtualCardService->fundCardFromMerchantBalance($card->user, $card, (float) $request->amount);
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Virtual card funded'];
        return back()->withNotify($notify);
    }

    public function sync($id)
    {
        $card = VirtualCard::findOrFail($id);

        try {
            $synced = $this->virtualCardService->syncCardTransactions($card);
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', $synced . ' transaction(s) synced'];
        return back()->withNotify($notify);
    }

    public function syncAll()
    {
        $cards = VirtualCard::query()->get();
        $synced = 0;

        foreach ($cards as $card) {
            try {
                $synced += $this->virtualCardService->syncCardTransactions($card);
            } catch (\Throwable $e) {
            }
        }

        $notify[] = ['success', $synced . ' transaction(s) synced globally'];
        return back()->withNotify($notify);
    }

    public function updateSettings(Request $request)
    {
        if (!Schema::hasTable('virtual_card_settings')) {
            $notify[] = ['error', 'Virtual card settings table is not migrated yet'];
            return back()->withNotify($notify);
        }

        $request->validate([
            'enabled' => 'nullable|boolean',
            'provider' => 'required|string|max:32',
            'mode' => 'required|string|in:live,sandbox',
            'max_cards_per_merchant' => 'required|integer|min:1|max:100',
            'default_currency' => 'required|string|max:16',
            'base_url' => 'nullable|string|max:255',
            'webhook_url' => 'nullable|string|max:255',
            'secret_key' => 'nullable|string|max:255',
            'public_key' => 'nullable|string|max:255',
            'webhook_secret' => 'nullable|string|max:255',
            'card_details_text' => 'nullable|string',
            'fixed_charge' => 'nullable|numeric|min:0',
            'percent_charge' => 'nullable|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'daily_limit' => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
            'reload_fixed_charge' => 'nullable|numeric|min:0',
            'reload_percent_charge' => 'nullable|numeric|min:0',
            'reload_min_amount' => 'nullable|numeric|min:0',
            'reload_max_amount' => 'nullable|numeric|min:0',
            'reload_daily_limit' => 'nullable|numeric|min:0',
            'reload_monthly_limit' => 'nullable|numeric|min:0',
            'endpoint_create_customer' => 'nullable|string|max:255',
            'endpoint_get_customer' => 'nullable|string|max:255',
            'endpoint_update_customer' => 'nullable|string|max:255',
            'endpoint_create_card' => 'nullable|string|max:255',
            'endpoint_fund_card' => 'nullable|string|max:255',
            'endpoint_card_details' => 'nullable|string|max:255',
            'endpoint_card_transactions' => 'nullable|string|max:255',
            'endpoint_full_card_history' => 'nullable|string|max:255',
            'endpoint_freeze_unfreeze' => 'nullable|string|max:255',
            'endpoint_withdraw_from_card' => 'nullable|string|max:255',
            'endpoint_card_withdraw_status' => 'nullable|string|max:255',
            'endpoint_upgrade_card_limit' => 'nullable|string|max:255',
            'endpoint_mastercard_details' => 'nullable|string|max:255',
        ]);

        $settings = VirtualCardSetting::config();
        $settings->enabled = (bool) $request->boolean('enabled');
        $settings->provider = (string) $request->provider;
        $settings->mode = (string) $request->mode;
        $settings->max_cards_per_merchant = (int) $request->max_cards_per_merchant;
        $settings->default_currency = strtoupper((string) $request->default_currency);
        $settings->base_url = $request->base_url ? rtrim((string) $request->base_url, '/') : null;
        $settings->webhook_url = $request->webhook_url ? (string) $request->webhook_url : route('webhooks.strowallet.card');
        $settings->card_details_text = (string) ($request->card_details_text ?? '');

        $settings->fixed_charge = (float) ($request->fixed_charge ?? 0);
        $settings->percent_charge = (float) ($request->percent_charge ?? 0);
        $settings->min_amount = (float) ($request->min_amount ?? 0);
        $settings->max_amount = (float) ($request->max_amount ?? 0);
        $settings->daily_limit = (float) ($request->daily_limit ?? 0);
        $settings->monthly_limit = (float) ($request->monthly_limit ?? 0);

        $settings->reload_fixed_charge = (float) ($request->reload_fixed_charge ?? 0);
        $settings->reload_percent_charge = (float) ($request->reload_percent_charge ?? 0);
        $settings->reload_min_amount = (float) ($request->reload_min_amount ?? 0);
        $settings->reload_max_amount = (float) ($request->reload_max_amount ?? 0);
        $settings->reload_daily_limit = (float) ($request->reload_daily_limit ?? 0);
        $settings->reload_monthly_limit = (float) ($request->reload_monthly_limit ?? 0);

        if ($request->filled('secret_key')) {
            $settings->secret_key = (string) $request->secret_key;
        }
        if ($request->filled('public_key')) {
            $settings->public_key = (string) $request->public_key;
        }
        if ($request->filled('webhook_secret')) {
            $settings->webhook_secret = (string) $request->webhook_secret;
        }

        $defaultEndpoints = (array) config('strowallet.endpoints', []);
        $existingEndpoints = (array) $settings->endpoints;

        $endpoints = [
            'create_customer' => $request->endpoint_create_customer,
            'get_customer' => $request->endpoint_get_customer,
            'update_customer' => $request->endpoint_update_customer,
            'create_card' => $request->endpoint_create_card,
            'fund_card' => $request->endpoint_fund_card,
            'card_details' => $request->endpoint_card_details,
            'card_transactions' => $request->endpoint_card_transactions,
            'full_card_history' => $request->endpoint_full_card_history,
            'freeze_unfreeze' => $request->endpoint_freeze_unfreeze,
            'withdraw_from_card' => $request->endpoint_withdraw_from_card,
            'card_withdraw_status' => $request->endpoint_card_withdraw_status,
            'upgrade_card_limit' => $request->endpoint_upgrade_card_limit,
            'mastercard_details' => $request->endpoint_mastercard_details,
        ];

        foreach ($endpoints as $key => $value) {
            if (filled($value)) {
                $endpoints[$key] = (string) $value;
                continue;
            }

            $existing = $existingEndpoints[$key] ?? null;
            if (filled($existing)) {
                $endpoints[$key] = (string) $existing;
                continue;
            }

            $endpoints[$key] = (string) ($defaultEndpoints[$key] ?? '');
        }

        $settings->endpoints = $endpoints;
        $settings->save();

        $notify[] = ['success', 'Virtual card settings updated'];
        return back()->withNotify($notify);
    }

    public function freeze(Request $request, $id)
    {
        $card = VirtualCard::with('user')->findOrFail($id);
        $request->validate(['freeze' => 'required|boolean']);

        try {
            $this->virtualCardService->freezeCard($card, (bool) $request->boolean('freeze'));
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', $request->boolean('freeze') ? 'Card frozen successfully' : 'Card unfrozen successfully'];
        return back()->withNotify($notify);
    }

    public function withdraw(Request $request, $id)
    {
        $card = VirtualCard::with('user')->findOrFail($id);
        $request->validate([
            'amount' => 'required|numeric|gt:0',
        ]);

        try {
            $this->virtualCardService->withdrawFromCardToMerchantBalance($card->user, $card, (float) $request->amount);
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Card withdrawn to merchant balance successfully'];
        return back()->withNotify($notify);
    }

    public function upgradeLimit(Request $request, $id)
    {
        $card = VirtualCard::with('user')->findOrFail($id);
        $request->validate([
            'new_limit' => 'required|numeric|gt:0',
        ]);

        try {
            $this->virtualCardService->upgradeCardLimit($card, (float) $request->new_limit);
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Card limit upgrade request submitted'];
        return back()->withNotify($notify);
    }

    public function fetchMastercard($id, StrowalletService $strowalletService)
    {
        $card = VirtualCard::findOrFail($id);

        try {
            $response = $strowalletService->mastercardDetails([
                'card_id' => $card->provider_card_id,
            ]);
            $card->meta = array_merge((array) $card->meta, ['mastercard_details' => $response]);
            $card->save();
        } catch (\Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Mastercard details synced'];
        return back()->withNotify($notify);
    }
}
