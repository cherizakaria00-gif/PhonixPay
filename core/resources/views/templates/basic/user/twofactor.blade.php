@extends($activeTemplate.'layouts.master')

@php
    $showHeaderBalance = true;
@endphp

@section('settings_panel_content')
    <div class="profile-modern-section-head">
        <h5>@lang('2FA Security')</h5>
        <p>
            @lang('Add an extra layer of security to your account with Google Authenticator. Enable or disable two-factor authentication here.')
        </p>
    </div>

    <div class="row g-4">
        @if(!$user->ts)
            <div class="col-md-6">
                <div class="card custom--card style--two h-100">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-center">
                        <h5 class="card-title">@lang('Add Your Account')</h5>
                    </div>

                    <div class="card-body">
                        <h6 class="mb-3">
                            @lang('Use the QR code or setup key on your Google Authenticator app to add your account.')
                        </h6>

                        <div class="form-group mx-auto text-center">
                            <img class="mx-auto" src="{{ $qrCodeUrl }}" alt="@lang('QR Code')">
                        </div>

                        <div class="form-group">
                            <label>@lang('Setup Key')</label>
                            <div class="copy-link">
                                <input type="text" class="copyURL" name="key" id="key" value="{{ $secret }}" readonly>
                                <span class="copy" data-id="key">
                                    <i class="las la-copy"></i> <strong class="copyText">@lang('Copy')</strong>
                                </span>
                            </div>
                        </div>

                        <label><i class="fa fa-info-circle"></i> @lang('Help')</label>
                        <p>
                            @lang('Google Authenticator is a multifactor app for mobile devices. It generates timed codes used during the 2-step verification process.')
                            <a class="text--base" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en" target="_blank">@lang('Download')</a>
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-md-6">
            @if($user->ts)
                <div class="card style--two h-100">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-center">
                        <h5 class="card-title">@lang('Disable 2FA Security')</h5>
                    </div>
                    <form action="{{ route('user.twofactor.disable') }}" method="POST">
                        <div class="card-body">
                            @csrf
                            <input type="hidden" name="key" value="{{ $secret }}">
                            <div class="form-group">
                                <label class="form-label">@lang('Google Authenticatior OTP')</label>
                                <input type="text" class="form-control form--control" name="code" required>
                            </div>
                            <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                        </div>
                    </form>
                </div>
            @else
                <div class="card style--two h-100">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-center">
                        <h5 class="card-title">@lang('Enable 2FA Security')</h5>
                    </div>
                    <form action="{{ route('user.twofactor.enable') }}" method="POST">
                        <div class="card-body">
                            @csrf
                            <input type="hidden" name="key" value="{{ $secret }}">
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

@section('content')
    @include($activeTemplate.'partials.profile_settings_shell')
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
            border-radius: 10px;
            transition: all .3s;
            padding-right: 80px;
            height: 50px;
            background: #f8fafc;
        }

        .copy-link span {
            text-align: center;
            position: absolute;
            top: 14px;
            right: 14px;
            cursor: pointer;
        }

        .form-check-input:focus {
            box-shadow: none;
        }
    </style>
@endpush

@push('script')
    <script>
        (function($){
            "use strict";

            $('.copy').on('click', function() {
                const copyText = document.getElementById($(this).data('id'));
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
