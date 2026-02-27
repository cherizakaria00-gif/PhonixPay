@extends($activeTemplate.'layouts.app')

@php
    $pages = App\Models\Page::where('tempname', $activeTemplate)->where('is_default', \App\Constants\Status::NO)->get();
    $blogs = App\Models\Frontend::where('data_keys', 'blog.element')->orderBy('id', 'DESC')->paginate(getPaginate());
    $flujiBlogs = [
        [
            'image' => '6423dccc7bc2b1680071884.png',
            'title' => 'How FlujiPay boosts approval rates for global merchants',
            'date' => '2026-02-01',
            'excerpt' => 'Learn how multi-acquirer routing, smart retries, and localized acquiring keep approvals high across regions.',
        ],
        [
            'image' => '6423dcd12424d1680071889.png',
            'title' => 'High-risk payments: the FlujiPay onboarding checklist',
            'date' => '2026-01-20',
            'excerpt' => 'A step-by-step guide to KYB, compliance, and documentation to get approved fast without surprises.',
        ],
        [
            'image' => '6423dcd4d8bb81680071892.png',
            'title' => 'Chargeback defense: reduce disputes with FlujiPay tools',
            'date' => '2026-01-10',
            'excerpt' => 'Proactive alerts, evidence automation, and dispute workflows that protect revenue and improve win rates.',
        ],
        [
            'image' => '6423dce5b429e1680071909.png',
            'title' => 'Same-day payouts: how FlujiPay accelerates cash flow',
            'date' => '2025-12-28',
            'excerpt' => 'Understand payout schedules, reserve strategies, and how to unlock faster settlements.',
        ],
        [
            'image' => '6423dce9c01631680071913.png',
            'title' => 'Unified API: launch cards, wallets, and crypto in days',
            'date' => '2025-12-15',
            'excerpt' => 'One integration for multiple payment methods, with webhooks and sandbox tools built for teams.',
        ],
        [
            'image' => '6423dcf2ca7011680071922.png',
            'title' => 'Scaling internationally with multi-currency processing',
            'date' => '2025-12-02',
            'excerpt' => 'Localize pricing, reduce FX friction, and settle in your preferred currencies with FlujiPay.',
        ],
    ];
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
                <p class="text-xs uppercase tracking-[0.2em] text-[#a7d9c2]">@lang('Blogs')</p>
                <h1 class="mt-4 text-4xl sm:text-5xl font-bold tracking-tight text-white">
                    {{ __($pageTitle ?? 'Blogs') }}
                </h1>
                <p class="mt-4 text-slate-300 text-lg">@lang('Explore our latest insights and product updates.')</p>
            </div>
        </section>

        <section class="pb-20">
            <div class="mx-auto max-w-6xl px-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($flujiBlogs as $blog)
                        <article class="group bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden">
                            <a href="{{ route('contact') }}" class="block">
                                <div class="relative overflow-hidden">
                                    <img src="{{ getImage('assets/images/frontend/blog/' . $blog['image'], '820x450') }}" alt="@lang('Blog')" class="h-48 w-full object-cover transition-transform duration-300 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
                                </div>
                            </a>
                            <div class="p-5">
                                <div class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($blog['date'])->format('M d, Y') }}</div>
                                <h3 class="mt-2 text-lg font-semibold text-white">
                                    {{ __($blog['title']) }}
                                </h3>
                                <p class="mt-2 text-sm text-slate-400">{{ __($blog['excerpt']) }}</p>
                                <a href="{{ route('contact') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-[#87c5a6] hover:text-[#a7d9c2]">
                                    @lang('Learn More')
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

            </div>
        </section>
    </main>

    <footer class="bg-slate-950 border-t border-slate-800">
        <div class="mx-auto max-w-7xl px-6 py-12 md:flex md:items-center md:justify-between lg:px-8">
            <div class="mt-8 md:order-1 md:mt-0">
                <p class="text-center text-xs leading-5 text-slate-500">
                    &copy; {{ date('Y') }} {{ __(gs('site_name')) }}. Tous droits réservés.
                </p>
            </div>
            <div class="flex justify-center space-x-6 md:order-2">
                <a href="{{ route('policy.pages', 'terms-of-service') }}" class="text-slate-400 hover:text-slate-300">Conditions</a>
                <a href="{{ route('policy.pages', 'privacy-policy') }}" class="text-slate-400 hover:text-slate-300">Confidentialité</a>
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
