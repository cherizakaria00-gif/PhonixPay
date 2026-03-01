@extends($activeTemplate.'layouts.master')

@php
    $showHeaderBalance = true;
@endphp

@section('settings_panel_content')
    <div class="profile-modern-section-head">
        <h5>@lang('Change Password')</h5>
        <p>
            @lang('Protect your account from unauthorized access and enhance your security by changing your password.')
        </p>
    </div>

    <form class="register profile-modern-form" action="" method="post">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="col-form-label">@lang('Current Password')</label>
                    <input type="password" class="form--control" name="current_password" required autocomplete="current-password">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label class="col-form-label">@lang('Password')</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form--control @if(gs('secure_password')) secure-password @endif"
                            name="password"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label class="col-form-label">@lang('Confirm Password')</label>
                    <input type="password" class="form--control" name="password_confirmation" required autocomplete="current-password">
                </div>
            </div>
            <div class="col-md-12 mt-2">
                <div class="form-group">
                    <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('content')
    @include($activeTemplate.'partials.profile_settings_shell')
@endsection

@if(gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif
