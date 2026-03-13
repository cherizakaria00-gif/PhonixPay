<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AiIntegration;
use App\Models\GatewayCurrency;
use App\Models\PaymentLink;
use App\Services\AiIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AiIntegrationController extends Controller
{
    public function __construct(private readonly AiIntegrationService $service)
    {
        parent::__construct();
    }

    public function index()
    {
        $pageTitle = 'AI Integration';
        $merchant = auth()->user();

        $integration = $this->service->forMerchant($merchant);
        $prompts = $this->service->buildPrompts($integration);
        $snippets = $this->service->buildSnippets($integration);

        $paymentLinks = PaymentLink::query()
            ->where('user_id', $merchant->id)
            ->when(Schema::hasColumn('payment_links', 'link_type'), fn ($query) => $query->where('link_type', PaymentLink::TYPE_STANDARD))
            ->latest('id')
            ->take(50)
            ->get();

        $currencies = GatewayCurrency::query()
            ->whereHas('method', fn ($query) => $query->where('status', 1))
            ->pluck('currency')
            ->map(fn ($currency) => strtoupper((string) $currency))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($currencies->isEmpty()) {
            $currencies = collect(['USD']);
        }

        return view('Template::user.developer.ai_integration.index', compact(
            'pageTitle',
            'integration',
            'paymentLinks',
            'prompts',
            'snippets',
            'currencies'
        ));
    }

    public function selectOption(Request $request)
    {
        $request->validate([
            'selected_option' => 'required|in:' . implode(',', [
                AiIntegration::OPTION_API_KEYS,
                AiIntegration::OPTION_PAYMENT_LINK,
                AiIntegration::OPTION_PLUGIN_SDK,
            ]),
        ]);

        $this->service->selectOption(auth()->user(), $request->selected_option, $request);

        $notify[] = ['success', 'Integration option updated'];
        return back()->withNotify($notify);
    }

    public function saveApiKeys(Request $request)
    {
        $request->validate([
            'success_url' => 'required|url|max:255',
            'cancel_url' => 'required|url|max:255',
        ]);

        $this->service->saveApiKeysConfig(auth()->user(), $request->only(['success_url', 'cancel_url']), $request);

        $notify[] = ['success', 'API Keys integration configured successfully'];
        return back()->withNotify($notify);
    }

    public function selectPaymentLink(Request $request)
    {
        $request->validate([
            'payment_link_id' => 'required|integer',
            'button_label' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
            'amount_mode' => 'nullable|in:fixed,customer_defined',
        ]);

        $paymentLink = PaymentLink::query()
            ->where('id', $request->payment_link_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->service->savePaymentLinkConfig(auth()->user(), $paymentLink, $request->only(['button_label', 'description', 'amount_mode']), $request);

        $notify[] = ['success', 'Payment Link integration configured successfully'];
        return back()->withNotify($notify);
    }

    public function createPaymentLink(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'amount_mode' => 'required|in:fixed,customer_defined',
            'amount' => 'nullable|numeric|gt:0',
            'currency' => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
            'button_label' => 'nullable|string|max:100',
        ]);

        if ($request->amount_mode === 'fixed' && !(float) $request->amount) {
            $notify[] = ['error', 'Amount is required for fixed mode'];
            return back()->withNotify($notify)->withInput();
        }

        $paymentLink = $this->service->createPaymentLinkForAi(auth()->user(), [
            'amount_mode' => $request->amount_mode,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'description' => $request->description ?: $request->name,
            'redirect_url' => route('user.home'),
            'button_label' => $request->button_label ?: 'Pay now',
        ], $request);

        $this->service->savePaymentLinkConfig(auth()->user(), $paymentLink, $request->only(['amount_mode', 'button_label', 'description']), $request);

        $notify[] = ['success', 'Payment Link created and connected'];
        return back()->withNotify($notify);
    }

    public function savePluginSdk(Request $request)
    {
        $request->validate([
            'merchant_email' => 'required|email|max:191',
            'website_url' => 'required|string|max:255',
        ]);

        $this->service->savePluginSdkConfig(auth()->user(), $request->only(['merchant_email', 'website_url']), $request);

        $notify[] = ['success', 'Plugin/SDK integration configured successfully'];
        return back()->withNotify($notify);
    }

    public function generateLicense(Request $request)
    {
        $license = $this->service->ensurePluginLicense(auth()->user(), $request);

        $notify[] = ['success', 'License key ready: ' . $license->license_key];
        return back()->withNotify($notify);
    }
}
