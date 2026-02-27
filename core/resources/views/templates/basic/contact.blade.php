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
<div class="min-h-screen bg-slate-950 text-white font-sans selection:bg-[#87c5a6]/30">
    @include($activeTemplate.'partials.new_nav')

    <main>
        <section class="pt-28 sm:pt-32 pb-10">
            <div class="mx-auto max-w-5xl px-6 text-center">
                <p class="text-xs uppercase tracking-[0.2em] text-[#a7d9c2]">@lang('Contact')</p>
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
                        <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#87c5a6]/15 border border-[#87c5a6]/30">
                            <i data-lucide="phone" class="w-6 h-6 text-[#9ad8bf]"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-400">@lang('Phone')</p>
                            <p class="text-base font-semibold text-white">{{ $contactPhone }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 flex items-start gap-4">
                        <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#87c5a6]/15 border border-[#87c5a6]/30">
                            <i data-lucide="mail" class="w-6 h-6 text-[#9ad8bf]"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-400">@lang('Email')</p>
                            <p class="text-base font-semibold text-white">{{ $contactEmail }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 flex items-start gap-4">
                        <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#87c5a6]/15 border border-[#87c5a6]/30">
                            <i data-lucide="map-pin" class="w-6 h-6 text-[#9ad8bf]"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-400">@lang('Office')</p>
                            <p class="text-base font-semibold text-white">{{ @$contact->office_address }}</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -inset-4 bg-[#87c5a6]/20 blur-2xl rounded-3xl"></div>
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
                            <input name="name" type="text" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-white placeholder:text-slate-500 focus:border-[#9ad8bf] focus:ring-2 focus:ring-[#9ad8bf]/30" value="@if(auth()->user()){{ auth()->user()->fullname }}@else{{ old('name') }}@endif" @if(auth()->user()) readonly @endif required>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-2">@lang('Email Address')</label>
                            <input name="email" type="email" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-white placeholder:text-slate-500 focus:border-[#9ad8bf] focus:ring-2 focus:ring-[#9ad8bf]/30" value="@if(auth()->user()){{ auth()->user()->email }}@else{{  old('email') }}@endif" @if(auth()->user()) readonly @endif required>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-2">@lang('Subject')</label>
                            <input name="subject" type="text" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-white placeholder:text-slate-500 focus:border-[#9ad8bf] focus:ring-2 focus:ring-[#9ad8bf]/30" value="{{ old('subject') }}" required>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-2">@lang('Your Message')</label>
                            <textarea name="message" rows="5" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-white placeholder:text-slate-500 focus:border-[#9ad8bf] focus:ring-2 focus:ring-[#9ad8bf]/30" required>{{ old('message') }}</textarea>
                        </div>

                        <div>
                            <x-captcha />
                        </div>

                        <button class="w-full rounded-xl bg-[#87c5a6] px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-[#9ad8bf]">
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
