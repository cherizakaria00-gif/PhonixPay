@extends($activeTemplate.'layouts.master', ['setting'=>true])

@section('content')
<div class="row justify-content-center gy-4">

    <div class="col-12">
        <div class="page-heading mb-4">
            <h3 class="mb-2">{{ __($pageTitle) }}</h3>
            <p>
                @lang('Add an extra layer of security to your account with our Google Authenticator page, allowing you to enable and manage two-factor authentication with ease. Protect your account and ensure that only you can access it, even in the event of a password breach.')
            </p>
        </div>
        <hr>
    </div>

    @if(!$user->ts)
        <div class="col-md-6">
            <div class="card custom--card style--two">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-center">
                    <h5 class="card-title">@lang('Add Your Account')</h5>
                </div>

                <div class="card-body">
                    <h6 class="mb-3">
                        @lang('Use the QR code or setup key on your Google Authenticator app to add your account. ')
                    </h6>

                    <div class="form-group mx-auto text-center">
                        <img class="mx-auto" src="{{$qrCodeUrl}}">
                    </div>

                    <div class="form-group">
                        <label>@lang('Setup Key')</label>
                        <div class="copy-link">
                            <input type="text" class="copyURL" name="key" id="key" value="{{$secret}}" readonly="">
                            <span class="copy" data-id="key">
                                <i class="las la-copy"></i> <strong class="copyText">@lang('Copy')</strong>
                            </span>
                        </div>
                    </div>

                    <label><i class="fa fa-info-circle"></i> @lang('Help')</label>
                    <p>@lang('Google Authenticator is a multifactor app for mobile devices. It generates timed codes used during the 2-step verification process. To use Google Authenticator, install the Google Authenticator application on your mobile device.') <a class="text--base" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en" target="_blank">@lang('Download')</a></p>
                </div>
            </div>
        </div>
    @endif

    <div class="col-md-6">
        @if($user->ts)
            <div class="card style--two">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-center">
                    <h5 class="card-title">@lang('Disable 2FA Security')</h5>
                </div>
                <form action="{{route('user.twofactor.disable')}}" method="POST">
                    <div class="card-body">
                        @csrf
                        <input type="hidden" name="key" value="{{$secret}}">
                        <div class="form-group">
                            <label class="form-label">@lang('Google Authenticatior OTP')</label>
                            <input type="text" class="form-control form--control" name="code" required>
                        </div>
                        <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        @else
            <div class="card style--two">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-center">
                    <h5 class="card-title">@lang('Enable 2FA Security')</h5>
                </div>
                <form action="{{ route('user.twofactor.enable') }}" method="POST">
                    <div class="card-body">
                        @csrf
                        <input type="hidden" name="key" value="{{$secret}}">
                        <div class="form-group">
                            <label class="form-label">@lang('Google Authenticatior OTP')</label>
                            <input type="text" class="form-control form--control" name="code" required>
                        </div>
                        <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
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
        (function($){
            "use strict";

            $('.copy').on('click', function() {

                var copyText = document.getElementById($(this).data('id'));
                copyText.select();
                copyText.setSelectionRange(0, 99999);

                document.execCommand("copy");
                $(this).find('.copyText').text('Copied');

                setTimeout(() => {
                    $(this).find('.copyText').text('Copy');
                }, 2000);
            });

        })(jQuery);
    </script>
@endpush
