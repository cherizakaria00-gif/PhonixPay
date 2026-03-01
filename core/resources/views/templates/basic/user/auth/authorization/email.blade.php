@extends($activeTemplate .'layouts.app')

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
        .new-auth .anchor-color { color: #a5b4fc; }
        .new-auth .anchor-color:hover { color: #c7d2fe; }

        .new-auth .verification-code-wrapper {
            width: 100%;
            padding: 0;
            background: transparent;
            border: none;
        }
        .new-auth .verification-code::after {
            background-color: #0b1220;
        }
        .new-auth .verification-code span {
            background: #0b1220;
            border-color: #1f2a44;
            color: #94a3b8;
        }
        .new-auth .verification-code input {
            color: #ffffff !important;
        }
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
                    @lang('Verify Email')
                </h1>
                <p class="text-slate-300 text-lg">
                    @lang('Confirm your email to secure access to your account.')
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
                <h2 class="text-2xl font-bold text-white mb-2">@lang('Verify Email Address')</h2>
                <p class="text-slate-400 mb-6">
                    @lang('A 6 digit verification code sent to your email address'):
                    <span class="text-indigo-300">{{ showEmailAddress(auth()->user()->email) }}</span>
                </p>

                <form action="{{route('user.verify.email')}}" method="POST" class="submit-form space-y-5">
                    @csrf

                    @include($activeTemplate.'partials.verification_code')

                    <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors">
                        @lang('Submit')
                    </button>

                    <div class="text-sm text-slate-400">
                        @lang('Don\'t get any code'),
                        <span class="countdown-wrapper">@lang('try again after') <span id="countdown" class="fw-bold">--</span> @lang('seconds')</span>
                        <a href="{{route('user.send.verify.code', 'email')}}" class="try-again-link d-none anchor-color"> @lang('Try again')</a>
                    </div>
                    <div class="text-sm text-slate-400">
                        @lang('Wrong email address?')
                        <button type="button" id="toggleEmailUpdateForm" class="anchor-color bg-transparent border-0 p-0">@lang('Change email')</button>
                    </div>
                    <button type="button" onclick="window.history.back()" class="anchor-color text-sm bg-transparent border-0 p-0">@lang('Back')</button>
                    <a href="{{ route('user.logout') }}" class="anchor-color text-sm">@lang('Logout')</a>
                </form>

                <form id="emailUpdateForm" action="{{ route('user.authorization.email.update') }}" method="POST" class="space-y-3 mt-5 {{ $errors->has('email') ? '' : 'd-none' }}">
                    @csrf
                    <label class="text-sm text-slate-300 d-block" for="emailUpdateInput">@lang('Update email')</label>
                    <input
                        id="emailUpdateInput"
                        type="email"
                        name="email"
                        value="{{ old('email', auth()->user()->email) }}"
                        class="w-full rounded-md border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-indigo-400 focus:outline-none"
                        required
                    >
                    @error('email')
                        <p class="text-danger text-sm mb-0">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="w-full rounded-md bg-slate-700 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-600 transition-colors">
                        @lang('Save new email')
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
    <script>
        var distance =Number("{{@$user->ver_code_send_at->addMinutes(2)->timestamp-time()}}");
        var x = setInterval(function() {
            distance--;
            document.getElementById("countdown").innerHTML = distance;
            if (distance <= 0) {
                clearInterval(x);
                document.querySelector('.countdown-wrapper').classList.add('d-none');
                document.querySelector('.try-again-link').classList.remove('d-none');
            }
        }, 1000);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navToggle = document.getElementById('nav-toggle');
            const navMenuMobile = document.getElementById('nav-menu-mobile');
            const emailUpdateToggle = document.getElementById('toggleEmailUpdateForm');
            const emailUpdateForm = document.getElementById('emailUpdateForm');
            if (navToggle && navMenuMobile) {
                navToggle.addEventListener('click', () => {
                    navMenuMobile.classList.toggle('hidden');
                });
            }
            if (emailUpdateToggle && emailUpdateForm) {
                emailUpdateToggle.addEventListener('click', () => {
                    emailUpdateForm.classList.toggle('d-none');
                });
            }
        });
    </script>
@endpush
