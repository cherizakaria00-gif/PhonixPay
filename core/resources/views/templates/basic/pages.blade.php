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
                                <a href="{{ route('user.register') }}" class="bg-[#d83000] hover:bg-[#f86000] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">Créer un compte</a>
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
                                    <a href="{{ route('user.register') }}" class="bg-[#d83000] hover:bg-[#f86000] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">Créer un compte</a>
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
                    <p class="text-xs uppercase tracking-[0.2em] text-[#ffb07a]">{{ __($page->name ?? $page->title ?? 'Page') }}</p>
                    <h1 class="mt-4 text-4xl sm:text-5xl font-bold tracking-tight text-white">
                        {{ __($page->title ?? $page->name) }}
                    </h1>
                    @if($page->slug === 'about' && !empty($about->heading))
                        <p class="mt-4 text-slate-300 text-lg">{{ __($about->heading) }}</p>
                    @elseif($page->slug === 'faq')
                        @if(!empty($faq->subheading))
                            <p class="mt-4 text-slate-300 text-lg">{{ __($faq->subheading) }}</p>
                        @elseif(!empty($faq->heading))
                            <p class="mt-4 text-slate-300 text-lg">{{ __($faq->heading) }}</p>
                        @endif
                    @elseif($page->slug === 'mission')
                        @if(!empty($mission->description))
                            <p class="mt-4 text-slate-300 text-lg">{{ __($mission->description) }}</p>
                        @elseif(!empty($mission->heading))
                            <p class="mt-4 text-slate-300 text-lg">{{ __($mission->heading) }}</p>
                        @endif
                    @elseif($page->slug === 'gateway')
                        @if(!empty($feature->subheading))
                            <p class="mt-4 text-slate-300 text-lg">{{ __($feature->subheading) }}</p>
                        @elseif(!empty($feature->heading))
                            <p class="mt-4 text-slate-300 text-lg">{{ __($feature->heading) }}</p>
                        @endif
                    @endif
                </div>
            </section>

            @if($page->slug === 'about')
            <section class="pb-16">
                <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div class="relative">
                        <div class="absolute -inset-4 bg-[#d83000]/20 blur-2xl rounded-3xl"></div>
                        <img src="{{ getImage('assets/images/frontend/about/' .@$about->image, '330x375') }}" alt="@lang('About')" class="relative rounded-3xl border border-white/10 shadow-2xl w-full">
                    </div>
                    <div>
                        <p class="text-[#f86000] text-sm font-semibold">{{ __(@$about->heading) }}</p>
                        <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-white">{{ __(@$about->subheading) }}</h2>
                        <p class="mt-4 text-slate-300">{{ __(@$about->description) }}</p>
                        <div class="mt-8 grid grid-cols-2 gap-4">
                            @foreach($abouts as $stat)
                                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4">
                                    <div class="text-2xl font-bold text-white">{{ __(@$stat->data_values->title) }}</div>
                                    <div class="text-sm text-slate-400">{{ __(@$stat->data_values->description) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-16 bg-slate-900/60">
                <div class="mx-auto max-w-6xl px-6">
                    <div class="text-center mb-10">
                        <p class="text-[#f86000] text-sm font-semibold">{{ __(@$paymentSolution->heading) }}</p>
                        <h2 class="text-3xl font-bold text-white">{{ __(@$paymentSolution->subheading) }}</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($paymentSolutions as $item)
                            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
                                <h3 class="text-lg font-semibold text-white">{{ __(@$item->data_values->title) }}</h3>
                                <p class="mt-3 text-sm text-slate-400">{{ __(@$item->data_values->description) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="py-16">
                <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-white">{{ __(@$roadmap->heading) }}</h2>
                        <p class="mt-4 text-slate-300">{{ __(@$roadmap->description) }}</p>
                    </div>
                    <div class="relative">
                        <div class="absolute -inset-4 bg-[#d83000]/10 blur-2xl rounded-3xl"></div>
                        <img src="{{ getImage('assets/images/frontend/roadmap/' .@$roadmap->image, '620x645') }}" alt="@lang('Image')" class="relative rounded-3xl border border-white/10 shadow-2xl w-full">
                    </div>
                </div>
            </section>

            <section class="py-16 bg-slate-900/60">
                <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <div class="lg:col-span-1">
                        <p class="text-[#f86000] text-sm font-semibold">{{ __(@$developer->heading) }}</p>
                        <h2 class="mt-2 text-3xl font-bold text-white">{{ __(@$developer->subheading) }}</h2>
                    </div>
                    <div class="lg:col-span-2 space-y-4">
                        @foreach($developers as $item)
                            <div class="flex gap-4 items-start bg-slate-950/70 border border-slate-800 rounded-2xl p-5">
                                <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#d83000]/15 border border-[#d83000]/30">
                                    <img src="{{ getImage('assets/images/frontend/developer_tool/' .@$item->data_values->image, '630x865') }}" alt="@lang('Image')" class="h-6 w-6">
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">{{ __(@$item->data_values->title) }}</h3>
                                    <p class="text-sm text-slate-400 mt-2">{{ __(@$item->data_values->description) }}</p>
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
                        <div class="absolute -inset-4 bg-[#d83000]/20 blur-2xl rounded-3xl"></div>
                        <img src="{{ getImage('assets/images/frontend/mission/' .@$mission->image, '500x560') }}" alt="@lang('Mission')" class="relative rounded-3xl border border-white/10 shadow-2xl w-full">
                    </div>
                    <div>
                        <p class="text-[#f86000] text-sm font-semibold">{{ __(@$mission->heading) }}</p>
                        <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-white">{{ __($page->title ?? $page->name) }}</h2>
                        <p class="mt-4 text-slate-300">{{ __(@$mission->description) }}</p>
                        <ul class="mt-6 space-y-3 text-slate-300 text-sm">
                            @foreach($missions as $data)
                                <li class="flex gap-3">
                                    <span class="mt-1.5 h-2 w-2 rounded-full bg-[#f86000]"></span>
                                    <span>{{ __(@$data->data_values->list_text) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if(!empty($mission->button_text))
                            <a href="{{ @$mission->button_url }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#d83000] px-5 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-[#f86000]">
                                {{ __(@$mission->button_text) }}
                                <span aria-hidden="true">→</span>
                            </a>
                        @endif
                    </div>
                </div>
            </section>
            @endif

            @if($page->slug === 'gateway')
            <section class="py-16 bg-slate-900/60">
                <div class="mx-auto max-w-6xl px-6">
                    <div class="text-center mb-10">
                        <p class="text-[#f86000] text-sm font-semibold">{{ __(@$feature->heading) }}</p>
                        <h2 class="text-3xl font-bold text-white">{{ __(@$feature->subheading) }}</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($features as $item)
                            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
                                <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#d83000]/15 border border-[#d83000]/30">
                                    <img src="{{ getImage('assets/images/frontend/feature/' .@$item->data_values->image, '39x39') }}" alt="@lang('Feature')" class="h-6 w-6">
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-white">{{ __(@$item->data_values->title) }}</h3>
                                <p class="mt-2 text-sm text-slate-400">{{ __(@$item->data_values->description) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="py-12">
                <div class="mx-auto max-w-6xl px-6 text-center">
                    <p class="text-sm text-slate-400">{{ __(@$brand->heading) }}</p>
                    <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6 items-center">
                        @foreach($brands as $item)
                            <div class="flex items-center justify-center">
                                <img src="{{ getImage('assets/images/frontend/brand/' .@$item->data_values->image, '128x32') }}" alt="@lang('Brand')" class="h-6 opacity-70 grayscale hover:grayscale-0 hover:opacity-100 transition">
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="py-12 bg-slate-900/60">
                <div class="mx-auto max-w-5xl px-6">
                    <div class="bg-slate-950/70 border border-slate-800 rounded-3xl p-6">
                        <img src="{{ getImage('assets/images/frontend/payment_method/' .@$paymentMethod->image, '1216x116') }}" alt="@lang('Payment Method')" class="w-full object-contain">
                    </div>
                </div>
            </section>

            <section class="py-16">
                <div class="mx-auto max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <p class="text-[#f86000] text-sm font-semibold">{{ __(@$payout->heading) }}</p>
                        <h2 class="mt-2 text-3xl font-bold text-white">{{ __(@$payout->subheading) }}</h2>
                        <div class="mt-6 space-y-4">
                            @foreach($payouts as $item)
                                <div class="flex gap-4 items-start bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
                                    <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-[#d83000]/15 border border-[#d83000]/30">
                                        <img src="{{ getImage('assets/images/frontend/payout/' .@$item->data_values->image, '565x775') }}" alt="@lang('Payout')" class="h-6 w-6">
                                    </div>
                                    <div>
                                        <h3 class="text-white font-semibold">{{ __(@$item->data_values->title) }}</h3>
                                        <p class="text-sm text-slate-400 mt-2">{{ __(@$item->data_values->description) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="relative">
                        <div class="absolute -inset-4 bg-[#d83000]/10 blur-2xl rounded-3xl"></div>
                        <img src="{{ getImage('assets/images/frontend/payout/' .@$payout->image, '565x775') }}" alt="@lang('Payout')" class="relative rounded-3xl border border-white/10 shadow-2xl w-full">
                    </div>
                </div>
            </section>
            @endif

            @if(in_array($page->slug, ['about','faq']))
            <section class="py-16">
                <div class="mx-auto max-w-4xl px-6">
                    <div class="text-center mb-8">
                        <p class="text-[#f86000] text-sm font-semibold">{{ __(@$faq->heading) }}</p>
                        <h2 class="text-3xl font-bold text-white">{{ __(@$faq->subheading) }}</h2>
                    </div>
                    <div class="space-y-4">
                        @foreach($faqs as $index => $item)
                            <details class="group bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
                                <summary class="cursor-pointer text-white font-medium flex items-center justify-between">
                                    <span>{{ __(@$item->data_values->question) }}</span>
                                    <span class="text-[#ff8a4d] group-open:rotate-180 transition-transform">+</span>
                                </summary>
                                <p class="mt-3 text-slate-400 text-sm">{{ __(@$item->data_values->answer) }}</p>
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
@else
    @section('content')
        @if($sections != null)
            @foreach(json_decode($sections) as $sec)
                @include($activeTemplate.'sections.'.$sec)
            @endforeach
        @endif
    @endsection
@endif
