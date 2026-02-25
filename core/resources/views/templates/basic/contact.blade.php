@extends($activeTemplate.'layouts.app')
@php
    $contact = @getContent('contact_us.content', true)->data_values;
    $pages = App\Models\Page::where('tempname', $activeTemplate)->where('is_default', \App\Constants\Status::NO)->get();
    $contactPhone = '+19707807495';
    $contactEmail = 'contact@flujipay.com';
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
    </style>
@endpush

@section('app')
<div class="min-h-screen bg-slate-950 text-white font-sans selection:bg-[#d83000]/30">
    <nav class="absolute w-full z-50 top-3 md:top-4 left-0">
        <div class="max-w-7xl mx-auto px-6">
            <div class="bg-black/60 backdrop-blur-md rounded-full shadow-lg border border-white/10 px-4 md:px-6">
                <div class="flex items-center gap-4 py-1.5">
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <img src="{{ siteLogo() }}" alt="@lang('Logo')" class="h-7 w-auto">
                    </a>

                    <div class="hidden md:flex flex-1 justify-center">
                        <div class="flex items-center gap-5 text-[12px] font-medium text-slate-200">
                            <a href="{{ route('home') }}" class="hover:text-white transition-colors">@lang('Home')</a>
                            @foreach($pages as $data)
                                <a href="{{ route('pages',[$data->slug]) }}" class="hover:text-white transition-colors">{{ __($data->name) }}</a>
                            @endforeach
                            <a href="{{ route('blogs') }}" class="hover:text-white transition-colors">@lang('Blogs')</a>
                            <a href="{{ route('api.documentation') }}" class="hover:text-white transition-colors">@lang('Developer')</a>
                        </div>
                    </div>

                    <div class="hidden md:flex items-center gap-3">
                        @auth
                            <a href="{{ route('user.home') }}" class="bg-[#d83000] hover:bg-[#f86000] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">@lang('Dashboard')</a>
                        @else
                            <a href="{{ route('user.login') }}" class="text-[#ff8a4d] hover:text-[#ffb07a] text-[12px] font-semibold transition-colors">@lang('Login')</a>
                            <a href="{{ route('user.register') }}" class="bg-[#d83000] hover:bg-[#f86000] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">Create an account</a>
                        @endauth
                    </div>

                    <button id="nav-toggle" class="md:hidden ml-auto inline-flex items-center justify-center h-8 w-8 rounded-full border border-white/20 text-slate-200 hover:text-white hover:border-white/30">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="md:hidden pb-3">
                    <div id="nav-menu-mobile" class="hidden bg-black/80 rounded-2xl shadow-lg border border-white/10 p-4">
                        <div class="flex flex-col gap-3 text-[12px] font-medium text-slate-200">
                            <a href="{{ route('home') }}" class="hover:text-white transition-colors">@lang('Home')</a>
                            @foreach($pages as $data)
                                <a href="{{ route('pages',[$data->slug]) }}" class="hover:text-white transition-colors">{{ __($data->name) }}</a>
                            @endforeach
                            <a href="{{ route('blogs') }}" class="hover:text-white transition-colors">@lang('Blogs')</a>
                            <a href="{{ route('api.documentation') }}" class="hover:text-white transition-colors">@lang('Developer')</a>
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            @auth
                                <a href="{{ route('user.home') }}" class="bg-[#d83000] hover:bg-[#f86000] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">@lang('Dashboard')</a>
                            @else
                                <a href="{{ route('user.login') }}" class="text-[#ff8a4d] hover:text-[#ffb07a] text-[12px] font-semibold transition-colors">@lang('Login')</a>
                                <a href="{{ route('user.register') }}" class="bg-[#d83000] hover:bg-[#f86000] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">Create an account</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <section class="pt-28 sm:pt-32 pb-10">
            <div class="mx-auto max-w-5xl px-6 text-center">
                <p class="text-xs uppercase tracking-[0.2em] text-[#ffb07a]">@lang('Contact')</p>
                <h1 class="mt-4 text-4xl sm:text-5xl font-bold tracking-tight text-white">
                    {{ __($pageTitle ?? 'Contact Us') }}
                </h1>
                <p class="mt-4 text-slate-300 text-lg">@lang('Tell us about your business and we will get back to you within 24-48 hours.')</p>
            </div>
        </section>

        <section class="pb-16">
            <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div class="space-y-4">
                    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 flex items-start gap-4">
                        <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#d83000]/15 border border-[#d83000]/30">
                            <i data-lucide="phone" class="w-6 h-6 text-[#f86000]"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-400">@lang('Phone')</p>
                            <p class="text-base font-semibold text-white">{{ $contactPhone }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 flex items-start gap-4">
                        <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#d83000]/15 border border-[#d83000]/30">
                            <i data-lucide="mail" class="w-6 h-6 text-[#f86000]"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-400">@lang('Email')</p>
                            <p class="text-base font-semibold text-white">{{ $contactEmail }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 flex items-start gap-4">
                        <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#d83000]/15 border border-[#d83000]/30">
                            <i data-lucide="map-pin" class="w-6 h-6 text-[#f86000]"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-400">@lang('Office')</p>
                            <p class="text-base font-semibold text-white">{{ @$contact->office_address }}</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -inset-4 bg-[#d83000]/20 blur-2xl rounded-3xl"></div>
                    <img src="{{ getImage('assets/images/frontend/contact_us/' .@$contact->image, '680x585') }}" alt="@lang('Contact')" class="relative rounded-3xl border border-white/10 shadow-2xl w-full object-cover">
                </div>
            </div>
        </section>

        <section class="pb-20">
            <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 sm:p-8">
                    <h2 class="text-2xl font-bold text-white mb-6">@lang('Contact With Us')</h2>
                    <form method="post" action="" class="verify-gcaptcha space-y-4" autocomplete="off">
                        @csrf
                        <div>
                            <label class="block text-sm text-slate-400 mb-2">@lang('Name')</label>
                            <input name="name" type="text" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-white placeholder:text-slate-500 focus:border-[#f86000] focus:ring-2 focus:ring-[#f86000]/30" value="@if(auth()->user()){{ auth()->user()->fullname }}@else{{ old('name') }}@endif" @if(auth()->user()) readonly @endif required>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-2">@lang('Email Address')</label>
                            <input name="email" type="email" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-white placeholder:text-slate-500 focus:border-[#f86000] focus:ring-2 focus:ring-[#f86000]/30" value="@if(auth()->user()){{ auth()->user()->email }}@else{{  old('email') }}@endif" @if(auth()->user()) readonly @endif required>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-2">@lang('Subject')</label>
                            <input name="subject" type="text" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-white placeholder:text-slate-500 focus:border-[#f86000] focus:ring-2 focus:ring-[#f86000]/30" value="{{ old('subject') }}" required>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-2">@lang('Your Message')</label>
                            <textarea name="message" rows="5" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-white placeholder:text-slate-500 focus:border-[#f86000] focus:ring-2 focus:ring-[#f86000]/30" required>{{ old('message') }}</textarea>
                        </div>

                        <div>
                            <x-captcha />
                        </div>

                        <button class="w-full rounded-xl bg-[#d83000] px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-[#f86000]">
                            @lang('Send Message')
                        </button>
                    </form>
                </div>
                <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 sm:p-8 flex items-center">
                    <img src="{{ getImage('assets/images/frontend/contact_us/' .@$contact->map_image, '885x535') }}" alt="@lang('Map')" class="w-full rounded-2xl border border-slate-800 object-cover">
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-slate-950 border-t border-slate-800">
        <div class="mx-auto max-w-7xl px-6 py-12 md:flex md:items-center md:justify-between lg:px-8">
            <div class="mt-8 md:order-1 md:mt-0">
                <p class="text-center text-xs leading-5 text-slate-500">
                    &copy; {{ date('Y') }} {{ __(gs('site_name')) }}. All rights reserved.
                </p>
            </div>
            <div class="flex justify-center space-x-6 md:order-2">
                <a href="{{ route('policy.pages', 'terms-of-service') }}" class="text-slate-400 hover:text-slate-300">Terms</a>
                <a href="{{ route('policy.pages', 'privacy-policy') }}" class="text-slate-400 hover:text-slate-300">Privacy</a>
                <a href="{{ route('api.documentation') }}" class="text-slate-400 hover:text-slate-300">API Docs</a>
            </div>
        </div>
    </footer>
</div>
@endsection

@push('script')
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();
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
