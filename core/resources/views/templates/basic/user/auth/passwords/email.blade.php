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
        .new-auth .form--control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
            outline: none;
        }
        .new-auth .form--control:-webkit-autofill,
        .new-auth .form--control:-webkit-autofill:hover,
        .new-auth .form--control:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--input-fg);
            transition: background-color 9999s ease-in-out 0s;
            box-shadow: 0 0 0 1000px var(--input-bg) inset;
        }
        .new-auth .anchor-color { color: #a5b4fc; }
        .new-auth .anchor-color:hover { color: #c7d2fe; }
    </style>
@endpush

@section('app')
<div class="new-auth min-h-screen bg-slate-950 text-white relative overflow-hidden">
    @include($activeTemplate.'partials.new_nav')

    <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
        <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%);"></div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col lg:flex-row pt-24 lg:pt-28">
        <div class="hidden lg:flex lg:w-1/2 items-center justify-center px-12 py-12">
            <div class="max-w-md">
                <div class="flex items-center gap-3 mb-6">
                    <img src="{{ siteLogo() }}" alt="@lang('Logo')" class="h-10">
                </div>
                <h1 class="text-4xl font-bold tracking-tight text-white mb-4">
                    @lang('Account Recovery')
                </h1>
                <p class="text-slate-300 text-lg">
                    @lang('Recover access in a few steps and continue managing your payments safely.')
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
                <h2 class="text-2xl font-bold text-white mb-2">{{ __($pageTitle) }}</h2>
                <p class="text-slate-400 mb-6">@lang('Enter your email or username to recover your account.')</p>

                <form method="POST" action="{{ route('user.password.email') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Email or Username')</label>
                        <input type="text" class="form--control" name="value" value="{{ old('value') }}" required autofocus="off">
                    </div>
                    <x-captcha />
                    <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors">
                        @lang('Submit')
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navToggle = document.getElementById('nav-toggle');
            const navMenuMobile = document.getElementById('nav-menu-mobile');
            if (navToggle && navMenuMobile) {
                navToggle.addEventListener('click', () => {
                    navMenuMobile.classList.toggle('hidden');
                });
            }
        });
    </script>
@endpush
