<?php

namespace App\Services;

use App\Models\AiIntegration;
use App\Models\AiIntegrationEvent;
use App\Models\Deposit;
use App\Models\PaymentLink;
use App\Models\PluginLicense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiIntegrationService
{
    public function __construct(private readonly PluginLicenseService $pluginLicenseService)
    {
    }

    public function forMerchant(User $merchant): AiIntegration
    {
        $integration = AiIntegration::firstOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'status' => AiIntegration::STATUS_NOT_CONFIGURED,
                'merchant_email' => $merchant->email,
                'website_url' => $merchant->website_url,
                'normalized_domain' => $merchant->website_domain,
            ]
        );

        if (!$integration->merchant_email) {
            $integration->merchant_email = $merchant->email;
        }

        if (!$integration->website_url && $merchant->website_url) {
            $integration->website_url = $merchant->website_url;
        }

        if (!$integration->normalized_domain && $merchant->website_domain) {
            $integration->normalized_domain = $merchant->website_domain;
        }

        if ($integration->isDirty()) {
            $integration->save();
        }

        return $integration;
    }

    public function selectOption(User $merchant, string $option, ?Request $request = null): AiIntegration
    {
        $integration = $this->forMerchant($merchant);
        $integration->selected_option = $option;

        if ($integration->status === AiIntegration::STATUS_NOT_CONFIGURED) {
            $integration->status = AiIntegration::STATUS_DRAFT;
        }

        $integration->last_configured_at = now();
        $integration->save();

        $this->logEvent($integration, 'select_option', 'success', 'Integration option selected', $request, [
            'selected_option' => $option,
        ]);

        return $integration;
    }

    public function saveApiKeysConfig(User $merchant, array $payload, ?Request $request = null): AiIntegration
    {
        $integration = $this->forMerchant($merchant);

        $integration->selected_option = AiIntegration::OPTION_API_KEYS;
        $integration->public_key_reference = $merchant->public_api_key;
        $integration->secret_key_reference = $merchant->secret_api_key;
        $integration->success_url = trim((string) Arr::get($payload, 'success_url'));
        $integration->cancel_url = trim((string) Arr::get($payload, 'cancel_url'));
        $integration->status = AiIntegration::STATUS_CONNECTED;
        $integration->setup_completed_at = now();
        $integration->last_configured_at = now();

        $optionPayload = (array) $integration->option_payload;
        $optionPayload['api_keys'] = [
            'success_url' => $integration->success_url,
            'cancel_url' => $integration->cancel_url,
            'recommended' => 'Use server-side secret key only.',
        ];
        $integration->option_payload = $optionPayload;
        $integration->save();

        $this->logEvent($integration, 'configure_api_keys', 'success', 'API keys flow configured', $request, [
            'success_url' => $integration->success_url,
            'cancel_url' => $integration->cancel_url,
        ]);

        return $integration;
    }

    public function savePaymentLinkConfig(User $merchant, PaymentLink $paymentLink, array $payload = [], ?Request $request = null): AiIntegration
    {
        if ((int) $paymentLink->user_id !== (int) $merchant->id) {
            throw new \RuntimeException('Invalid payment link owner');
        }

        $integration = $this->forMerchant($merchant);
        $integration->selected_option = AiIntegration::OPTION_PAYMENT_LINK;
        $integration->payment_link_id = $paymentLink->id;
        $integration->payment_link_url = route('payment.link.show', $paymentLink->code);
        $integration->status = AiIntegration::STATUS_CONNECTED;
        $integration->setup_completed_at = now();
        $integration->last_configured_at = now();

        $optionPayload = (array) $integration->option_payload;
        $optionPayload['payment_link'] = [
            'payment_link_id' => $paymentLink->id,
            'payment_link_code' => $paymentLink->code,
            'amount_mode' => Arr::get($payload, 'amount_mode', 'fixed'),
            'button_label' => Arr::get($payload, 'button_label', 'Pay now'),
            'description' => Arr::get($payload, 'description', $paymentLink->description),
        ];
        $integration->option_payload = $optionPayload;
        $integration->save();

        $this->logEvent($integration, 'configure_payment_link', 'success', 'Payment link flow configured', $request, [
            'payment_link_id' => $paymentLink->id,
            'payment_link_code' => $paymentLink->code,
        ]);

        return $integration;
    }

    public function createPaymentLinkForAi(User $merchant, array $payload, ?Request $request = null): PaymentLink
    {
        $amountMode = Arr::get($payload, 'amount_mode', 'fixed');
        $amount = (float) Arr::get($payload, 'amount', 0);

        // Current payment links require an amount. Keep a safe fallback for customer-defined mode.
        if ($amountMode === 'customer_defined' && $amount <= 0) {
            $amount = 1;
        }

        $paymentLink = new PaymentLink();
        $paymentLink->user_id = $merchant->id;
        $paymentLink->code = $this->generatePaymentLinkCode();
        $paymentLink->amount = $amount;
        $paymentLink->currency = strtoupper((string) Arr::get($payload, 'currency', 'USD'));
        $paymentLink->description = (string) Arr::get($payload, 'description', 'AI Integration payment link');
        $paymentLink->redirect_url = (string) Arr::get($payload, 'redirect_url', route('user.home'));
        $paymentLink->expires_at = null;

        if (Schema::hasColumn('payment_links', 'is_reusable')) {
            $paymentLink->is_reusable = true;
        }
        if (Schema::hasColumn('payment_links', 'link_type')) {
            $paymentLink->link_type = PaymentLink::TYPE_STANDARD;
        }

        $paymentLink->status = PaymentLink::STATUS_ACTIVE;
        $paymentLink->save();

        $integration = $this->forMerchant($merchant);
        $this->logEvent($integration, 'create_payment_link', 'success', 'Payment link created for AI integration', $request, [
            'payment_link_id' => $paymentLink->id,
            'amount_mode' => $amountMode,
            'amount' => $paymentLink->amount,
        ]);

        return $paymentLink;
    }

    public function savePluginSdkConfig(User $merchant, array $payload, ?Request $request = null): AiIntegration
    {
        $integration = $this->forMerchant($merchant);

        $websiteUrl = trim((string) Arr::get($payload, 'website_url', $merchant->website_url));
        $normalizedDomain = $this->pluginLicenseService->normalizeDomain($websiteUrl ?: ($merchant->website_domain ?? ''));

        if (!$normalizedDomain) {
            throw new \RuntimeException('Invalid website URL/domain');
        }

        $license = $this->pluginLicenseService->merchantCurrentLicense($merchant);

        if ($license && $license->normalized_domain && !hash_equals((string) $license->normalized_domain, (string) $normalizedDomain)) {
            $integration->selected_option = AiIntegration::OPTION_PLUGIN_SDK;
            $integration->merchant_email = (string) Arr::get($payload, 'merchant_email', $merchant->email);
            $integration->website_url = $websiteUrl;
            $integration->normalized_domain = $normalizedDomain;
            $integration->status = AiIntegration::STATUS_NEEDS_ATTENTION;
            $integration->last_configured_at = now();
            $integration->save();

            $this->logEvent($integration, 'configure_plugin_sdk', 'failed', 'Domain mismatch between license and requested domain', $request, [
                'license_domain' => $license->normalized_domain,
                'requested_domain' => $normalizedDomain,
            ]);

            throw new \RuntimeException('Domain mismatch. Plugin/SDK licenses must match the merchant domain.');
        }

        if (!$license) {
            $create = $this->pluginLicenseService->createLicense($merchant, [
                'email' => Arr::get($payload, 'merchant_email', $merchant->email),
                'domain' => $websiteUrl,
            ], $request);

            if (!($create['ok'] ?? false)) {
                throw new \RuntimeException($create['message'] ?? 'Unable to generate license key');
            }

            $license = $create['license'];
        }

        $integration->selected_option = AiIntegration::OPTION_PLUGIN_SDK;
        $integration->merchant_email = (string) Arr::get($payload, 'merchant_email', $merchant->email);
        $integration->website_url = $websiteUrl;
        $integration->normalized_domain = $normalizedDomain;
        $integration->license_key = $license->license_key;
        $integration->status = AiIntegration::STATUS_CONNECTED;
        $integration->setup_completed_at = now();
        $integration->last_configured_at = now();

        $optionPayload = (array) $integration->option_payload;
        $optionPayload['plugin_sdk'] = [
            'license_id' => $license->id,
            'license_status' => $license->status,
            'plugin' => 'flujipay-woocommerce',
        ];
        $integration->option_payload = $optionPayload;
        $integration->save();

        $this->logEvent($integration, 'configure_plugin_sdk', 'success', 'Plugin/SDK flow configured', $request, [
            'license_id' => $license->id,
            'normalized_domain' => $normalizedDomain,
        ]);

        return $integration;
    }

    public function ensurePluginLicense(User $merchant, ?Request $request = null): PluginLicense
    {
        $license = $this->pluginLicenseService->merchantCurrentLicense($merchant);
        if ($license) {
            return $license;
        }

        $domain = $merchant->website_domain ?: $merchant->website_url;
        if (!$domain) {
            throw new \RuntimeException('Please set your website URL in profile first');
        }

        $result = $this->pluginLicenseService->createLicense($merchant, [
            'email' => $merchant->email,
            'domain' => $domain,
        ], $request);

        if (!($result['ok'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'Unable to generate license key');
        }

        return $result['license'];
    }

    public function syncPaymentSourceByReference(string $reference, string $sourceType, ?AiIntegration $integration = null, ?string $providerReference = null): void
    {
        if ($reference === '') {
            return;
        }

        $deposit = Deposit::query()
            ->where('trx', $reference)
            ->orWhere('btc_wallet', $reference)
            ->latest('id')
            ->first();

        if (!$deposit) {
            Log::warning('AI integration payment source sync: deposit not found', [
                'reference' => $reference,
                'source_type' => $sourceType,
            ]);
            return;
        }

        $deposit->integration_source_type = $sourceType;
        if ($integration) {
            $deposit->ai_integration_id = $integration->id;
        }
        if ($providerReference) {
            $deposit->provider_reference = $providerReference;
        }
        $deposit->save();

        $this->logEvent($integration, 'sync_payment_source', 'success', 'Deposit source linked to AI integration', null, [
            'deposit_id' => $deposit->id,
            'trx' => $deposit->trx,
            'source_type' => $sourceType,
            'provider_reference' => $providerReference,
        ]);
    }

    public function buildPrompts(AiIntegration $integration): array
    {
        $merchant = $integration->merchant;
        $domain = $integration->normalized_domain ?: $integration->website_url ?: 'example.com';

        $apiPrompt = "Build a FlujiPay hosted checkout integration for {$merchant->email}. "
            . "Ask for PUBLIC_KEY and SECRET_KEY, plus SUCCESS_URL ({$integration->success_url}) and CANCEL_URL ({$integration->cancel_url}). "
            . "Use identifier prefix ai_api_ for each checkout request so FlujiPay can map transactions to AI Integration. "
            . "Create a secure backend endpoint that uses SECRET_KEY server-side only, creates checkout session, and returns checkout URL. "
            . "Frontend must never expose SECRET_KEY and should redirect users to FlujiPay checkout URL.";

        $paymentLinkPrompt = "Create a payment button for FlujiPay using this link: {$integration->payment_link_url}. "
            . "No backend required. Button text should be customizable. "
            . "Use direct redirect/open new tab to payment link and show success message after return.";

        $pluginPrompt = "Configure FlujiPay plugin/SDK activation with LICENSE_KEY={$integration->license_key}, "
            . "MERCHANT_EMAIL={$integration->merchant_email}, WEBSITE_URL={$integration->website_url}, DOMAIN={$domain}. "
            . "Use identifier prefix ai_sdk_ (or ai_plugin_) on checkout API calls for transaction attribution. "
            . "Implement domain-linked validation flow and deny activation when domain or email mismatch.";

        return [
            AiIntegration::OPTION_API_KEYS => $apiPrompt,
            AiIntegration::OPTION_PAYMENT_LINK => $paymentLinkPrompt,
            AiIntegration::OPTION_PLUGIN_SDK => $pluginPrompt,
        ];
    }

    public function buildSnippets(AiIntegration $integration): array
    {
        $paymentLinkUrl = $integration->payment_link_url ?: '#';

        $htmlButton = "<a href=\"{$paymentLinkUrl}\" target=\"_blank\" rel=\"noopener\" class=\"flujipay-button\">Pay with FlujiPay</a>";

        $frontendExample = "fetch('/api/flujipay/checkout', {\n"
            . "  method: 'POST',\n"
            . "  headers: { 'Content-Type': 'application/json' },\n"
            . "  body: JSON.stringify({ amount: 100, currency: 'USD' })\n"
            . "}).then(r => r.json()).then(data => window.location.href = data.checkout_url);";

        $backendExample = "// Node/Express pseudo-code\n"
            . "app.post('/api/flujipay/checkout', async (req, res) => {\n"
            . "  // use SECRET_KEY from server env only\n"
            . "  // send identifier like: ai_api_ORDER123\n"
            . "  // create FlujiPay checkout session\n"
            . "  // return checkout_url\n"
            . "});";

        return [
            'payment_link_button' => $htmlButton,
            'frontend_example' => $frontendExample,
            'backend_example' => $backendExample,
        ];
    }

    protected function generatePaymentLinkCode(): string
    {
        do {
            $code = Str::random(32);
        } while (PaymentLink::where('code', $code)->exists());

        return $code;
    }

    protected function logEvent(?AiIntegration $integration, string $action, string $result, string $message, ?Request $request = null, array $context = []): void
    {
        AiIntegrationEvent::create([
            'ai_integration_id' => $integration?->id,
            'merchant_id' => $integration?->merchant_id,
            'action' => $action,
            'result' => $result,
            'message' => $message,
            'integration_type' => $integration?->selected_option,
            'payment_reference' => Arr::get($context, 'payment_reference'),
            'provider_reference' => Arr::get($context, 'provider_reference'),
            'ip' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 255),
            'context' => $context,
        ]);
    }
}
