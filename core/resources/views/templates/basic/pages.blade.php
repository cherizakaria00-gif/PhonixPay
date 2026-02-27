@extends(in_array($page->slug, ['about','faq','mission','gateway']) ? $activeTemplate.'layouts.app' : $activeTemplate.'layouts.frontend')

@if(in_array($page->slug, ['about','faq','mission','gateway']))
    @php
        $pages = App\Models\Page::where('tempname', $activeTemplate)->where('is_default', \App\Constants\Status::NO)->get();
        $about = @getContent('about.content', true)->data_values;
        $abouts = @getContent('about.element', orderById:true);
        $paymentSolution = @getContent('payment_solution.content', true)->data_values;
        $paymentSolutions = @getContent('payment_solution.element', orderById:true);
        $roadmap = @getContent('roadmap.content', true)->data_values;
        $developer = @getContent('developer_tool.content', true)->data_values;
        $developers = @getContent('developer_tool.element', orderById:true);
        $faq = @getContent('faq.content', true)->data_values;
        $faqs = @getContent('faq.element', orderById:true);
        $mission = @getContent('mission.content', true)->data_values;
        $missions = @getContent('mission.element', orderById:true);
        $feature = @getContent('feature.content', true)->data_values;
        $features = @getContent('feature.element', orderById:true);
        $brand = @getContent('brand.content', true)->data_values;
        $brands = @getContent('brand.element');
        $paymentMethod = @getContent('payment_method.content', true)->data_values;
        $payout = @getContent('payout.content', true)->data_values;
        $payouts = @getContent('payout.element', orderById:true);
        $aboutCopy = [
            'hero' => 'FlujiPay is a payments partner built for high-risk and global merchants. Accept cards, wallets, and crypto with fast onboarding and reliable payouts.',
            'eyebrow' => 'Built for global merchants',
            'title' => 'Meet Your Strategic Payments Partner',
            'body' => 'From underwriting to settlement, FlujiPay gives you the infrastructure to scale. Our multi-acquirer routing improves approvals, while risk tools and chargeback defenses protect revenue.',
        ];
        $aboutStats = [
            ['value' => '150+', 'label' => 'Countries supported'],
            ['value' => '5 min', 'label' => 'Average setup time'],
            ['value' => '99.9%', 'label' => 'Platform uptime'],
            ['value' => '24/7', 'label' => 'Risk monitoring'],
        ];
        $solutionsCopy = [
            ['title' => 'Card and Wallet Acceptance', 'description' => 'Accept Visa, Mastercard, Apple Pay, Google Pay, and regional wallets with smart routing.'],
            ['title' => 'Multi-currency Processing', 'description' => 'Price and settle in multiple currencies with dynamic currency conversion and local acquiring.'],
            ['title' => 'Chargeback Protection', 'description' => 'Automated alerts, evidence tools, and dispute workflows to reduce losses.'],
            ['title' => 'High-risk Friendly Underwriting', 'description' => 'Built for global and high-risk industries with compliance-first onboarding.'],
            ['title' => 'Payouts and Settlement', 'description' => 'Flexible payout schedules with transparent fees and consolidated reporting.'],
            ['title' => 'Unified Reporting', 'description' => 'Real-time dashboards, reconciliation exports, and webhook alerts.'],
        ];
        $roadmapCopy = [
            'title' => 'Global reach, local settlement',
            'body' => 'We combine a network of acquiring partners with intelligent routing to keep approvals high and costs under control.',
            'points' => [
                'Smart routing across multiple acquirers',
                'Geo-optimized payment flows',
                'Consistent settlement across regions',
            ],
        ];
        $developerCopy = [
            'eyebrow' => 'Developer-first',
            'title' => 'Tools that ship faster',
            'items' => [
                ['title' => 'API and SDKs', 'description' => 'REST APIs, SDKs, and sample integrations to launch quickly.'],
                ['title' => 'Webhooks and Events', 'description' => 'Real-time notifications for payments, disputes, and payouts.'],
                ['title' => 'Sandbox Mode', 'description' => 'Test cards and end-to-end flows before going live.'],
            ],
        ];
        $faqCopy = [
            'eyebrow' => 'Faq',
            'title' => 'Find Answers to Your Common Questions',
            'intro' => 'Everything you need to know about onboarding, payouts, and compliance with FlujiPay.',
            'items' => [
                [
                    'q' => 'How quickly can I go live?',
                    'a' => 'Most merchants go live in minutes once KYC/KYB is approved. We provide a guided setup and test mode before switching to production.',
                ],
                [
                    'q' => 'Which payment methods are supported?',
                    'a' => 'We support Visa, Mastercard, Apple Pay, Google Pay, and select local wallets. Crypto acceptance is available for eligible merchants.',
                ],
                [
                    'q' => 'When do I receive payouts?',
                    'a' => 'Payouts are available on flexible schedules based on your risk profile and processing history. Same-day or next-day options are available.',
                ],
                [
                    'q' => 'Do you support high-risk industries?',
                    'a' => 'Yes. FlujiPay is built for global and high-risk merchants with compliant underwriting and tailored risk controls.',
                ],
                [
                    'q' => 'How do chargebacks and disputes work?',
                    'a' => 'We provide dispute alerts, evidence tools, and structured workflows to help reduce chargebacks and recover revenue.',
                ],
                [
                    'q' => 'Is there a setup fee or monthly fee?',
                    'a' => 'There are no setup fees or monthly charges. Pricing is usage-based and customized to your processing volume.',
                ],
            ],
        ];
        $missionCopy = [
            'eyebrow' => 'Our mission',
            'title' => 'Help global merchants scale with confidence',
            'body' => 'We make payments simple for high-risk and international businesses by combining compliant onboarding, smart routing, and transparent payouts.',
            'points' => [
                'Increase approval rates with multi-acquirer routing',
                'Protect revenue with built-in risk and dispute tools',
                'Expand globally with multi-currency settlement',
                'Launch quickly with developer-friendly APIs',
            ],
            'cta_text' => 'Get started',
            'cta_url' => route('user.register'),
        ];
        $gatewayCopy = [
            'eyebrow' => 'Gateway',
            'title' => 'A payment gateway built for global scale',
            'intro' => 'Accept cards, wallets, and crypto with intelligent routing, strong compliance, and real-time controls.',
            'features' => [
                ['title' => 'Smart Routing', 'description' => 'Automatically route payments to the best acquirer to improve approval rates.'],
                ['title' => '3DS & SCA Ready', 'description' => 'Built-in support for 3DS, SCA, and regional authentication requirements.'],
                ['title' => 'Tokenization', 'description' => 'Securely store card data and reduce PCI scope with tokenization.'],
                ['title' => 'Fraud Screening', 'description' => 'Rules-based checks and risk signals to block suspicious payments.'],
                ['title' => 'Subscriptions', 'description' => 'Recurring billing with retries, dunning, and smart payment recovery.'],
                ['title' => 'Unified API', 'description' => 'One integration for cards, wallets, and alternative payment methods.'],
            ],
        ];
        $gatewayPayoutCopy = [
            'eyebrow' => 'Payouts',
            'title' => 'Fast, predictable settlements',
            'items' => [
                ['title' => 'Flexible schedules', 'description' => 'Choose daily, weekly, or custom payout cycles based on your risk profile.'],
                ['title' => 'Multi-currency settlement', 'description' => 'Settle in supported currencies with transparent FX handling.'],
                ['title' => 'Consolidated reporting', 'description' => 'Track all payouts and fees with clean, exportable reports.'],
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
                    <p class="text-xs uppercase tracking-[0.2em] text-[#a7d9c2]">{{ __($page->name ?? $page->title ?? 'Page') }}</p>
                    <h1 class="mt-4 text-4xl sm:text-5xl font-bold tracking-tight text-white">
                        {{ __($page->title ?? $page->name) }}
                    </h1>
                    @if($page->slug === 'about')
                        <p class="mt-4 text-slate-300 text-lg">{{ __($aboutCopy['hero']) }}</p>
                    @elseif($page->slug === 'faq')
                        <p class="mt-4 text-slate-300 text-lg">{{ __($faqCopy['intro']) }}</p>
                    @elseif($page->slug === 'mission')
                        <p class="mt-4 text-slate-300 text-lg">{{ __($missionCopy['body']) }}</p>
                    @elseif($page->slug === 'gateway')
                        <p class="mt-4 text-slate-300 text-lg">{{ __($gatewayCopy['intro']) }}</p>
                    @endif
                </div>
            </section>

            @if($page->slug === 'about')
            <section class="pb-16">
                <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div class="relative">
                        <div class="absolute -inset-4 bg-[#87c5a6]/20 blur-2xl rounded-3xl"></div>
                        <img src="{{ getImage('assets/images/frontend/about/' .@$about->image, '330x375') }}" alt="@lang('About')" class="relative rounded-3xl border border-white/10 shadow-2xl w-full">
                    </div>
                    <div>
                        <p class="text-[#9ad8bf] text-sm font-semibold">{{ __($aboutCopy['eyebrow']) }}</p>
                        <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-white">{{ __($aboutCopy['title']) }}</h2>
                        <p class="mt-4 text-slate-300">{{ __($aboutCopy['body']) }}</p>
                        <div class="mt-8 grid grid-cols-2 gap-4">
                            @foreach($aboutStats as $stat)
                                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4">
                                    <div class="text-2xl font-bold text-white">{{ __($stat['value']) }}</div>
                                    <div class="text-sm text-slate-400">{{ __($stat['label']) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-16 bg-slate-900/60">
                <div class="mx-auto max-w-6xl px-6">
                    <div class="text-center mb-10">
                        <p class="text-[#9ad8bf] text-sm font-semibold">@lang('Payment Solutions')</p>
                        <h2 class="text-3xl font-bold text-white">@lang('Everything you need to scale')</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($solutionsCopy as $item)
                            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
                                <h3 class="text-lg font-semibold text-white">{{ __($item['title']) }}</h3>
                                <p class="mt-3 text-sm text-slate-400">{{ __($item['description']) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="py-16">
                <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-white">{{ __($roadmapCopy['title']) }}</h2>
                        <p class="mt-4 text-slate-300">{{ __($roadmapCopy['body']) }}</p>
                        <ul class="mt-6 space-y-3 text-sm text-slate-300">
                            @foreach($roadmapCopy['points'] as $point)
                                <li class="flex gap-3">
                                    <span class="mt-2 h-2 w-2 rounded-full bg-[#87c5a6]"></span>
                                    <span>{{ __($point) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="relative">
                        <div class="absolute -inset-4 bg-[#87c5a6]/10 blur-2xl rounded-3xl"></div>
                        <img src="{{ getImage('assets/images/frontend/roadmap/' .@$roadmap->image, '620x645') }}" alt="@lang('Image')" class="relative rounded-3xl border border-white/10 shadow-2xl w-full">
                    </div>
                </div>
            </section>

            <section class="py-16 bg-slate-900/60">
                <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <div class="lg:col-span-1">
                        <p class="text-[#9ad8bf] text-sm font-semibold">{{ __($developerCopy['eyebrow']) }}</p>
                        <h2 class="mt-2 text-3xl font-bold text-white">{{ __($developerCopy['title']) }}</h2>
                    </div>
                    <div class="lg:col-span-2 space-y-4">
                        @foreach($developerCopy['items'] as $index => $item)
                            <div class="flex gap-4 items-start bg-slate-950/70 border border-slate-800 rounded-2xl p-5">
                                <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#87c5a6]/15 border border-[#87c5a6]/30 text-[#87c5a6] font-semibold">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">{{ __($item['title']) }}</h3>
                                    <p class="text-sm text-slate-400 mt-2">{{ __($item['description']) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif

            @if($page->slug === 'mission')
            <section class="pb-16">
                <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div class="relative">
                        <div class="absolute -inset-4 bg-[#87c5a6]/20 blur-2xl rounded-3xl"></div>
                        <img src="{{ getImage('assets/images/frontend/mission/' .@$mission->image, '500x560') }}" alt="@lang('Mission')" class="relative rounded-3xl border border-white/10 shadow-2xl w-full">
                    </div>
                    <div>
                        <p class="text-[#9ad8bf] text-sm font-semibold">{{ __($missionCopy['eyebrow']) }}</p>
                        <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-white">{{ __($missionCopy['title']) }}</h2>
                        <p class="mt-4 text-slate-300">{{ __($missionCopy['body']) }}</p>
                        <ul class="mt-6 space-y-3 text-slate-300 text-sm">
                            @foreach($missionCopy['points'] as $point)
                                <li class="flex gap-3">
                                    <span class="mt-1.5 h-2 w-2 rounded-full bg-[#9ad8bf]"></span>
                                    <span>{{ __($point) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ $missionCopy['cta_url'] }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#87c5a6] px-5 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-[#9ad8bf]">
                            {{ __($missionCopy['cta_text']) }}
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </section>
            @endif

            @if($page->slug === 'gateway')
            <section class="py-16 bg-slate-900/60">
                <div class="mx-auto max-w-6xl px-6">
                    <div class="text-center mb-10">
                        <p class="text-[#9ad8bf] text-sm font-semibold">{{ __($gatewayCopy['eyebrow']) }}</p>
                        <h2 class="text-3xl font-bold text-white">{{ __($gatewayCopy['title']) }}</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($gatewayCopy['features'] as $item)
                            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
                                <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#87c5a6]/15 border border-[#87c5a6]/30 text-[#87c5a6] font-semibold">
                                    ✓
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-white">{{ __($item['title']) }}</h3>
                                <p class="mt-2 text-sm text-slate-400">{{ __($item['description']) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>


            <section class="py-16">
                <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <p class="text-[#9ad8bf] text-sm font-semibold">{{ __($gatewayPayoutCopy['eyebrow']) }}</p>
                        <h2 class="mt-2 text-3xl font-bold text-white">{{ __($gatewayPayoutCopy['title']) }}</h2>
                        <div class="mt-6 space-y-4">
                            @foreach($gatewayPayoutCopy['items'] as $item)
                                <div class="flex gap-4 items-start bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
                                    <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#87c5a6]/15 border border-[#87c5a6]/30 text-[#87c5a6] font-semibold">
                                        $
                                    </div>
                                    <div>
                                        <h3 class="text-white font-semibold">{{ __($item['title']) }}</h3>
                                        <p class="text-sm text-slate-400 mt-2">{{ __($item['description']) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="relative">
                        <div class="absolute -inset-4 bg-[#87c5a6]/10 blur-2xl rounded-3xl"></div>
                        <img src="{{ getImage('assets/images/frontend/payout/' .@$payout->image, '565x775') }}" alt="@lang('Payout')" class="relative rounded-3xl border border-white/10 shadow-2xl w-full">
                    </div>
                </div>
            </section>
            @endif

            @if(in_array($page->slug, ['about','faq']))
            <section class="py-16">
                <div class="mx-auto max-w-4xl px-6">
                    <div class="text-center mb-8">
                        <p class="text-[#9ad8bf] text-sm font-semibold">{{ __($faqCopy['eyebrow']) }}</p>
                        <h2 class="text-3xl font-bold text-white">{{ __($faqCopy['title']) }}</h2>
                    </div>
                    <div class="space-y-4">
                        @foreach($faqCopy['items'] as $index => $item)
                            <details class="group bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
                                <summary class="cursor-pointer text-white font-medium flex items-center justify-between">
                                    <span>{{ __($item['q']) }}</span>
                                    <span class="text-[#87c5a6] group-open:rotate-180 transition-transform">+</span>
                                </summary>
                                <p class="mt-3 text-slate-400 text-sm">{{ __($item['a']) }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif
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
            });
        </script>
    @endpush
@else
    @section('content')
        @if($sections != null)
            @foreach(json_decode($sections) as $sec)
                @include($activeTemplate.'sections.'.$sec)
            @endforeach
        @endif
    @endsection
@endif
