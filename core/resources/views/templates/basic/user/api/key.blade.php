@extends($activeTemplate.'layouts.master', ['setting'=>false])

@section('content')
<div class="row justify-content-center gy-4">
    <div class="col-12">
        <div class="pf-dev-header">
            <div>
                <h3 class="pf-dev-title mb-2">{{ __($pageTitle) }}</h3>
                <p class="pf-dev-subtitle mb-0">
                    @lang('Manage API credentials, plugin package, and WooCommerce license from one place.')
                </p>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card custom--card h-auto pf-dev-card">
            <div class="card-header p-0 bg-white">
                <ul class="nav nav-tabs pf-dev-main-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" href="{{ route('user.api.key') }}">@lang('Developer Toolkit')</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="{{ route('user.ai.integration.index') }}">@lang('AI Integration')</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card custom--card h-auto pf-dev-card">
            <div class="card-header bg-white pf-dev-card__header p-0">
                <ul class="nav nav-tabs pf-dev-tabs" id="developerTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-api" data-bs-toggle="tab" data-bs-target="#pane-api" type="button" role="tab" aria-controls="pane-api" aria-selected="true">
                            @lang('Api Key')
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-plugin" data-bs-toggle="tab" data-bs-target="#pane-plugin" type="button" role="tab" aria-controls="pane-plugin" aria-selected="false">
                            @lang('WooCommerce Plugin')
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-license" data-bs-toggle="tab" data-bs-target="#pane-license" type="button" role="tab" aria-controls="pane-license" aria-selected="false">
                            @lang('License Key')
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-api" role="tabpanel" aria-labelledby="tab-api" tabindex="0">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h6 class="mb-1">@lang('API Credentials')</h6>
                                <p class="pf-dev-card__desc mb-0">@lang('Use these keys to authenticate your API requests.')</p>
                            </div>
                            <div class="pf-dev-actions">
                                <button
                                    class="btn btn--base btn-sm confirmationBtn"
                                    data-question="@lang('All API keys will be reset. Are you sure to generate new keys?')"
                                    data-action="{{ route('user.generate.key') }}"
                                >
                                    <i class="las la-key"></i> @lang('Generate API Keys')
                                </button>
                            </div>
                        </div>

                        <div class="custom-switch mb-3">
                            <div class="form-check form-switch mt-xl-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="api_mode">
                                <label class="form-check-label mb-0" for="api_mode">@lang('Live Mode')</label>
                            </div>
                        </div>

                        <div class="test">
                            <div class="form-group">
                                <label>@lang('Test Public Key')</label>
                                <div class="copy-link">
                                    <input type="text" class="copyURL" id="testPublicKey" value="{{ $user->test_public_api_key }}" readonly="">
                                    <span class="copy" data-id="testPublicKey">
                                        <i class="las la-copy"></i> <strong class="copyText">@lang('Copy')</strong>
                                    </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>@lang('Test Secret Key')</label>
                                <div class="copy-link">
                                    <input type="text" class="copyURL" id="testSecretKey" value="{{ $user->test_secret_api_key }}" readonly="">
                                    <span class="copy" data-id="testSecretKey">
                                        <i class="las la-copy"></i> <strong class="copyText">@lang('Copy')</strong>
                                    </span>
                                </div>
                                <p class="pf-dev-warning mb-0">@lang('Keep your secret key safe. Do not share it in client-side code.')</p>
                            </div>
                        </div>
                        <div class="live d-none">
                            <div class="form-group">
                                <label>@lang('Public Key')</label>
                                <div class="copy-link">
                                    <input type="text" class="copyURL" id="publicKey" value="{{ $user->public_api_key }}" readonly="">
                                    <span class="copy" data-id="publicKey">
                                        <i class="las la-copy"></i> <strong class="copyText">@lang('Copy')</strong>
                                    </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>@lang('Secret Key')</label>
                                <div class="copy-link">
                                    <input type="text" class="copyURL" id="secretKey" value="{{ $user->secret_api_key }}" readonly="">
                                    <span class="copy" data-id="secretKey">
                                        <i class="las la-copy"></i> <strong class="copyText">@lang('Copy')</strong>
                                    </span>
                                </div>
                                <p class="pf-dev-warning mb-0">@lang('Keep your secret key safe. Do not share it in client-side code.')</p>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pane-plugin" role="tabpanel" aria-labelledby="tab-plugin" tabindex="0">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h6 class="mb-1">@lang('WooCommerce Plugin')</h6>
                                <p class="pf-dev-card__desc mb-0">@lang('Easily integrate FlujiPay into your WordPress store.')</p>
                            </div>
                            <div class="pf-dev-actions">
                                <a class="btn btn--base btn-sm" href="{{ asset('assets/files/Pluging.zip') }}?v=2.5.3" download="FlujiPay Plug V2.5.3.zip">
                                    <i class="las la-download"></i> @lang('FlujiPay Plugin v2.5.0')
                                </a>
                            </div>
                        </div>

                        <div class="pf-dev-plugin__content">
                            <h6 class="mb-2">@lang('FlujiPay for WooCommerce v2.5.0')</h6>
                            <ol class="pf-dev-plugin__list mb-0">
                                <li>@lang('Download the ZIP file.')</li>
                                <li>@lang('Go to WordPress Admin > Plugins > Add New > Upload.')</li>
                                <li>@lang('Activate and enter your API Keys.')</li>
                            </ol>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pane-license" role="tabpanel" aria-labelledby="tab-license" tabindex="0">
                        <div class="mb-3">
                            <h6 class="mb-1">@lang('License Key')</h6>
                            <p class="pf-dev-card__desc mb-0">@lang('Your license is generated automatically from your profile domain.')</p>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">@lang('Email')</label>
                                <input type="text" class="form-control" value="{{ $user->email }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('Allowed Domain')</label>
                                <input type="text" class="form-control" value="{{ $user->website_domain ?: $user->website_url }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('Current Key')</label>
                                <div class="copy-link">
                                    <input type="text" class="copyURL" id="merchantLicenseKey" value="{{ $currentLicense?->license_key ?: '' }}" readonly>
                                    <span class="copy" data-id="merchantLicenseKey">
                                        <i class="las la-copy"></i> <strong class="copyText">@lang('Copy')</strong>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>@lang('Key')</th>
                                    <th>@lang('Domain')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Last Validation')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($licenses as $license)
                                    <tr>
                                        <td>
                                            <div class="small fw-bold">{{ $license->license_key }}</div>
                                            <button type="button" class="btn btn-sm btn-outline--primary mt-1 copy-license-btn" data-license="{{ $license->license_key }}">
                                                @lang('Copy')
                                            </button>
                                        </td>
                                        <td>{{ $license->normalized_domain }}</td>
                                        <td>@php echo $license->statusBadge; @endphp</td>
                                        <td>
                                            @if($license->last_validated_at)
                                                {{ diffForHumans($license->last_validated_at) }}
                                            @else
                                                <span class="text-muted">@lang('Never')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($license->status !== \App\Models\PluginLicense::STATUS_REVOKED)
                                                <form method="post" action="{{ route('user.plugin.licenses.regenerate', $license->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline--warning w-100"
                                                            onclick="return confirm('@lang('Regenerate this license key? The current key will be revoked.')')">
                                                        @lang('Regenerate')
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted">@lang('Revoked')</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted py-4">@lang('No plugin licenses found')</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($licenses->hasPages())
                            <div class="pt-3">
                                @php echo paginateLinks($licenses) @endphp
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<x-user-confirmation-modal />
@endsection

