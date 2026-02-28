<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\GatewayCurrency;
use App\Models\PaymentLink;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentLinkController extends Controller
{
    public function __construct(private readonly PlanService $planService)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $pageTitle = 'Payment Links';
        $query = PaymentLink::where('user_id', auth()->id())->orderBy('id', 'desc');

        if ($request->search) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        $paymentLinks = $query->paginate(getPaginate());
        $paymentLinks->getCollection()->each->markExpiredIfNeeded();

        return view('Template::user.payment_links.index', compact('pageTitle', 'paymentLinks'));
    }

    public function create()
    {
        if ($redirect = $this->ensurePaymentLinksEnabled()) {
            return $redirect;
        }

        $pageTitle = 'Create Payment Link';
        $currencies = $this->availableCurrencies();

        return view('Template::user.payment_links.create', compact('pageTitle', 'currencies'));
    }

    public function store(Request $request)
    {
        if ($redirect = $this->ensurePaymentLinksEnabled()) {
            return $redirect;
        }

        $currencies = $this->availableCurrencies();

        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required|string|in:' . implode(',', $currencies),
            'description' => 'required|string|max:255',
            'redirect_url' => 'required|url|max:255',
            'expires_at' => 'required|date|after:now',
        ]);

        $paymentLink = new PaymentLink();
        $paymentLink->user_id = auth()->id();
        $paymentLink->code = $this->generateCode();
        $paymentLink->amount = $request->amount;
        $paymentLink->currency = strtoupper($request->currency);
        $paymentLink->description = $request->description;
        $paymentLink->redirect_url = $request->redirect_url;
        $paymentLink->expires_at = $request->expires_at;
        $paymentLink->status = PaymentLink::STATUS_ACTIVE;
        $paymentLink->save();

        $notify[] = ['success', 'Payment link created successfully'];
        return to_route('user.payment.links.index')->withNotify($notify);
    }

    public function edit($id)
    {
        if ($redirect = $this->ensurePaymentLinksEnabled()) {
            return $redirect;
        }

        $pageTitle = 'Edit Payment Link';
        $paymentLink = PaymentLink::where('user_id', auth()->id())->findOrFail($id);
        $paymentLink->markExpiredIfNeeded();

        if ($paymentLink->status != PaymentLink::STATUS_ACTIVE) {
            $notify[] = ['error', 'Only active payment links can be edited'];
            return back()->withNotify($notify);
        }

        $currencies = $this->availableCurrencies($paymentLink->currency);

        return view('Template::user.payment_links.edit', compact('pageTitle', 'paymentLink', 'currencies'));
    }

    public function update(Request $request, $id)
    {
        if ($redirect = $this->ensurePaymentLinksEnabled()) {
            return $redirect;
        }

        $paymentLink = PaymentLink::where('user_id', auth()->id())->findOrFail($id);
        $paymentLink->markExpiredIfNeeded();

        if ($paymentLink->status != PaymentLink::STATUS_ACTIVE) {
            $notify[] = ['error', 'Only active payment links can be updated'];
            return back()->withNotify($notify);
        }

        $currencies = $this->availableCurrencies($paymentLink->currency);

        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required|string|in:' . implode(',', $currencies),
            'description' => 'required|string|max:255',
            'redirect_url' => 'required|url|max:255',
            'expires_at' => 'required|date|after:now',
        ]);

        $paymentLink->amount = $request->amount;
        $paymentLink->currency = strtoupper($request->currency);
        $paymentLink->description = $request->description;
        $paymentLink->redirect_url = $request->redirect_url;
        $paymentLink->expires_at = $request->expires_at;
        $paymentLink->save();

        $notify[] = ['success', 'Payment link updated successfully'];
        return to_route('user.payment.links.index')->withNotify($notify);
    }

    protected function generateCode(): string
    {
        do {
            $code = Str::random(32);
        } while (PaymentLink::where('code', $code)->exists());

        return $code;
    }

    protected function ensurePaymentLinksEnabled()
    {
        $user = auth()->user();

        if (!Schema::hasTable('plans')) {
            return null;
        }

        if ($this->planService->isFeatureEnabled($user, 'payment_links')) {
            return null;
        }

        $notify[] = ['error', 'Payment Links are not available on your current plan. Please upgrade to continue.'];
        return to_route('user.plan.billing')->withNotify($notify);
    }

    protected function availableCurrencies(?string $currentCurrency = null): array
    {
        $query = GatewayCurrency::query()
            ->whereHas('method', function ($query) {
                $query->where('status', Status::ENABLE);
            });

        if (Schema::hasColumn('gateway_currencies', 'status')) {
            $query->where('status', Status::ENABLE);
        }

        $currencies = $query
            ->pluck('currency')
            ->map(fn ($currency) => strtoupper((string) $currency))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($currentCurrency) {
            $currentCurrency = strtoupper($currentCurrency);
            if (!in_array($currentCurrency, $currencies, true)) {
                $currencies[] = $currentCurrency;
                sort($currencies);
            }
        }

        return !empty($currencies) ? $currencies : ['USD'];
    }
}
