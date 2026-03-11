@extends($activeTemplate.'layouts.master', ['setting'=>false])

@section('content')
<div class="row justify-content-center gy-4">
    <div class="col-12">
        <div class="pf-ai-header">
            <div>
                <h3 class="pf-ai-title mb-2">@lang('AI Integration')</h3>
                <p class="pf-ai-subtitle mb-0">
                    @lang('Connect FlujiPay to Lovable, v0, Bolt, Replit, Framer, Webflow, and custom AI-generated apps.')
                </p>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card custom--card pf-ai-tabs-wrap">
            <div class="card-header p-0 bg-white">
                <ul class="nav nav-tabs pf-ai-dev-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="{{ route('user.api.key') }}">@lang('Developer Toolkit')</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" href="{{ route('user.ai.integration.index') }}">@lang('AI Integration')</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="pf-ai-status-row">
                    <span class="pf-ai-label">@lang('Connection Status')</span>
                    <span>@php echo $integration->statusBadge; @endphp</span>
                </div>

                <div class="pf-ai-grid mt-4">
                    <form method="post" action="{{ route('user.ai.integration.select.option') }}" class="pf-ai-choice-card {{ $integration->selected_option === \App\Models\AiIntegration::OPTION_API_KEYS ? 'active' : '' }}">
                        @csrf
                        <input type="hidden" name="selected_option" value="{{ \App\Models\AiIntegration::OPTION_API_KEYS }}">
                        <h5>@lang('Option A — API Keys')</h5>
                        <p>@lang('Best for custom websites and full control.')</p>
                        <small>@lang('Hosted checkout + backend endpoint + secure key usage.')</small>
                        <button type="submit" class="btn btn-sm btn-outline--primary mt-3">@lang('Select')</button>
                    </form>

                    <form method="post" action="{{ route('user.ai.integration.select.option') }}" class="pf-ai-choice-card {{ $integration->selected_option === \App\Models\AiIntegration::OPTION_PAYMENT_LINK ? 'active' : '' }}">
                        @csrf
                        <input type="hidden" name="selected_option" value="{{ \App\Models\AiIntegration::OPTION_PAYMENT_LINK }}">
                        <h5>@lang('Option B — Payment Link')</h5>
                        <p>@lang('Easiest and fastest setup.')</p>
                        <small>@lang('No backend required. Direct link/button integration.')</small>
                        <button type="submit" class="btn btn-sm btn-outline--primary mt-3">@lang('Select')</button>
                    </form>

                    <form method="post" action="{{ route('user.ai.integration.select.option') }}" class="pf-ai-choice-card {{ $integration->selected_option === \App\Models\AiIntegration::OPTION_PLUGIN_SDK ? 'active' : '' }}">
                        @csrf
                        <input type="hidden" name="selected_option" value="{{ \App\Models\AiIntegration::OPTION_PLUGIN_SDK }}">
                        <h5>@lang('Option C — Plugin / SDK')</h5>
                        <p>@lang('Best for plugin or SDK-based integrations.')</p>
                        <small>@lang('WooCommerce plugin, JS SDK, reusable activation flow.')</small>
                        <button type="submit" class="btn btn-sm btn-outline--primary mt-3">@lang('Select')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card custom--card pf-ai-wizard-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">@lang('Guided Setup Wizard')</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning mb-4">
                    <strong>@lang('Security warning:')</strong>
                    @lang('Never expose secret keys in frontend code. Always create checkout sessions from your backend.')
                </div>

                <div id="wizard-option-api" class="wizard-option {{ $integration->selected_option === \App\Models\AiIntegration::OPTION_API_KEYS ? '' : 'd-none' }}">
                    <h6 class="mb-3">@lang('Option A — API Keys Flow')</h6>
                    <form method="post" action="{{ route('user.ai.integration.save.api.keys') }}" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">@lang('Public Key')</label>
                            <input type="text" class="form-control" value="{{ auth()->user()->public_api_key }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">@lang('Secret Key')</label>
                            <input type="password" class="form-control" value="{{ auth()->user()->secret_api_key }}" readonly>
                            <small class="text-danger">@lang('Use secret key only in backend/server code')</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">@lang('Success URL')</label>
                            <input type="url" name="success_url" class="form-control" value="{{ old('success_url', $integration->success_url) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">@lang('Cancel URL')</label>
                            <input type="url" name="cancel_url" class="form-control" value="{{ old('cancel_url', $integration->cancel_url) }}" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn--base">@lang('Save API Integration')</button>
                        </div>
                    </form>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <h6>@lang('Backend Snippet')</h6>
                            <pre class="pf-ai-code"><code>{{ $snippets['backend_example'] ?? '' }}</code></pre>
                        </div>
                        <div class="col-md-6">
                            <h6>@lang('Frontend Snippet')</h6>
                            <pre class="pf-ai-code"><code>{{ $snippets['frontend_example'] ?? '' }}</code></pre>
                        </div>
                        <div class="col-12">
                            <h6>@lang('AI Builder Prompt')</h6>
                            <div class="pf-ai-prompt-wrap">
                                <textarea class="form-control pf-ai-prompt" readonly id="prompt-api">{{ $prompts[\App\Models\AiIntegration::OPTION_API_KEYS] ?? '' }}</textarea>
                                <button type="button" class="btn btn-sm btn-outline--primary copy-btn" data-copy-target="prompt-api">@lang('Copy Prompt')</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="wizard-option-link" class="wizard-option {{ $integration->selected_option === \App\Models\AiIntegration::OPTION_PAYMENT_LINK ? '' : 'd-none' }}">
                    <h6 class="mb-3">@lang('Option B — Payment Link Flow')</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <form method="post" action="{{ route('user.ai.integration.select.payment.link') }}" class="row g-2">
                                @csrf
                                <input type="hidden" name="amount_mode" value="fixed">
                                <div class="col-12">
                                    <label class="form-label">@lang('Use Existing Payment Link')</label>
                                    <select class="form-select" name="payment_link_id" required>
                                        <option value="">@lang('Select Link')</option>
                                        @foreach($paymentLinks as $link)
                                            <option value="{{ $link->id }}" @selected((int) $integration->payment_link_id === (int) $link->id)>
                                                #{{ $link->id }} - {{ $link->description }} ({{ showAmount($link->amount) }} {{ $link->currency }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-outline--primary">@lang('Connect Selected Link')</button>
                                </div>
                            </form>
                        </div>

                        <div class="col-md-6">
                            <form method="post" action="{{ route('user.ai.integration.create.payment.link') }}" class="row g-2">
                                @csrf
                                <div class="col-12">
                                    <label class="form-label">@lang('Create New Link')</label>
                                    <input type="text" class="form-control" name="name" placeholder="@lang('Link name')" required>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" name="amount_mode" required>
                                        <option value="fixed">@lang('Fixed amount')</option>
                                        <option value="customer_defined">@lang('Customer-defined')</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="number" step="0.01" class="form-control" name="amount" placeholder="@lang('Amount')">
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" name="currency" required>
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency }}">{{ $currency }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="button_label" placeholder="@lang('Button label')" value="Pay now">
                                </div>
                                <div class="col-12">
                                    <input type="text" class="form-control" name="description" placeholder="@lang('Description')">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn--base">@lang('Create & Connect')</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6>@lang('Payment Link URL')</h6>
                            <div class="pf-ai-prompt-wrap">
                                <input type="text" class="form-control" id="payment-link-url" value="{{ $integration->payment_link_url }}" readonly>
                                <button type="button" class="btn btn-sm btn-outline--primary copy-btn" data-copy-target="payment-link-url">@lang('Copy')</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>@lang('HTML Button Snippet')</h6>
                            <pre class="pf-ai-code"><code id="snippet-link-button">{{ $snippets['payment_link_button'] ?? '' }}</code></pre>
                        </div>
                        <div class="col-12">
                            <h6>@lang('AI Builder Prompt')</h6>
                            <div class="pf-ai-prompt-wrap">
                                <textarea class="form-control pf-ai-prompt" readonly id="prompt-link">{{ $prompts[\App\Models\AiIntegration::OPTION_PAYMENT_LINK] ?? '' }}</textarea>
                                <button type="button" class="btn btn-sm btn-outline--primary copy-btn" data-copy-target="prompt-link">@lang('Copy Prompt')</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="wizard-option-plugin" class="wizard-option {{ $integration->selected_option === \App\Models\AiIntegration::OPTION_PLUGIN_SDK ? '' : 'd-none' }}">
                    <h6 class="mb-3">@lang('Option C — Plugin / SDK Flow')</h6>

                    <form method="post" action="{{ route('user.ai.integration.save.plugin.sdk') }}" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">@lang('Merchant Email')</label>
                            <input type="email" class="form-control" name="merchant_email" value="{{ old('merchant_email', $integration->merchant_email ?: auth()->user()->email) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('Website URL / Domain')</label>
                            <input type="text" class="form-control" name="website_url" value="{{ old('website_url', $integration->website_url ?: auth()->user()->website_url ?: auth()->user()->website_domain) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('License Key')</label>
                            <input type="text" class="form-control" value="{{ $integration->license_key }}" readonly>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn--base">@lang('Save Plugin / SDK Setup')</button>
                        </div>
                    </form>

                    <form method="post" action="{{ route('user.ai.integration.generate.license') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-outline--warning">@lang('Generate License Key')</button>
                    </form>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <div class="pf-ai-help-card">
                                <h6>@lang('Plugin Activation Instructions')</h6>
                                <ul>
                                    <li>@lang('Install FlujiPay WooCommerce plugin in WordPress')</li>
                                    <li>@lang('Enter merchant email + website URL + license key')</li>
                                    <li>@lang('Activation checks domain-linked license validity')</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="pf-ai-help-card">
                                <h6>@lang('SDK Instructions')</h6>
                                <ul>
                                    <li>@lang('Use API credentials server-side for secure session creation')</li>
                                    <li>@lang('Store license data in integration env config')</li>
                                    <li>@lang('Reject activation when email/domain mismatch')</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-12">
                            <h6>@lang('AI Builder Prompt')</h6>
                            <div class="pf-ai-prompt-wrap">
                                <textarea class="form-control pf-ai-prompt" readonly id="prompt-plugin">{{ $prompts[\App\Models\AiIntegration::OPTION_PLUGIN_SDK] ?? '' }}</textarea>
                                <button type="button" class="btn btn-sm btn-outline--primary copy-btn" data-copy-target="prompt-plugin">@lang('Copy Prompt')</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 border rounded bg-light">
                    <h6 class="mb-2">@lang('Setup Summary')</h6>
                    <p class="mb-1"><strong>@lang('Selected option:')</strong> {{ $integration->selected_option ?: __('N/A') }}</p>
                    <p class="mb-1"><strong>@lang('Status:')</strong> {{ ucfirst(str_replace('_', ' ', $integration->status)) }}</p>
                    <p class="mb-0"><strong>@lang('Completed at:')</strong> {{ $integration->setup_completed_at ? showDateTime($integration->setup_completed_at) : __('Not completed') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .pf-ai-title { font-size: 24px; font-weight: 700; color: #0f172a; }
    .pf-ai-subtitle { color: #64748b; font-size: 13px; }
    .pf-ai-tabs-wrap, .pf-ai-wizard-card { border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 12px 28px rgba(15, 23, 42, .06); }
    .pf-ai-dev-tabs { border-bottom: 1px solid #edf2f7; padding: 0 16px; gap: 12px; }
    .pf-ai-dev-tabs .nav-link { border: 0 !important; color: #6b7280 !important; font-weight: 600; padding: 14px 2px; border-bottom: 3px solid transparent !important; }
    .pf-ai-dev-tabs .nav-link.active { color: #2d5bff !important; border-bottom-color: #2d5bff !important; }
    .pf-ai-status-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .pf-ai-label { font-weight: 600; color: #334155; }
    .pf-ai-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    .pf-ai-choice-card { border: 1px solid #dbe5f1; border-radius: 12px; padding: 16px; background: #fff; text-align: left; }
    .pf-ai-choice-card.active { border-color: #2d5bff; box-shadow: 0 0 0 3px rgba(45, 91, 255, .12); }
    .pf-ai-choice-card h5 { font-size: 16px; margin-bottom: 6px; }
    .pf-ai-choice-card p { margin-bottom: 4px; color: #334155; }
    .pf-ai-choice-card small { color: #64748b; }
    .pf-ai-code { background: #0f172a; color: #e2e8f0; border-radius: 10px; padding: 14px; min-height: 145px; white-space: pre-wrap; }
    .pf-ai-prompt-wrap { display: flex; gap: 10px; align-items: flex-start; }
    .pf-ai-prompt { min-height: 110px; font-size: 13px; }
    .pf-ai-help-card { border: 1px solid #dbe5f1; border-radius: 12px; padding: 14px; background: #fff; height: 100%; }
    .pf-ai-help-card ul { margin: 0; padding-left: 18px; color: #475569; }
    .wizard-option { border-top: 1px dashed #dbe5f1; padding-top: 14px; }
    @media (max-width: 991px) { .pf-ai-grid { grid-template-columns: 1fr; } .pf-ai-prompt-wrap { flex-direction: column; } }
</style>
@endpush

@push('script')
<script>
(function () {
    'use strict';

    document.querySelectorAll('.copy-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-copy-target');
            const target = document.getElementById(targetId);
            if (!target) return;

            const value = target.value || target.textContent || '';
            if (!value) return;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(value);
            } else {
                const temp = document.createElement('textarea');
                temp.value = value;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                temp.remove();
            }
        });
    });
})();
</script>
@endpush