@push('style')
    <style>
        .pf-dev-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .pf-dev-title {
            font-size: 22px;
            font-weight: 600;
            color: #0f172a;
        }

        .pf-dev-subtitle {
            font-size: 13px;
            color: #6b7280;
        }

        .pf-dev-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pf-dev-card {
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        }

        .pf-dev-card__header {
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 20px;
        }

        .pf-dev-main-tabs {
            border-bottom: 1px solid #edf2f7;
            padding: 0 22px;
            display: flex;
            gap: 16px;
            flex-wrap: nowrap;
            overflow-x: auto;
            margin-bottom: 0;
        }

        .pf-dev-main-tabs .nav-link {
            border: 0 !important;
            background: transparent !important;
            color: #6b7280 !important;
            font-size: 15px;
            font-weight: 600;
            padding: 16px 2px 14px;
            white-space: nowrap;
            border-bottom: 3px solid transparent !important;
            border-radius: 0;
        }

        .pf-dev-main-tabs .nav-link.active {
            color: #2d5bff !important;
            border-bottom-color: #2d5bff !important;
        }

        .pf-dev-tabs {
            border-bottom: 1px solid #edf2f7;
            padding: 0 22px;
            display: flex;
            gap: 16px;
            flex-wrap: nowrap;
            overflow-x: auto;
            margin-bottom: 0;
        }

        .pf-dev-tabs .nav-link {
            border: 0 !important;
            background: transparent !important;
            color: #6b7280 !important;
            font-size: 15px;
            font-weight: 600;
            padding: 16px 2px 14px;
            white-space: nowrap;
            border-bottom: 3px solid transparent !important;
            border-radius: 0;
        }

        .pf-dev-tabs .nav-link.active {
            color: #2d5bff !important;
            border-bottom-color: #2d5bff !important;
        }

        .pf-dev-card__desc {
            font-size: 12px;
            color: #6b7280;
        }

        .copy-link {
            position: relative;
        }
        .copy-link input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            transition: all .2s ease;
            padding-right: 90px;
            font-size: 13px;
        }
        .copy-link span {
            text-align: center;
            position: absolute;
            top: 6px;
            right: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            color: #475569;
        }
        .form-check-input:focus{
            box-shadow: none;
        }

        .pf-dev-warning {
            margin-top: 6px;
            font-size: 12px;
            color: #ef4444;
        }

        .pf-dev-plugin__content {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .pf-dev-plugin__list {
            padding-left: 18px;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 767px) {
            .pf-dev-card__header {
                padding: 14px 16px;
            }

            .pf-dev-tabs {
                padding: 0 12px;
                gap: 12px;
            }

            .pf-dev-main-tabs {
                padding: 0 12px;
                gap: 12px;
            }

            .copy-link span {
                position: static;
                margin-top: 8px;
                width: fit-content;
            }

            .copy-link {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            $('#api_mode').on('click', function(){

                if($(this).prop('checked')){
                    $('.test').addClass('d-none');
                    return $('.live').removeClass('d-none');
                }

                $('.test').removeClass('d-none');
                $('.live').addClass('d-none');
            });

            function copy(getId, textElement){

                var copyText = document.getElementById(getId);
                copyText.select();
                copyText.setSelectionRange(0, 99999);

                document.execCommand("copy");
                textElement.text('Copied');

                setTimeout(() => {
                    textElement.text('Copy');
                }, 2000);
            }

            $('.copy').on('click', function() {
                copy($(this).data('id'), $(this).find('.copyText'));
            });

            document.querySelectorAll('.copy-license-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    const value = this.getAttribute('data-license') || '';
                    if (!value) {
                        return;
                    }

                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(value);
                        return;
                    }

                    const temp = document.createElement('input');
                    temp.value = value;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    temp.remove();
                });
            });

        })(jQuery);
    </script>
@endpush
