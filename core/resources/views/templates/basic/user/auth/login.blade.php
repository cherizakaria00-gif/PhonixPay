@extends($activeTemplate.'layouts.app')

@php
    $login = @getContent('login_register.content', true)->data_values;
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
        .new-auth .form--control {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--input-fg);
            padding: 12px 14px;
            border-radius: 12px;
            width: 100%;
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
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
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
            background-color: #6366f1;
            border-color: #6366f1;
        }
        .new-auth .anchor-color { color: #a5b4fc; }
        .new-auth .anchor-color:hover { color: #c7d2fe; }
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
            border-color: #6366f1;
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
                    Accedez a votre espace marchand
                </h1>
                <p class="text-slate-300 text-lg">
                    Suivez vos paiements, gerez vos integrations et lancez votre croissance en toute confiance.
                </p>
                <div class="mt-10">
                    <div class="relative bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-2xl">
                        <img src="{{ getImage('assets/images/frontend/login_register/' .@$login->image, '615x620') }}" alt="" class="rounded-xl w-full h-auto">
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md bg-slate-900/70 border border-slate-800 rounded-2xl p-8 shadow-2xl">
                <h2 class="text-2xl font-bold text-white mb-2">@lang('Login Your Account')</h2>
                <p class="text-slate-400 mb-6">Bienvenue ! Connectez-vous pour continuer.</p>

                <form method="POST" action="{{ route('user.login') }}" class="verify-gcaptcha space-y-5">
                    @csrf

                    @include($activeTemplate.'partials.social_login')

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Username or Email')</label>
                        <input type="text" name="username" value="{{ old('username') }}" class="form--control" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Password')</label>
                        <div class="input--group">
                            <input id="password" type="password" class="form--control" name="password" required>
                            <div class="password-show-hide fa-solid toggle-password fa-eye-slash" id="#password"></div>
                        </div>
                    </div>

                    <x-captcha />

                    <div class="flex items-center justify-between text-sm">
                        <label class="inline-flex items-center gap-2 text-slate-300">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            @lang('Remember Me')
                        </label>
                        <a href="{{ route('user.password.request') }}" class="anchor-color">@lang('Forgot Password')?</a>
                    </div>

                    <button type="submit" id="recaptcha" class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors">
                        @lang('Login')
                    </button>

                    <div class="text-center text-sm text-slate-400">
                        @lang("Don't have an account?")
                    </div>

                    <a href="{{ route('user.register') }}" class="w-full inline-flex items-center justify-center rounded-md border border-slate-700 px-4 py-3 text-sm font-semibold text-white hover:border-indigo-500 hover:text-indigo-200 transition-colors">
                        @lang('Become a Merchant')
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
