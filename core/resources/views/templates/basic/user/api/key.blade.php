@extends($activeTemplate.'layouts.master', ['setting'=>true])

@section('content')
<div class="row justify-content-center gy-4">
    <div class="col-12">
        <div class="page-heading mb-4">
            <h3 class="mb-2">{{ __($pageTitle) }}</h3>
            <p>
                @lang('Take control of your API access with our comprehensive API key page, providing both production and test mode keys with corresponding secrets. Manage your keys with ease and ensure secure access to your account.')
            </p>
        </div> 
        <hr>
    </div>
    <div class="col-12">
        <div class="text-end">
            <button 
                class="btn btn--base btn-sm mb-3 confirmationBtn"
                data-question="@lang('All API keys will be reset. Are you sure to generate new keys?')" 
                data-action="{{ route('user.generate.key') }}"
            >
                <i class="las la-key"></i> @lang('Generate API Keys')
            </button>
        </div>
        <div class="card custom--card api_key h-auto">
            <div class="card-header d-flex flex-wrap justify-content-between bg-white">
                <div class="card-title mb-0">
                    <h6>@lang('API Credentials')</h6>
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
        .copy-link {
            position: relative;
        }
        .copy-link input {
            width: 100%;
            padding: 5px;
            border: 1px solid #d7d7d7;
            border-radius: 4px;
            transition: all .3s;
            padding-right: 70px;
        }
        .copy-link span {
            text-align: center;
            position: absolute;
            top: 6px;
            right: 10px;
            cursor: pointer;
        }
        .form-check-input:focus{
            box-shadow: none;
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

