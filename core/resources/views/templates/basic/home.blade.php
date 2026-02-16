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

        .scanner-section {
            position: relative;
            background: #000000;
            padding: 70px 0 90px;
            overflow: hidden;
        }
        .scanner-shell {
            position: relative;
            min-height: 360px;
        }
        .scanner-container {
            position: relative;
            width: 100%;
            height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .card-stream {
            position: absolute;
            width: 100vw;
            height: 180px;
            display: flex;
            align-items: center;
            overflow: visible;
        }
        .card-line {
            display: flex;
            align-items: center;
            gap: 60px;
            white-space: nowrap;
            cursor: grab;
            user-select: none;
            will-change: transform;
        }
        .card-line:active,
        .card-line.dragging {
            cursor: grabbing;
        }
        .card-line.css-animated {
            animation: scrollCards 40s linear infinite;
        }
        @keyframes scrollCards {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100vw); }
        }
        .card-wrapper {
            position: relative;
            width: 400px;
            height: 250px;
            flex-shrink: 0;
        }
        .card {
            position: absolute;
            top: 0;
            left: 0;
            width: 400px;
            height: 250px;
            border-radius: 15px;
            overflow: hidden;
        }
        .card-normal {
            background: transparent;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            z-index: 2;
            position: relative;
            overflow: hidden;
            clip-path: inset(0 0 0 var(--clip-right, 0%));
        }
        .card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 15px;
            transition: all 0.3s ease;
            filter: brightness(1.1) contrast(1.1);
        }
        .card-image:hover {
            filter: brightness(1.2) contrast(1.2);
        }
        .card-ascii {
            background: transparent;
            z-index: 1;
            position: absolute;
            top: 0;
            left: 0;
            width: 400px;
            height: 250px;
            border-radius: 15px;
            overflow: hidden;
            clip-path: inset(0 calc(100% - var(--clip-left, 0%)) 0 0);
        }
        .ascii-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            color: rgba(220, 210, 255, 0.6);
            font-family: "Courier New", monospace;
            font-size: 11px;
            line-height: 13px;
            overflow: hidden;
            white-space: pre;
            animation: glitch 0.1s infinite linear alternate-reverse;
            margin: 0;
            padding: 0;
            text-align: left;
            vertical-align: top;
            box-sizing: border-box;
            -webkit-mask-image: linear-gradient(
                to right,
                rgba(0, 0, 0, 1) 0%,
                rgba(0, 0, 0, 0.8) 30%,
                rgba(0, 0, 0, 0.6) 50%,
                rgba(0, 0, 0, 0.4) 80%,
                rgba(0, 0, 0, 0.2) 100%
            );
            mask-image: linear-gradient(
                to right,
                rgba(0, 0, 0, 1) 0%,
                rgba(0, 0, 0, 0.8) 30%,
                rgba(0, 0, 0, 0.6) 50%,
                rgba(0, 0, 0, 0.4) 80%,
                rgba(0, 0, 0, 0.2) 100%
            );
        }
        @keyframes glitch {
            0% { opacity: 1; }
            15% { opacity: 0.9; }
            16% { opacity: 1; }
            49% { opacity: 0.8; }
            50% { opacity: 1; }
            99% { opacity: 0.9; }
            100% { opacity: 1; }
        }
        .scanner {
            display: none;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 4px;
            height: 300px;
            border-radius: 30px;
            background: linear-gradient(
                to bottom,
                transparent,
                rgba(0, 255, 255, 0.8),
                rgba(0, 255, 255, 1),
                rgba(0, 255, 255, 0.8),
                transparent
            );
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.8), 0 0 40px rgba(0, 255, 255, 0.4);
            animation: scanPulse 2s ease-in-out infinite alternate;
            z-index: 10;
        }
        @keyframes scanPulse {
            0% { opacity: 0.8; transform: translate(-50%, -50%) scaleY(1); }
            100% { opacity: 1; transform: translate(-50%, -50%) scaleY(1.1); }
        }
        .scan-effect {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(0, 255, 255, 0.4),
                transparent
            );
            animation: scanEffect 0.6s ease-out;
            pointer-events: none;
            z-index: 5;
        }
        @keyframes scanEffect {
            0% { transform: translateX(-100%); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateX(100%); opacity: 0; }
        }
        #particleCanvas {
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            width: 100vw;
            height: 250px;
            z-index: 0;
            pointer-events: none;
        }
        #scannerCanvas {
            position: absolute;
            top: 50%;
            left: -3px;
            transform: translateY(-50%);
            width: 100vw;
            height: 300px;
            z-index: 15;
            pointer-events: none;
        }
        @media (max-width: 768px) {
            .scanner-shell {
                padding-top: 16px;
            }
            .scanner-container {
                height: 280px;
            }
        }
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

        <!-- Scanner Section -->
        <section class="scanner-section">
            <div class="scanner-shell">
                <div class="scanner-container">
                    <canvas id="particleCanvas"></canvas>
                    <canvas id="scannerCanvas"></canvas>
                    <div class="scanner"></div>
                    <div class="card-stream" id="cardStream">
                        <div class="card-line" id="cardLine"></div>
                    </div>
                </div>
            </div>
        </section>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        const codeChars =
            "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789(){}[]<>;:,._-+=!@#$%^&*|\\/\"'`~?";

        class CardStreamController {
            constructor() {
                this.container = document.getElementById("cardStream");
                this.cardLine = document.getElementById("cardLine");
                this.speedIndicator = document.getElementById("speedValue");

                this.position = 0;
                this.velocity = 120;
                this.direction = -1;
                this.isAnimating = true;
                this.isDragging = false;

                this.lastTime = 0;
                this.lastMouseX = 0;
                this.mouseVelocity = 0;
                this.friction = 0.95;
                this.minVelocity = 30;

                this.containerWidth = 0;
                this.cardLineWidth = 0;

                this.init();
            }

            init() {
                if (!this.container || !this.cardLine) return;
                this.populateCardLine();
                this.calculateDimensions();
                this.setupEventListeners();
                this.updateCardPosition();
                this.animate();
                this.startPeriodicUpdates();
            }

            calculateDimensions() {
                this.containerWidth = this.container.offsetWidth;
                const cardWidth = 400;
                const cardGap = 60;
                const cardCount = this.cardLine.children.length;
                this.cardLineWidth = (cardWidth + cardGap) * cardCount;
            }

            setupEventListeners() {
                this.cardLine.addEventListener("mousedown", (e) => this.startDrag(e));
                document.addEventListener("mousemove", (e) => this.onDrag(e));
                document.addEventListener("mouseup", () => this.endDrag());

                this.cardLine.addEventListener(
                    "touchstart",
                    (e) => this.startDrag(e.touches[0]),
                    { passive: false }
                );
                document.addEventListener("touchmove", (e) => this.onDrag(e.touches[0]), {
                    passive: false,
                });
                document.addEventListener("touchend", () => this.endDrag());

                this.cardLine.addEventListener("wheel", (e) => this.onWheel(e), {
                    passive: false,
                });
                this.cardLine.addEventListener("selectstart", (e) => e.preventDefault());
                this.cardLine.addEventListener("dragstart", (e) => e.preventDefault());

                window.addEventListener("resize", () => this.calculateDimensions());
            }

            startDrag(e) {
                e.preventDefault();

                this.isDragging = true;
                this.isAnimating = false;
                this.lastMouseX = e.clientX;
                this.mouseVelocity = 0;

                const transform = window.getComputedStyle(this.cardLine).transform;
                if (transform !== "none") {
                    const matrix = new DOMMatrix(transform);
                    this.position = matrix.m41;
                }

                this.cardLine.style.animation = "none";
                this.cardLine.classList.add("dragging");

                document.body.style.userSelect = "none";
                document.body.style.cursor = "grabbing";
            }

            onDrag(e) {
                if (!this.isDragging) return;
                e.preventDefault();

                const deltaX = e.clientX - this.lastMouseX;
                this.position += deltaX;
                this.mouseVelocity = deltaX * 60;
                this.lastMouseX = e.clientX;

                this.cardLine.style.transform = `translateX(${this.position}px)`;
                this.updateCardClipping();
            }

            endDrag() {
                if (!this.isDragging) return;

                this.isDragging = false;
                this.cardLine.classList.remove("dragging");

                if (Math.abs(this.mouseVelocity) > this.minVelocity) {
                    this.velocity = Math.abs(this.mouseVelocity);
                    this.direction = this.mouseVelocity > 0 ? 1 : -1;
                } else {
                    this.velocity = 120;
                }

                this.isAnimating = true;
                this.updateSpeedIndicator();

                document.body.style.userSelect = "";
                document.body.style.cursor = "";
            }

            animate() {
                const currentTime = performance.now();
                const deltaTime = (currentTime - this.lastTime) / 1000;
                this.lastTime = currentTime;

                if (this.isAnimating && !this.isDragging) {
                    if (this.velocity > this.minVelocity) {
                        this.velocity *= this.friction;
                    } else {
                        this.velocity = Math.max(this.minVelocity, this.velocity);
                    }

                    this.position += this.velocity * this.direction * deltaTime;
                    this.updateCardPosition();
                    this.updateSpeedIndicator();
                }

                requestAnimationFrame(() => this.animate());
            }

            updateCardPosition() {
                const containerWidth = this.containerWidth;
                const cardLineWidth = this.cardLineWidth;

                if (this.position < -cardLineWidth) {
                    this.position = containerWidth;
                } else if (this.position > containerWidth) {
                    this.position = -cardLineWidth;
                }

                this.cardLine.style.transform = `translateX(${this.position}px)`;
                this.updateCardClipping();
            }

            updateSpeedIndicator() {
                if (!this.speedIndicator) return;
                this.speedIndicator.textContent = Math.round(this.velocity);
            }

            toggleAnimation() {
                this.isAnimating = !this.isAnimating;
                const btn = document.getElementById("scannerToggleBtn");
                if (btn) btn.textContent = this.isAnimating ? "Pause" : "Play";

                if (this.isAnimating) {
                    this.cardLine.style.animation = "none";
                }
            }

            resetPosition() {
                this.position = this.containerWidth;
                this.velocity = 120;
                this.direction = -1;
                this.isAnimating = true;
                this.isDragging = false;

                this.cardLine.style.animation = "none";
                this.cardLine.style.transform = `translateX(${this.position}px)`;
                this.cardLine.classList.remove("dragging");

                this.updateSpeedIndicator();

                const btn = document.getElementById("scannerToggleBtn");
                if (btn) btn.textContent = "Pause";
            }

            changeDirection() {
                this.direction *= -1;
                this.updateSpeedIndicator();
            }

            onWheel(e) {
                e.preventDefault();

                const scrollSpeed = 20;
                const delta = e.deltaY > 0 ? scrollSpeed : -scrollSpeed;

                this.position += delta;
                this.updateCardPosition();
                this.updateCardClipping();
            }

            generateCode(width, height) {
                const randInt = (min, max) =>
                    Math.floor(Math.random() * (max - min + 1)) + min;
                const pick = (arr) => arr[randInt(0, arr.length - 1)];

                const header = [
                    "// compiled preview - scanner demo",
                    "/* generated for visual effect - not executed */",
                    "const SCAN_WIDTH = 8;",
                    "const FADE_ZONE = 35;",
                    "const MAX_PARTICLES = 2500;",
                    "const TRANSITION = 0.05;",
                ];

                const helpers = [
                    "function clamp(n, a, b) { return Math.max(a, Math.min(b, n)); }",
                    "function lerp(a, b, t) { return a + (b - a) * t; }",
                    "const now = () => performance.now();",
                    "function rng(min, max) { return Math.random() * (max - min) + min; }",
                ];

                const particleBlock = (idx) => [
                    `class Particle${idx} {`,
                    "  constructor(x, y, vx, vy, r, a) {",
                    "    this.x = x; this.y = y;",
                    "    this.vx = vx; this.vy = vy;",
                    "    this.r = r; this.a = a;",
                    "  }",
                    "  step(dt) { this.x += this.vx * dt; this.y += this.vy * dt; }",
                    "}",
                ];

                const scannerBlock = [
                    "const scanner = {",
                    "  x: Math.floor(window.innerWidth / 2),",
                    "  width: SCAN_WIDTH,",
                    "  glow: 3.5,",
                    "};",
                    "",
                    "function drawParticle(ctx, p) {",
                    "  ctx.globalAlpha = clamp(p.a, 0, 1);",
                    "  ctx.drawImage(gradient, p.x - p.r, p.y - p.r, p.r * 2, p.r * 2);",
                    "}",
                ];

                const loopBlock = [
                    "function tick(t) {",
                    "  // requestAnimationFrame(tick);",
                    "  const dt = 0.016;",
                    "  // update & render",
                    "}",
                ];

                const misc = [
                    "const state = { intensity: 1.2, particles: MAX_PARTICLES };",
                    "const bounds = { w: window.innerWidth, h: 300 };",
                    "const gradient = document.createElement('canvas');",
                    "const ctx = gradient.getContext('2d');",
                    "ctx.globalCompositeOperation = 'lighter';",
                    "// ascii overlay is masked with a 3-phase gradient",
                ];

                const library = [];
                header.forEach((l) => library.push(l));
                helpers.forEach((l) => library.push(l));
                for (let b = 0; b < 3; b++) particleBlock(b).forEach((l) => library.push(l));
                scannerBlock.forEach((l) => library.push(l));
                loopBlock.forEach((l) => library.push(l));
                misc.forEach((l) => library.push(l));

                for (let i = 0; i < 40; i++) {
                    const n1 = randInt(1, 9);
                    const n2 = randInt(10, 99);
                    library.push(`const v${i} = (${n1} + ${n2}) * 0.${randInt(1, 9)};`);
                }
                for (let i = 0; i < 20; i++) {
                    library.push(
                        `if (state.intensity > ${1 + (i % 3)}) { scanner.glow += 0.01; }`
                    );
                }

                let flow = library.join(" ");
                flow = flow.replace(/\s+/g, " ").trim();
                const totalChars = width * height;
                while (flow.length < totalChars + width) {
                    const extra = pick(library).replace(/\s+/g, " ").trim();
                    flow += " " + extra;
                }

                let out = "";
                let offset = 0;
                for (let row = 0; row < height; row++) {
                    let line = flow.slice(offset, offset + width);
                    if (line.length < width) line = line + " ".repeat(width - line.length);
                    out += line + (row < height - 1 ? "\n" : "");
                    offset += width;
                }
                return out;
            }

            calculateCodeDimensions(cardWidth, cardHeight) {
                const fontSize = 11;
                const lineHeight = 13;
                const charWidth = 6;
                const width = Math.floor(cardWidth / charWidth);
                const height = Math.floor(cardHeight / lineHeight);
                return { width, height, fontSize, lineHeight };
            }

            createCardWrapper(index) {
                const wrapper = document.createElement("div");
                wrapper.className = "card-wrapper";

                const normalCard = document.createElement("div");
                normalCard.className = "card card-normal";

                const cardImages = [
                    "https://cdn.prod.website-files.com/68789c86c8bc802d61932544/689f20b55e654d1341fb06f8_4.1.png",
                    "https://cdn.prod.website-files.com/68789c86c8bc802d61932544/689f20b5a080a31ee7154b19_1.png",
                    "https://cdn.prod.website-files.com/68789c86c8bc802d61932544/689f20b5c1e4919fd69672b8_3.png",
                    "https://cdn.prod.website-files.com/68789c86c8bc802d61932544/689f20b5f6a5e232e7beb4be_2.png",
                    "https://cdn.prod.website-files.com/68789c86c8bc802d61932544/689f20b5bea2f1b07392d936_4.png",
                ];

                const cardImage = document.createElement("img");
                cardImage.className = "card-image";
                cardImage.src = cardImages[index % cardImages.length];
                cardImage.alt = "Credit Card";

                cardImage.onerror = () => {
                    const canvas = document.createElement("canvas");
                    canvas.width = 400;
                    canvas.height = 250;
                    const ctx = canvas.getContext("2d");

                    const gradient = ctx.createLinearGradient(0, 0, 400, 250);
                    gradient.addColorStop(0, "#667eea");
                    gradient.addColorStop(1, "#764ba2");

                    ctx.fillStyle = gradient;
                    ctx.fillRect(0, 0, 400, 250);

                    cardImage.src = canvas.toDataURL();
                };

                normalCard.appendChild(cardImage);

                const asciiCard = document.createElement("div");
                asciiCard.className = "card card-ascii";

                const asciiContent = document.createElement("div");
                asciiContent.className = "ascii-content";

                const { width, height, fontSize, lineHeight } =
                    this.calculateCodeDimensions(400, 250);
                asciiContent.style.fontSize = fontSize + "px";
                asciiContent.style.lineHeight = lineHeight + "px";
                asciiContent.textContent = this.generateCode(width, height);

                asciiCard.appendChild(asciiContent);
                wrapper.appendChild(normalCard);
                wrapper.appendChild(asciiCard);

                return wrapper;
            }

            updateCardClipping() {
                const scannerX = window.innerWidth / 2;
                const scannerWidth = 8;
                const scannerLeft = scannerX - scannerWidth / 2;
                const scannerRight = scannerX + scannerWidth / 2;
                let anyScanningActive = false;

                document.querySelectorAll(".card-wrapper").forEach((wrapper) => {
                    const rect = wrapper.getBoundingClientRect();
                    const cardLeft = rect.left;
                    const cardRight = rect.right;
                    const cardWidth = rect.width;

                    const normalCard = wrapper.querySelector(".card-normal");
                    const asciiCard = wrapper.querySelector(".card-ascii");

                    if (cardLeft < scannerRight && cardRight > scannerLeft) {
                        anyScanningActive = true;
                        const scannerIntersectLeft = Math.max(scannerLeft - cardLeft, 0);
                        const scannerIntersectRight = Math.min(
                            scannerRight - cardLeft,
                            cardWidth
                        );

                        const normalClipRight = (scannerIntersectLeft / cardWidth) * 100;
                        const asciiClipLeft = (scannerIntersectRight / cardWidth) * 100;

                        normalCard.style.setProperty("--clip-right", `${normalClipRight}%`);
                        asciiCard.style.setProperty("--clip-left", `${asciiClipLeft}%`);

                        if (!wrapper.hasAttribute("data-scanned") && scannerIntersectLeft > 0) {
                            wrapper.setAttribute("data-scanned", "true");
                            const scanEffect = document.createElement("div");
                            scanEffect.className = "scan-effect";
                            wrapper.appendChild(scanEffect);
                            setTimeout(() => {
                                if (scanEffect.parentNode) {
                                    scanEffect.parentNode.removeChild(scanEffect);
                                }
                            }, 600);
                        }
                    } else {
                        if (cardRight < scannerLeft) {
                            normalCard.style.setProperty("--clip-right", "100%");
                            asciiCard.style.setProperty("--clip-left", "100%");
                        } else if (cardLeft > scannerRight) {
                            normalCard.style.setProperty("--clip-right", "0%");
                            asciiCard.style.setProperty("--clip-left", "0%");
                        }
                        wrapper.removeAttribute("data-scanned");
                    }
                });

                if (window.setScannerScanning) {
                    window.setScannerScanning(anyScanningActive);
                }
            }

            updateAsciiContent() {
                document.querySelectorAll(".ascii-content").forEach((content) => {
                    if (Math.random() < 0.15) {
                        const { width, height } = this.calculateCodeDimensions(400, 250);
                        content.textContent = this.generateCode(width, height);
                    }
                });
            }

            populateCardLine() {
                this.cardLine.innerHTML = "";
                const cardsCount = 30;
                for (let i = 0; i < cardsCount; i++) {
                    const cardWrapper = this.createCardWrapper(i);
                    this.cardLine.appendChild(cardWrapper);
                }
            }

            startPeriodicUpdates() {
                setInterval(() => {
                    this.updateAsciiContent();
                }, 200);

                const updateClipping = () => {
                    this.updateCardClipping();
                    requestAnimationFrame(updateClipping);
                };
                updateClipping();
            }
        }

        let cardStream;

        function toggleAnimation() {
            if (cardStream) {
                cardStream.toggleAnimation();
            }
        }

        function resetPosition() {
            if (cardStream) {
                cardStream.resetPosition();
            }
        }

        function changeDirection() {
            if (cardStream) {
                cardStream.changeDirection();
            }
        }

        class ParticleSystem {
            constructor() {
                this.scene = null;
                this.camera = null;
                this.renderer = null;
                this.particles = null;
                this.particleCount = 400;
                this.canvas = document.getElementById("particleCanvas");

                this.init();
            }

            init() {
                if (!window.THREE || !this.canvas) return;
                this.scene = new THREE.Scene();

                this.camera = new THREE.OrthographicCamera(
                    -window.innerWidth / 2,
                    window.innerWidth / 2,
                    125,
                    -125,
                    1,
                    1000
                );
                this.camera.position.z = 100;

                this.renderer = new THREE.WebGLRenderer({
                    canvas: this.canvas,
                    alpha: true,
                    antialias: true,
                });
                this.renderer.setSize(window.innerWidth, 250);
                this.renderer.setClearColor(0x000000, 0);

                this.createParticles();

                this.animate();

                window.addEventListener("resize", () => this.onWindowResize());
            }

            createParticles() {
                const geometry = new THREE.BufferGeometry();
                const positions = new Float32Array(this.particleCount * 3);
                const colors = new Float32Array(this.particleCount * 3);
                const sizes = new Float32Array(this.particleCount);
                const velocities = new Float32Array(this.particleCount);

                const canvas = document.createElement("canvas");
                canvas.width = 100;
                canvas.height = 100;
                const ctx = canvas.getContext("2d");

                const half = canvas.width / 2;
                const hue = 217;

                const gradient = ctx.createRadialGradient(half, half, 0, half, half, half);
                gradient.addColorStop(0.025, "#fff");
                gradient.addColorStop(0.1, `hsl(${hue}, 61%, 33%)`);
                gradient.addColorStop(0.25, `hsl(${hue}, 64%, 6%)`);
                gradient.addColorStop(1, "transparent");

                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(half, half, half, 0, Math.PI * 2);
                ctx.fill();

                const texture = new THREE.CanvasTexture(canvas);

                for (let i = 0; i < this.particleCount; i++) {
                    positions[i * 3] = (Math.random() - 0.5) * window.innerWidth * 2;
                    positions[i * 3 + 1] = (Math.random() - 0.5) * 250;
                    positions[i * 3 + 2] = 0;

                    colors[i * 3] = 1;
                    colors[i * 3 + 1] = 1;
                    colors[i * 3 + 2] = 1;

                    const orbitRadius = Math.random() * 200 + 100;
                    sizes[i] = (Math.random() * (orbitRadius - 60) + 60) / 8;

                    velocities[i] = Math.random() * 60 + 30;
                }

                geometry.setAttribute("position", new THREE.BufferAttribute(positions, 3));
                geometry.setAttribute("color", new THREE.BufferAttribute(colors, 3));
                geometry.setAttribute("size", new THREE.BufferAttribute(sizes, 1));

                this.velocities = velocities;

                const alphas = new Float32Array(this.particleCount);
                for (let i = 0; i < this.particleCount; i++) {
                    alphas[i] = (Math.random() * 8 + 2) / 10;
                }
                geometry.setAttribute("alpha", new THREE.BufferAttribute(alphas, 1));
                this.alphas = alphas;

                const material = new THREE.ShaderMaterial({
                    uniforms: {
                        pointTexture: { value: texture },
                        size: { value: 15.0 },
                    },
                    vertexShader: `
                        attribute float alpha;
                        varying float vAlpha;
                        varying vec3 vColor;
                        uniform float size;
                        
                        void main() {
                          vAlpha = alpha;
                          vColor = color;
                          vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
                          gl_PointSize = size;
                          gl_Position = projectionMatrix * mvPosition;
                        }
                    `,
                    fragmentShader: `
                        uniform sampler2D pointTexture;
                        varying float vAlpha;
                        varying vec3 vColor;
                        
                        void main() {
                          gl_FragColor = vec4(vColor, vAlpha) * texture2D(pointTexture, gl_PointCoord);
                        }
                    `,
                    transparent: true,
                    blending: THREE.AdditiveBlending,
                    depthWrite: false,
                    vertexColors: true,
                });

                this.particles = new THREE.Points(geometry, material);
                this.scene.add(this.particles);
            }

            animate() {
                requestAnimationFrame(() => this.animate());

                if (this.particles) {
                    const positions = this.particles.geometry.attributes.position.array;
                    const alphas = this.particles.geometry.attributes.alpha.array;
                    const time = Date.now() * 0.001;

                    for (let i = 0; i < this.particleCount; i++) {
                        positions[i * 3] += this.velocities[i] * 0.016;

                        if (positions[i * 3] > window.innerWidth / 2 + 100) {
                            positions[i * 3] = -window.innerWidth / 2 - 100;
                            positions[i * 3 + 1] = (Math.random() - 0.5) * 250;
                        }

                        positions[i * 3 + 1] += Math.sin(time + i * 0.1) * 0.5;

                        const twinkle = Math.floor(Math.random() * 10);
                        if (twinkle === 1 && alphas[i] > 0) {
                            alphas[i] -= 0.05;
                        } else if (twinkle === 2 && alphas[i] < 1) {
                            alphas[i] += 0.05;
                        }

                        alphas[i] = Math.max(0, Math.min(1, alphas[i]));
                    }

                    this.particles.geometry.attributes.position.needsUpdate = true;
                    this.particles.geometry.attributes.alpha.needsUpdate = true;
                }

                this.renderer.render(this.scene, this.camera);
            }

            onWindowResize() {
                this.camera.left = -window.innerWidth / 2;
                this.camera.right = window.innerWidth / 2;
                this.camera.updateProjectionMatrix();

                this.renderer.setSize(window.innerWidth, 250);
            }

            destroy() {
                if (this.renderer) {
                    this.renderer.dispose();
                }
                if (this.particles) {
                    this.scene.remove(this.particles);
                    this.particles.geometry.dispose();
                    this.particles.material.dispose();
                }
            }
        }

        let particleSystem;

        class ParticleScanner {
            constructor() {
                this.canvas = document.getElementById("scannerCanvas");
                if (!this.canvas) return;
                this.ctx = this.canvas.getContext("2d");
                this.animationId = null;

                this.w = window.innerWidth;
                this.h = 300;
                this.particles = [];
                this.count = 0;
                this.maxParticles = 800;
                this.intensity = 0.8;
                this.lightBarX = this.w / 2;
                this.lightBarWidth = 3;
                this.fadeZone = 60;

                this.scanTargetIntensity = 1.8;
                this.scanTargetParticles = 2500;
                this.scanTargetFadeZone = 35;

                this.scanningActive = false;

                this.baseIntensity = this.intensity;
                this.baseMaxParticles = this.maxParticles;
                this.baseFadeZone = this.fadeZone;

                this.currentIntensity = this.intensity;
                this.currentMaxParticles = this.maxParticles;
                this.currentFadeZone = this.fadeZone;
                this.transitionSpeed = 0.05;

                this.setupCanvas();
                this.createGradientCache();
                this.initParticles();
                this.animate();

                window.addEventListener("resize", () => this.onResize());
            }

            setupCanvas() {
                this.canvas.width = this.w;
                this.canvas.height = this.h;
                this.canvas.style.width = this.w + "px";
                this.canvas.style.height = this.h + "px";
                this.ctx.clearRect(0, 0, this.w, this.h);
            }

            onResize() {
                this.w = window.innerWidth;
                this.lightBarX = this.w / 2;
                this.setupCanvas();
            }

            createGradientCache() {
                this.gradientCanvas = document.createElement("canvas");
                this.gradientCtx = this.gradientCanvas.getContext("2d");
                this.gradientCanvas.width = 16;
                this.gradientCanvas.height = 16;

                const half = this.gradientCanvas.width / 2;
                const gradient = this.gradientCtx.createRadialGradient(
                    half,
                    half,
                    0,
                    half,
                    half,
                    half
                );
                gradient.addColorStop(0, "rgba(255, 255, 255, 1)");
                gradient.addColorStop(0.3, "rgba(196, 181, 253, 0.8)");
                gradient.addColorStop(0.7, "rgba(139, 92, 246, 0.4)");
                gradient.addColorStop(1, "transparent");

                this.gradientCtx.fillStyle = gradient;
                this.gradientCtx.beginPath();
                this.gradientCtx.arc(half, half, half, 0, Math.PI * 2);
                this.gradientCtx.fill();
            }

            random(min, max) {
                if (arguments.length < 2) {
                    max = min;
                    min = 0;
                }
                return Math.floor(Math.random() * (max - min + 1)) + min;
            }

            randomFloat(min, max) {
                return Math.random() * (max - min) + min;
            }

            createParticle() {
                const intensityRatio = this.intensity / this.baseIntensity;
                const speedMultiplier = 1 + (intensityRatio - 1) * 1.2;
                const sizeMultiplier = 1 + (intensityRatio - 1) * 0.7;

                return {
                    x:
                        this.lightBarX +
                        this.randomFloat(-this.lightBarWidth / 2, this.lightBarWidth / 2),
                    y: this.randomFloat(0, this.h),

                    vx: this.randomFloat(0.2, 1.0) * speedMultiplier,
                    vy: this.randomFloat(-0.15, 0.15) * speedMultiplier,

                    radius: this.randomFloat(0.4, 1) * sizeMultiplier,
                    alpha: this.randomFloat(0.6, 1),
                    decay: this.randomFloat(0.005, 0.025) * (2 - intensityRatio * 0.5),
                    originalAlpha: 0,
                    life: 1.0,
                    time: 0,
                    startX: 0,

                    twinkleSpeed: this.randomFloat(0.02, 0.08) * speedMultiplier,
                    twinkleAmount: this.randomFloat(0.1, 0.25),
                };
            }

            initParticles() {
                for (let i = 0; i < this.maxParticles; i++) {
                    const particle = this.createParticle();
                    particle.originalAlpha = particle.alpha;
                    particle.startX = particle.x;
                    this.count++;
                    this.particles[this.count] = particle;
                }
            }

            updateParticle(particle) {
                particle.x += particle.vx;
                particle.y += particle.vy;
                particle.time++;

                particle.alpha =
                    particle.originalAlpha * particle.life +
                    Math.sin(particle.time * particle.twinkleSpeed) * particle.twinkleAmount;

                particle.life -= particle.decay;

                if (particle.x > this.w + 10 || particle.life <= 0) {
                    this.resetParticle(particle);
                }
            }

            resetParticle(particle) {
                particle.x =
                    this.lightBarX +
                    this.randomFloat(-this.lightBarWidth / 2, this.lightBarWidth / 2);
                particle.y = this.randomFloat(0, this.h);
                particle.vx = this.randomFloat(0.2, 1.0);
                particle.vy = this.randomFloat(-0.15, 0.15);
                particle.alpha = this.randomFloat(0.6, 1);
                particle.originalAlpha = particle.alpha;
                particle.life = 1.0;
                particle.time = 0;
                particle.startX = particle.x;
            }

            drawParticle(particle) {
                if (particle.life <= 0) return;

                let fadeAlpha = 1;

                if (particle.y < this.fadeZone) {
                    fadeAlpha = particle.y / this.fadeZone;
                } else if (particle.y > this.h - this.fadeZone) {
                    fadeAlpha = (this.h - particle.y) / this.fadeZone;
                }

                fadeAlpha = Math.max(0, Math.min(1, fadeAlpha));

                this.ctx.globalAlpha = particle.alpha * fadeAlpha;
                this.ctx.drawImage(
                    this.gradientCanvas,
                    particle.x - particle.radius,
                    particle.y - particle.radius,
                    particle.radius * 2,
                    particle.radius * 2
                );
            }

            drawLightBar() {
                const verticalGradient = this.ctx.createLinearGradient(0, 0, 0, this.h);
                verticalGradient.addColorStop(0, "rgba(255, 255, 255, 0)");
                verticalGradient.addColorStop(
                    this.fadeZone / this.h,
                    "rgba(255, 255, 255, 1)"
                );
                verticalGradient.addColorStop(
                    1 - this.fadeZone / this.h,
                    "rgba(255, 255, 255, 1)"
                );
                verticalGradient.addColorStop(1, "rgba(255, 255, 255, 0)");

                this.ctx.globalCompositeOperation = "lighter";

                const targetGlowIntensity = this.scanningActive ? 3.5 : 1;

                if (!this.currentGlowIntensity) this.currentGlowIntensity = 1;

                this.currentGlowIntensity +=
                    (targetGlowIntensity - this.currentGlowIntensity) * this.transitionSpeed;

                const glowIntensity = this.currentGlowIntensity;
                const lineWidth = this.lightBarWidth;
                const glow1Alpha = this.scanningActive ? 1.0 : 0.8;
                const glow2Alpha = this.scanningActive ? 0.8 : 0.6;
                const glow3Alpha = this.scanningActive ? 0.6 : 0.4;

                const coreGradient = this.ctx.createLinearGradient(
                    this.lightBarX - lineWidth / 2,
                    0,
                    this.lightBarX + lineWidth / 2,
                    0
                );
                coreGradient.addColorStop(0, "rgba(255, 255, 255, 0)");
                coreGradient.addColorStop(
                    0.3,
                    `rgba(255, 255, 255, ${0.9 * glowIntensity})`
                );
                coreGradient.addColorStop(0.5, `rgba(255, 255, 255, ${1 * glowIntensity})`);
                coreGradient.addColorStop(
                    0.7,
                    `rgba(255, 255, 255, ${0.9 * glowIntensity})`
                );
                coreGradient.addColorStop(1, "rgba(255, 255, 255, 0)");

                this.ctx.globalAlpha = 1;
                this.ctx.fillStyle = coreGradient;

                const radius = 15;
                this.ctx.beginPath();
                this.ctx.roundRect(
                    this.lightBarX - lineWidth / 2,
                    0,
                    lineWidth,
                    this.h,
                    radius
                );
                this.ctx.fill();

                const glow1Gradient = this.ctx.createLinearGradient(
                    this.lightBarX - lineWidth * 2,
                    0,
                    this.lightBarX + lineWidth * 2,
                    0
                );
                glow1Gradient.addColorStop(0, "rgba(139, 92, 246, 0)");
                glow1Gradient.addColorStop(
                    0.5,
                    `rgba(196, 181, 253, ${0.8 * glowIntensity})`
                );
                glow1Gradient.addColorStop(1, "rgba(139, 92, 246, 0)");

                this.ctx.globalAlpha = glow1Alpha;
                this.ctx.fillStyle = glow1Gradient;

                const glow1Radius = 25;
                this.ctx.beginPath();
                this.ctx.roundRect(
                    this.lightBarX - lineWidth * 2,
                    0,
                    lineWidth * 4,
                    this.h,
                    glow1Radius
                );
                this.ctx.fill();

                const glow2Gradient = this.ctx.createLinearGradient(
                    this.lightBarX - lineWidth * 4,
                    0,
                    this.lightBarX + lineWidth * 4,
                    0
                );
                glow2Gradient.addColorStop(0, "rgba(139, 92, 246, 0)");
                glow2Gradient.addColorStop(
                    0.5,
                    `rgba(139, 92, 246, ${0.4 * glowIntensity})`
                );
                glow2Gradient.addColorStop(1, "rgba(139, 92, 246, 0)");

                this.ctx.globalAlpha = glow2Alpha;
                this.ctx.fillStyle = glow2Gradient;

                const glow2Radius = 35;
                this.ctx.beginPath();
                this.ctx.roundRect(
                    this.lightBarX - lineWidth * 4,
                    0,
                    lineWidth * 8,
                    this.h,
                    glow2Radius
                );
                this.ctx.fill();

                if (this.scanningActive) {
                    const glow3Gradient = this.ctx.createLinearGradient(
                        this.lightBarX - lineWidth * 8,
                        0,
                        this.lightBarX + lineWidth * 8,
                        0
                    );
                    glow3Gradient.addColorStop(0, "rgba(139, 92, 246, 0)");
                    glow3Gradient.addColorStop(0.5, "rgba(139, 92, 246, 0.2)");
                    glow3Gradient.addColorStop(1, "rgba(139, 92, 246, 0)");

                    this.ctx.globalAlpha = glow3Alpha;
                    this.ctx.fillStyle = glow3Gradient;

                    const glow3Radius = 45;
                    this.ctx.beginPath();
                    this.ctx.roundRect(
                        this.lightBarX - lineWidth * 8,
                        0,
                        lineWidth * 16,
                        this.h,
                        glow3Radius
                    );
                    this.ctx.fill();
                }

                this.ctx.globalCompositeOperation = "destination-in";
                this.ctx.globalAlpha = 1;
                this.ctx.fillStyle = verticalGradient;
                this.ctx.fillRect(0, 0, this.w, this.h);
            }

            render() {
                const targetIntensity = this.scanningActive
                    ? this.scanTargetIntensity
                    : this.baseIntensity;
                const targetMaxParticles = this.scanningActive
                    ? this.scanTargetParticles
                    : this.baseMaxParticles;
                const targetFadeZone = this.scanningActive
                    ? this.scanTargetFadeZone
                    : this.baseFadeZone;

                this.currentIntensity +=
                    (targetIntensity - this.currentIntensity) * this.transitionSpeed;
                this.currentMaxParticles +=
                    (targetMaxParticles - this.currentMaxParticles) * this.transitionSpeed;
                this.currentFadeZone +=
                    (targetFadeZone - this.currentFadeZone) * this.transitionSpeed;

                this.intensity = this.currentIntensity;
                this.maxParticles = Math.floor(this.currentMaxParticles);
                this.fadeZone = this.currentFadeZone;

                this.ctx.globalCompositeOperation = "source-over";
                this.ctx.clearRect(0, 0, this.w, this.h);

                this.drawLightBar();

                this.ctx.globalCompositeOperation = "lighter";
                for (let i = 1; i <= this.count; i++) {
                    if (this.particles[i]) {
                        this.updateParticle(this.particles[i]);
                        this.drawParticle(this.particles[i]);
                    }
                }

                const currentIntensity = this.intensity;
                const currentMaxParticles = this.maxParticles;

                if (Math.random() < currentIntensity && this.count < currentMaxParticles) {
                    const particle = this.createParticle();
                    particle.originalAlpha = particle.alpha;
                    particle.startX = particle.x;
                    this.count++;
                    this.particles[this.count] = particle;
                }

                const intensityRatio = this.intensity / this.baseIntensity;

                if (intensityRatio > 1.1 && Math.random() < (intensityRatio - 1.0) * 1.2) {
                    const particle = this.createParticle();
                    particle.originalAlpha = particle.alpha;
                    particle.startX = particle.x;
                    this.count++;
                    this.particles[this.count] = particle;
                }

                if (intensityRatio > 1.3 && Math.random() < (intensityRatio - 1.3) * 1.4) {
                    const particle = this.createParticle();
                    particle.originalAlpha = particle.alpha;
                    particle.startX = particle.x;
                    this.count++;
                    this.particles[this.count] = particle;
                }

                if (intensityRatio > 1.5 && Math.random() < (intensityRatio - 1.5) * 1.8) {
                    const particle = this.createParticle();
                    particle.originalAlpha = particle.alpha;
                    particle.startX = particle.x;
                    this.count++;
                    this.particles[this.count] = particle;
                }

                if (intensityRatio > 2.0 && Math.random() < (intensityRatio - 2.0) * 2.0) {
                    const particle = this.createParticle();
                    particle.originalAlpha = particle.alpha;
                    particle.startX = particle.x;
                    this.count++;
                    this.particles[this.count] = particle;
                }

                if (this.count > currentMaxParticles + 200) {
                    const excessCount = Math.min(15, this.count - currentMaxParticles);
                    for (let i = 0; i < excessCount; i++) {
                        delete this.particles[this.count - i];
                    }
                    this.count -= excessCount;
                }
            }

            animate() {
                this.render();
                this.animationId = requestAnimationFrame(() => this.animate());
            }

            startScanning() {
                this.scanningActive = true;
            }

            stopScanning() {
                this.scanningActive = false;
            }

            setScanningActive(active) {
                this.scanningActive = active;
            }

            getStats() {
                return {
                    intensity: this.intensity,
                    maxParticles: this.maxParticles,
                    currentParticles: this.count,
                    lightBarWidth: this.lightBarWidth,
                    fadeZone: this.fadeZone,
                    scanningActive: this.scanningActive,
                    canvasWidth: this.w,
                    canvasHeight: this.h,
                };
            }

            destroy() {
                if (this.animationId) {
                    cancelAnimationFrame(this.animationId);
                }

                this.particles = [];
                this.count = 0;
            }
        }

        let particleScanner;

        document.addEventListener("DOMContentLoaded", () => {
            if (!document.getElementById("cardStream")) return;
            cardStream = new CardStreamController();
            particleScanner = new ParticleScanner();
            if (window.THREE) {
                particleSystem = new ParticleSystem();
            }

            window.setScannerScanning = (active) => {
                if (particleScanner) {
                    particleScanner.setScanningActive(active);
                }
            };

            window.getScannerStats = () => {
                if (particleScanner) {
                    return particleScanner.getStats();
                }
                return null;
            };
        });
    </script>
@endpush
