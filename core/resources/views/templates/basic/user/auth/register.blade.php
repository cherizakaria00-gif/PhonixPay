@extends($activeTemplate.'layouts.app')

@php
    $policyPages = getContent('policy_pages.element', orderById:true);
    $register = @getContent('login_register.content', true)->data_values; 
@endphp

@push('style-lib')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@endpush

@push('header-script-lib')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@push('style')
    <style>
        html { scroll-behavior: smooth; }
        .preloader,
        .body-overlay,
        .sidebar-overlay,
        .scroll-top { display: none !important; }

        .new-auth {
            font-family: 'Inter', sans-serif;
            --input-bg: #0b1220;
            --input-fg: #ffffff;
            --input-border: #1f2a44;
            --input-placeholder: #94a3b8;
        }
        .new-auth.light-inputs {
            --input-bg: #ffffff;
            --input-fg: #0b1220;
            --input-border: #e2e8f0;
            --input-placeholder: #64748b;
        }
        .new-auth .form-disabled { position: relative; }
        .new-auth .form-disabled::after {
            content: "";
            position: absolute;
            inset: 0;
            background-color: rgba(2, 6, 23, 0.45);
            backdrop-filter: blur(6px);
            border-radius: 16px;
            z-index: 10;
        }
        .new-auth .form-disabled-text {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 11;
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            color: #f8fafc;
        }
        .new-auth .form--control {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--input-fg);
            padding: 12px 14px;
            border-radius: 12px;
            width: 100%;
            box-shadow:
                0 0 0 1px rgba(136, 198, 166, 0.15),
                0 0 18px rgba(136, 198, 166, 0.12);
        }
        .new-auth .form--control::placeholder {
            color: var(--input-placeholder);
        }
        .new-auth .form--control:-webkit-autofill,
        .new-auth .form--control:-webkit-autofill:hover,
        .new-auth .form--control:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--input-fg);
            transition: background-color 9999s ease-in-out 0s;
            box-shadow: 0 0 0 1000px var(--input-bg) inset;
        }
        .new-auth .form--control:focus {
            border-color: #88c6a6;
            box-shadow:
                0 0 0 2px rgba(136, 198, 166, 0.35),
                0 0 24px rgba(136, 198, 166, 0.35);
            outline: none;
        }
        .new-auth .input--group { position: relative; }
        .new-auth .input--group .form--control { padding-right: 44px; }
        .new-auth .password-show-hide {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
        }
        .new-auth .form-check-input {
            background-color: #0b1220;
            border: 1px solid #334155;
        }
        .new-auth .form-check-input:checked {
            background-color: #87c5a6;
            border-color: #87c5a6;
        }
        .new-auth .anchor-color { color: #a7d9c2; }
        .new-auth .anchor-color:hover { color: #cfe9dc; }
        .new-auth .social-login-btn{
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: #ffffff;
            padding: 12px 16px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }
        .new-auth .btn.social-login-btn:hover {
            border-color: #87c5a6;
            color: #ffffff;
        }
        .new-auth .login-or span{
            background-color: #0b1220;
            color: #94a3b8;
        }
        .new-auth .login-or:before{
            background-color: rgba(148, 163, 184, 0.2);
        }
    </style>
@endpush

@section('app')
<div class="new-auth min-h-screen bg-slate-950 text-white relative overflow-hidden">
    <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
        <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%);"></div>
    </div>

    <a href="{{ route('home') }}" class="absolute top-6 right-6 z-20 h-11 w-11 rounded-full bg-white/10 hover:bg-white/20 border border-white/10 flex items-center justify-center">
        <i class="fas fa-times"></i>
    </a>

    <div class="relative z-10 min-h-screen flex flex-col lg:flex-row">
        <div class="hidden lg:flex lg:w-1/2 items-center justify-center px-12 py-12">
            <div class="max-w-md">
                <div class="flex items-center gap-3 mb-6">
                    <img src="{{ siteLogo() }}" alt="@lang('Logo')" class="h-10">
                </div>
                <h1 class="text-4xl font-bold tracking-tight text-white mb-4">
                    Become a merchant in minutes
                </h1>
                <p class="text-slate-300 text-lg">
                    Create your account, complete KYC, and start getting paid faster.
                </p>
                <div class="mt-10">
                    <div class="relative bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-2xl">
                        <img src="{{ getImage('assets/images/frontend/login_register/' .@$register->image, '615x620') }}" alt="" class="rounded-xl w-full h-auto">
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-xl bg-slate-900/70 border border-slate-800 rounded-2xl p-8 shadow-2xl">
                <h2 class="text-2xl font-bold text-white mb-2">@lang('Become a Merchant')</h2>
                <p class="text-slate-400 mb-6">Create your account to get started.</p>

                <form action="{{ route('user.register') }}" method="POST" class="verify-gcaptcha @if (!gs('registration')) form-disabled @endif space-y-5">
                    @csrf
                    <input type="hidden" name="referral_code" value="{{ old('referral_code', $referralCode ?? session('reward_referral_code')) }}">

                    @if(old('referral_code', $referralCode ?? session('reward_referral_code')))
                        <div class="rounded-xl border border-emerald-300/40 bg-emerald-500/10 p-3 text-xs text-emerald-100">
                            @lang('Referral code applied:') <strong>{{ old('referral_code', $referralCode ?? session('reward_referral_code')) }}</strong>
                        </div>
                    @endif

                    @include($activeTemplate.'partials.social_login')

                    @if (!gs('registration'))
                        <p class="form-disabled-text">
                            @lang('Registration is currently disabled')
                        </p>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('First Name')</label>
                            <input type="text" class="form--control" name="firstname" value="{{ old('firstname') }}" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Last Name')</label>
                            <input type="text" class="form--control" name="lastname" value="{{ old('lastname') }}" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Email address')</label>
                            <input type="email" class="form--control checkUser" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Password')</label>
                            <div class="input--group">
                                <input type="password" class="form--control @if (gs('secure_password')) secure-password @endif" name="password" id="password" required>
                                <div class="password-show-hide fa-solid toggle-password fa-eye-slash" id="#password"></div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Confirm Password')</label>
                            <div class="input--group">
                                <input type="password" class="form--control" name="password_confirmation" id="password_confirmation" required>
                                <div class="password-show-hide fa-solid toggle-password fa-eye-slash" id="#password_confirmation"></div>
                            </div>
                        </div>
                    </div>

                    <x-captcha />

                    @if(gs('agree'))
                        <div class="text-xs text-slate-300 leading-relaxed">
                            <label class="inline-flex items-start gap-2">
                                <input type="checkbox" id="agree" @checked(old('agree')) name="agree" required>
                                <span>
                                    @lang('I agree with')
                                    @foreach($policyPages as $policy)
                                        <a href="{{ route('policy.pages',[slug($policy->data_values->title)]) }}" class="anchor-color" target="_blank">{{ __($policy->data_values->title) }}</a>@if(!$loop->last), @endif
                                    @endforeach
                                </span>
                            </label>
                        </div>
                    @endif

                    <button type="submit" class="w-full rounded-md bg-[#87c5a6] px-4 py-3 text-sm font-semibold text-white hover:bg-[#9ad8bf] transition-colors">
                        @lang('Register')
                    </button>

                    <div class="text-center text-sm text-slate-400">@lang("Already have an account?")</div>

                    <a href="{{ route('user.login') }}" class="w-full inline-flex items-center justify-center rounded-md border border-slate-700 px-4 py-3 text-sm font-semibold text-white hover:border-[#87c5a6] hover:text-[#cfe9dc] transition-colors">
                        @lang('Login')
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

<div class="modal fade" id="existModalCenter" tabindex="-1" role="dialog" aria-labelledby="existModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="existModalLongTitle">@lang('You are with us')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="text-center mb-0">@lang('You already have an account please Login ')</h6>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--dark btn--sm" data-bs-dismiss="modal">@lang('Close')</button>
                <a href="{{ route('user.login') }}" class="btn btn--sm btn--base btn-sm">@lang('Login')</a>
            </div>
        </div>
    </div>
</div>

@if (gs('registration'))
    @if (gs('secure_password'))
        @push('script-lib')
            <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
        @endpush
    @endif

    @push('script')
        <script>
            "use strict";
            (function($) {

                $('.checkUser').on('focusout', function(e) {
                    var url = '{{ route('user.checkUser') }}';
                    var value = $(this).val();
                    var token = '{{ csrf_token() }}';

                    var data = {
                        email: value,
                        _token: token
                    }

                    $.post(url, data, function(response) {
                        if (response.data != false) {
                            $('#existModalCenter').modal('show');
                        }
                    });
                });
            })(jQuery);
        </script>
    @endpush
@endif
