@extends($activeTemplate.'layouts.app')

@php
    $banner = @getContent('banner.content', true)->data_values;
    $product = @getContent('product.content', true)->data_values;
    $pages = App\Models\Page::where('tempname', $activeTemplate)->where('is_default', \App\Constants\Status::NO)->get();
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
        <!-- Hero -->
        <div class="relative bg-slate-950 overflow-hidden isolate pt-20 pb-16 sm:pt-24 sm:pb-24 lg:pt-28 lg:pb-32">
            <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
                <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%);"></div>
            </div>

            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <div class="mb-8 flex justify-center">
                        <div class="relative rounded-full px-3 py-1 text-sm leading-6 text-slate-400 ring-1 ring-white/10 hover:ring-white/20">
                            Specialized in High Risk & Offshore
                            <a href="#features" class="font-semibold text-[#f86000]">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Learn more <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight text-white sm:text-6xl">
                        The Payment Gateway for <span class="text-[#d83000]">Bold Businesses</span>
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-slate-300">
                        IPTV, dropshipping, e-books, replicas. Stop letting traditional banks block your growth. WooCommerce integration in 5 minutes.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <a href="{{ @$banner->button_url ?: route('user.register') }}" class="rounded-md bg-[#d83000] px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#f86000] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d83000] flex items-center gap-2">
                            Get started <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                        <a href="{{ @$product->btn_url ? url($product->btn_url) : route('contact') }}" class="text-sm font-semibold leading-6 text-white flex items-center gap-2 hover:text-[#ffb07a] transition-colors">
                            View the demo <i data-lucide="zap" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>

                <div class="mt-16 flex justify-center gap-8 grayscale opacity-50">
                    <div class="flex items-center gap-2 text-white font-bold text-xl">
                        <i data-lucide="shield-check" class="w-6 h-6 text-green-500"></i> SecurePayments
                    </div>
                    <div class="hidden sm:flex items-center gap-2 text-white font-bold text-xl">WooCommerce</div>
                    <div class="flex items-center gap-2 text-white font-bold text-xl">Visa / MasterCard</div>
                </div>
            </div>
        </div>

        <!-- Industries -->
        <div class="bg-slate-950 py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <h2 class="text-center text-3xl font-bold tracking-tight text-white sm:text-4xl mb-16">
                    Supported Industries
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-6">
                    <div class="flex flex-col items-center justify-center p-6 bg-slate-900 rounded-xl border border-slate-800 hover:border-[#d83000]/50 hover:bg-slate-800 transition-all cursor-default group">
                        <div class="transform group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="monitor-play" class="w-8 h-8 mb-4 text-pink-500"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-300 group-hover:text-white text-center">IPTV</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-6 bg-slate-900 rounded-xl border border-slate-800 hover:border-[#d83000]/50 hover:bg-slate-800 transition-all cursor-default group">
                        <div class="transform group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="layers" class="w-8 h-8 mb-4 text-blue-500"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-300 group-hover:text-white text-center">Digital Products</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-6 bg-slate-900 rounded-xl border border-slate-800 hover:border-[#d83000]/50 hover:bg-slate-800 transition-all cursor-default group">
                        <div class="transform group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="shopping-bag" class="w-8 h-8 mb-4 text-purple-500"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-300 group-hover:text-white text-center">Replica</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-6 bg-slate-900 rounded-xl border border-slate-800 hover:border-[#d83000]/50 hover:bg-slate-800 transition-all cursor-default group">
                        <div class="transform group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="book-open" class="w-8 h-8 mb-4 text-yellow-500"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-300 group-hover:text-white text-center">E-Books & Info</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-6 bg-slate-900 rounded-xl border border-slate-800 hover:border-[#d83000]/50 hover:bg-slate-800 transition-all cursor-default group">
                        <div class="transform group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="plane" class="w-8 h-8 mb-4 text-cyan-500"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-300 group-hover:text-white text-center">Travel</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-6 bg-slate-900 rounded-xl border border-slate-800 hover:border-[#d83000]/50 hover:bg-slate-800 transition-all cursor-default group">
                        <div class="transform group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="gamepad-2" class="w-8 h-8 mb-4 text-green-500"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-300 group-hover:text-white text-center">Gaming</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-6 bg-slate-900 rounded-xl border border-slate-800 hover:border-[#d83000]/50 hover:bg-slate-800 transition-all cursor-default group">
                        <div class="transform group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="coins" class="w-8 h-8 mb-4 text-orange-500"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-300 group-hover:text-white text-center">Crypto</span>
                    </div>
                </div>
                <div class="mt-12 text-center">
                    <p class="text-slate-400">
                        Is your industry not listed?
                        <a href="{{ route('contact') }}" class="text-[#f86000] hover:underline">Contact our support</a>
                        or use the AI assistant.
                    </p>
                </div>
            </div>
        </div>

        <div id="pricing"></div>

        <!-- Features -->
        <div id="features" class="bg-slate-900 py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl lg:text-center">
                    <h2 class="text-base font-semibold leading-7 text-[#f86000]">Everything you need</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Built for High Risk, secure for everyone
                    </p>
                    <p class="mt-6 text-lg leading-8 text-slate-400">
                        We understand the challenges of industries like IPTV or dropshipping. Our infrastructure is built to withstand where others fail.
                    </p>
                </div>
                <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                    <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                        <div class="flex flex-col bg-slate-800/50 p-6 rounded-2xl border border-slate-700/50 hover:border-[#d83000]/50 transition-colors">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-white">
                                <i data-lucide="percent" class="h-6 w-6 text-[#f86000]"></i>
                                Flat 10% Commission
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-slate-400">
                                <p class="flex-auto">A simple, transparent model. 10% per successful transaction. No monthly fees, no hidden fees.</p>
                            </dd>
                        </div>
                        <div class="flex flex-col bg-slate-800/50 p-6 rounded-2xl border border-slate-700/50 hover:border-[#d83000]/50 transition-colors">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-white">
                                <i data-lucide="refresh-ccw" class="h-6 w-6 text-[#f86000]"></i>
                                Weekly Payouts
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-slate-400">
                                <p class="flex-auto">Optimized cashflow. Receive your funds weekly directly to your bank account or crypto.</p>
                            </dd>
                        </div>
                        <div class="flex flex-col bg-slate-800/50 p-6 rounded-2xl border border-slate-700/50 hover:border-[#d83000]/50 transition-colors">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-white">
                                <i data-lucide="plug" class="h-6 w-6 text-[#f86000]"></i>
                                WooCommerce Plugin
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-slate-400">
                                <p class="flex-auto">Our WordPress plugin installs in one click. Connect your store and start getting paid immediately.</p>
                            </dd>
                        </div>
                        <div class="flex flex-col bg-slate-800/50 p-6 rounded-2xl border border-slate-700/50 hover:border-[#d83000]/50 transition-colors">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-white">
                                <i data-lucide="lock" class="h-6 w-6 text-[#f86000]"></i>
                                KYC & Security
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-slate-400">
                                <p class="flex-auto">KYC is required to ensure network security and the long-term reliability of your payouts.</p>
                            </dd>
                        </div>
                        <div class="flex flex-col bg-slate-800/50 p-6 rounded-2xl border border-slate-700/50 hover:border-[#d83000]/50 transition-colors">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-white">
                                <i data-lucide="globe" class="h-6 w-6 text-[#f86000]"></i>
                                Global Support
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-slate-400">
                                <p class="flex-auto">Accept payments worldwide. We handle multiple currencies and international cards.</p>
                            </dd>
                        </div>
                        <div class="flex flex-col bg-slate-800/50 p-6 rounded-2xl border border-slate-700/50 hover:border-[#d83000]/50 transition-colors">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-white">
                                <i data-lucide="zap" class="h-6 w-6 text-[#f86000]"></i>
                                Fast Activation
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-slate-400">
                                <p class="flex-auto">Account verification within 24-48 hours after submitting KYC documents.</p>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Integration -->
        <div id="integration" class="bg-slate-900 py-24 overflow-hidden">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl mb-6">
                            Ultra-simple WooCommerce integration
                        </h2>
                        <p class="text-lg text-slate-400 mb-8">
                            No need to be a developer. Our official WordPress plugin is set up in minutes.
                        </p>

                        <div class="space-y-8">
                            <div class="flex gap-4">
                                <div class="flex-none flex items-center justify-center w-10 h-10 rounded-lg bg-[#d83000]/10 text-[#f86000]">
                                    <i data-lucide="download" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold text-lg">1. Download the Plugin</h3>
                                    <p class="text-slate-400 mt-1">Get the .zip file from your {{ __(gs('site_name')) }} dashboard after account approval.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-none flex items-center justify-center w-10 h-10 rounded-lg bg-[#d83000]/10 text-[#f86000]">
                                    <i data-lucide="settings" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold text-lg">2. Configure the API</h3>
                                    <p class="text-slate-400 mt-1">Enter your Merchant Key and Secret Key in WooCommerce settings.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-none flex items-center justify-center w-10 h-10 rounded-lg bg-[#d83000]/10 text-[#f86000]">
                                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold text-lg">3. Get Paid</h3>
                                    <p class="text-slate-400 mt-1">Customers can pay by card instantly. Secure redirect included.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute -inset-4 bg-[#d83000]/20 blur-xl rounded-full"></div>
                        <div class="relative bg-slate-800 rounded-xl border border-slate-700 p-6 shadow-2xl">
                            <div class="flex items-center gap-2 mb-4 border-b border-slate-700 pb-4">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                <span class="ml-2 text-xs text-slate-500">WooCommerce Settings &gt; Payments</span>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Method Title</label>
                                    <div class="w-full h-8 bg-slate-900 rounded border border-slate-600 flex items-center px-3 text-sm text-white">Credit Card (Secure)</div>
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">API Public Key</label>
                                    <div class="w-full h-8 bg-slate-900 rounded border border-slate-600 flex items-center px-3 text-sm text-slate-500 font-mono">pk_live_51Jz...</div>
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">API Secret Key</label>
                                    <div class="w-full h-8 bg-slate-900 rounded border border-slate-600 flex items-center px-3 text-sm text-slate-500 font-mono">••••••••••••••••••••</div>
                                </div>
                                <div class="pt-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-4 h-4 rounded border border-[#d83000] bg-[#d83000] flex items-center justify-center text-xs text-white">✓</div>
                                        <span class="text-sm text-white">Enable {{ __(gs('site_name')) }} Gateway</span>
                                    </div>
                                </div>
                                <button class="bg-[#d83000] text-white text-sm px-4 py-2 rounded mt-2">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KYC -->
        <div id="kyc" class="bg-slate-950 py-24 relative">
            <div class="absolute inset-0 bg-[#d83000]/5 bg-[radial-gradient(#d83000_1px,transparent_1px)] [background-size:16px_16px] [mask-image:radial-gradient(ellipse_50%_50%_at_50%_50%,#000_70%,transparent_100%)]"></div>

            <div class="relative mx-auto max-w-7xl px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl mb-4">
                    Compliance & KYC
                </h2>
                <p class="max-w-2xl mx-auto text-lg text-slate-400 mb-16">
                    To ensure stable weekly payouts, we require a complete file. This guarantees our longevity and yours.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-slate-900 p-8 rounded-2xl border border-slate-800">
                        <div class="mx-auto w-12 h-12 bg-[#d83000]/50 rounded-full flex items-center justify-center mb-4">
                            <i data-lucide="user-check" class="w-6 h-6 text-[#f86000]"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Identity</h3>
                        <p class="text-slate-400">Valid passport or national ID of the company director.</p>
                    </div>

                    <div class="bg-slate-900 p-8 rounded-2xl border border-slate-800">
                        <div class="mx-auto w-12 h-12 bg-[#d83000]/50 rounded-full flex items-center justify-center mb-4">
                            <i data-lucide="building-2" class="w-6 h-6 text-[#f86000]"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Company</h3>
                        <p class="text-slate-400">Certificate of incorporation and proof of company address (less than 3 months old).</p>
                    </div>

                    <div class="bg-slate-900 p-8 rounded-2xl border border-slate-800">
                        <div class="mx-auto w-12 h-12 bg-[#d83000]/50 rounded-full flex items-center justify-center mb-4">
                            <i data-lucide="file-text" class="w-6 h-6 text-[#f86000]"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Activity</h3>
                        <p class="text-slate-400">Proof of domain ownership (invoice) and transaction history (optional but recommended).</p>
                    </div>
                </div>

                <div class="mt-12 bg-[#d83000]/15 border border-[#d83000]/20 rounded-lg p-4 inline-block">
                    <p class="text-[#ffb07a] font-medium">Average verification time: 24 business hours</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-slate-950 border-t border-slate-800">
        <div class="mx-auto max-w-7xl px-6 py-12 md:flex md:items-center md:justify-between lg:px-8">
            <div class="mt-8 md:order-1 md:mt-0">
                <p class="text-center text-xs leading-5 text-slate-500">
                    &copy; {{ date('Y') }} {{ __(gs('site_name')) }}. All rights reserved.
                </p>
                <p class="text-center text-xs leading-5 text-slate-600 mt-2">
                    We do not support illegal activities. All businesses are subject to verification.
                </p>
            </div>
            <div class="flex justify-center space-x-6 md:order-2">
                <a href="{{ route('policy.pages', 'terms-of-service') }}" class="text-slate-400 hover:text-slate-300">Terms</a>
                <a href="{{ route('policy.pages', 'privacy-policy') }}" class="text-slate-400 hover:text-slate-300">Privacy</a>
                <a href="{{ route('api.documentation') }}" class="text-slate-400 hover:text-slate-300">API Docs</a>
            </div>
        </div>
    </footer>

    <!-- Support Widget (static) -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end">
        <div id="support-panel" class="hidden mb-4 w-[320px] sm:w-[360px] bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-[#d83000] p-4 flex justify-between items-center">
                <div class="flex items-center gap-2 text-white font-semibold">
                    <i data-lucide="bot" class="w-5 h-5"></i>
                    <span>Compliance Assistant</span>
                </div>
                <button type="button" id="support-close" class="text-white/80 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-4 bg-slate-900 space-y-3">
                <p class="text-sm text-slate-200">Hi! Ask your questions about your business or KYC.</p>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 text-sm text-[#ffb07a] hover:text-[#ffd0b0]">
                    Contact support <i data-lucide="message-square" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
        <button id="support-toggle" class="group relative flex items-center justify-center w-14 h-14 bg-[#d83000] rounded-full shadow-lg hover:bg-[#f86000] transition-all hover:scale-105 active:scale-95">
            <i data-lucide="message-square" class="w-6 h-6 text-white"></i>
        </button>
    </div>
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
            const panel = document.getElementById('support-panel');
            const toggle = document.getElementById('support-toggle');
            const closeBtn = document.getElementById('support-close');
            const togglePanel = () => panel && panel.classList.toggle('hidden');
            if (toggle) toggle.addEventListener('click', togglePanel);
            if (closeBtn) closeBtn.addEventListener('click', togglePanel);
        });
    </script>
@endpush
