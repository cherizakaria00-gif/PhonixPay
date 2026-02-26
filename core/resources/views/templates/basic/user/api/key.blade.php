@extends($activeTemplate.'layouts.master', ['setting'=>false])

@section('content')
<div class="row justify-content-center gy-4">
    <div class="col-12">
        <div class="pf-dev-header">
            <div>
                <h3 class="pf-dev-title mb-2">{{ __($pageTitle) }}</h3>
                <p class="pf-dev-subtitle mb-0">
                    @lang('Take control of your API access with our comprehensive API key page, providing both production and test mode keys with corresponding secrets. Manage your keys with ease and ensure secure access to your account.')
                </p>
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
    </div>
    <div class="col-12">
        <div class="card custom--card api_key h-auto pf-dev-card">
            <div class="card-header d-flex flex-wrap justify-content-between bg-white pf-dev-card__header">
                <div class="card-title mb-0">
                    <h6 class="mb-1">@lang('API Credentials')</h6>
                    <p class="pf-dev-card__desc mb-0">@lang('Use these keys to authenticate your API requests.')</p>
                </div>
                <div class="custom-switch">
                    <div class="form-check form-switch mt-xl-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="api_mode">
                        <label class="form-check-label mb-0" for="api_mode">@lang('Live Mode')</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
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
        </div>
    </div>
    <div class="col-12">
        <div class="card custom--card h-auto pf-dev-card pf-dev-plugin">
            <div class="card-header d-flex flex-wrap justify-content-between bg-white pf-dev-card__header">
                <div class="card-title mb-0">
                    <h6 class="mb-1">@lang('WooCommerce Plugin')</h6>
                    <p class="pf-dev-card__desc mb-0">@lang('Easily integrate FlujiPay into your WordPress store.')</p>
                </div>
                <div class="pf-dev-actions">
                    <a class="btn btn--base btn-sm" href="{{ asset('assets/files/Pluging.zip') }}" download="FlujiPay Plug V2.1.zip">
                        <i class="las la-download"></i> @lang('FlujiPay Plugin v2.1')
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="pf-dev-plugin__content">
                    <h6 class="mb-2">@lang('FlujiPay for WooCommerce v2.1.0')</h6>
                    <ol class="pf-dev-plugin__list mb-0">
                        <li>@lang('Download the ZIP file.')</li>
                        <li>@lang('Go to WordPress Admin > Plugins > Add New > Upload.')</li>
                        <li>@lang('Activate and enter your API Keys.')</li>
                    </ol>
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

        })(jQuery);
    </script>
@endpush
