@extends($activeTemplate.'layouts.app')

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

        .finisher-header {
            position: relative;
            z-index: 0;
        }

        .finisher-header > * {
            position: relative;
            z-index: 1;
        }

        .finisher-header #finisher-canvas {
            z-index: 0 !important;
        }
    </style>
@endpush

@section('app')
<div class="min-h-screen bg-[#020618] text-white font-sans selection:bg-[#87c5a6]/30">
    <main>
        <section class="relative overflow-hidden pt-28 pb-24 sm:pt-36 sm:pb-32 finisher-header">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(135,197,166,0.18),transparent_55%)]"></div>
            <div class="absolute inset-x-0 top-0 -z-10 h-40 bg-gradient-to-b from-black/60 to-transparent"></div>
            <div class="absolute inset-0 -z-10 opacity-25 bg-[radial-gradient(#87c5a6_1px,transparent_1px)] [background-size:18px_18px]"></div>

            <div class="mx-auto max-w-4xl px-6 text-center">
                <span class="inline-flex items-center gap-2 rounded-full border border-[#87c5a6]/30 bg-[#87c5a6]/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-[#87c5a6]">
                    Powered by Fluji Official
                </span>

                <h1 class="mt-8 text-4xl font-bold tracking-tight text-white sm:text-6xl lg:text-7xl">
                    New Payment Technology
                    <span class="block text-[#87c5a6]">Powered by Fluji Official</span>
                </h1>

                <p class="mt-5 text-base sm:text-lg text-slate-300">
                    A promotional landing experience built to showcase FlujiPay as the official payment power.
                </p>

                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <a href="https://fluji.com" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-full bg-[#87c5a6] px-7 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-emerald-500/20 hover:bg-[#9ad8bf] transition-colors">
                        Visit Fluji.com
                    </a>
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/5 px-7 py-3 text-sm font-semibold text-white hover:border-[#87c5a6]/60 hover:text-[#87c5a6] transition-colors">
                        Contact Sales
                    </a>
                </div>
            </div>
        </section>

        @if(($plans ?? collect())->count())
            <section class="pb-20 sm:pb-28">
                <div class="mx-auto max-w-7xl px-6">
                    <div class="mx-auto max-w-3xl text-center">
                        <h2 class="text-3xl font-bold sm:text-5xl">Plans that scale with you</h2>
                        <p class="mt-3 text-slate-300">Choose the right plan for your transaction volume and payout needs.</p>
                    </div>

                    <div class="mt-10 grid gap-5 lg:grid-cols-4 md:grid-cols-2">
                        @foreach($plans as $plan)
                            @php
                                $isStarter = $plan->slug === 'starter';
                                $payoutLabel = match($plan->payout_frequency) {
                                    'twice_weekly' => '2x per week (Tue/Fri)',
                                    'every_2_days' => 'Every 2 days',
                                    default => 'Every 7 days',
                                };
                                $ctaText = $isStarter ? 'Get Started Free' : 'Start with ' . $plan->name;
                                $features = $plan->features ?? [];
                            @endphp
                            <article class="rounded-3xl border border-white/15 bg-white/[0.04] p-6 backdrop-blur-md">
                                <h3 class="text-xl font-semibold">{{ $plan->name }}</h3>
                                <p class="mt-1 text-3xl font-bold">${{ number_format($plan->price_monthly_cents / 100, 0) }}<span class="text-sm font-medium text-slate-300">/month</span></p>

                                <ul class="mt-5 space-y-2 text-sm text-slate-200">
                                    <li><span class="text-[#87c5a6]">Limit:</span> {{ $plan->tx_limit_monthly ?? 'Unlimited' }}</li>
                                    <li><span class="text-[#87c5a6]">Fees:</span> {{ number_format($plan->fee_percent, 2) }}% + ${{ number_format($plan->fee_fixed, 2) }}</li>
                                    <li><span class="text-[#87c5a6]">Payout:</span> {{ $payoutLabel }}</li>
                                    <li><span class="text-[#87c5a6]">Support:</span> {{ implode(', ', $plan->support_channels ?? ['email']) }}</li>
                                    <li><span class="text-[#87c5a6]">Notifications:</span> {{ implode(', ', $plan->notification_channels ?? ['push']) }}</li>
                                    <li><span class="text-[#87c5a6]">Payment Links:</span> {{ ($features['payment_links'] ?? false) ? 'Enabled' : 'Not included' }}</li>
                                </ul>

                                <a href="{{ route('user.register') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-[#87c5a6] px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-[#9ad8bf]">
                                    {{ $ctaText }}
                                </a>
                            </article>
                        @endforeach
                    </div>

                    <p class="mt-6 text-center text-xs text-slate-400">Fees apply per successful transaction.</p>
                </div>
            </section>
        @endif
    </main>
</div>
@endsection

@push('script')
    <script src="{{ asset('assets/global/js/finisher-header.es5.min.js') }}" type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.FinisherHeader) {
                new FinisherHeader({
                    "count": 100,
                    "size": {
                        "min": 2,
                        "max": 6,
                        "pulse": 0.2
                    },
                    "speed": {
                        "x": {
                            "min": 0,
                            "max": 0.4
                        },
                        "y": {
                            "min": 0,
                            "max": 0.6
                        }
                    },
                    "colors": {
                        "background": "#020618",
                        "particles": [
                            "#87c5a6",
                            "#87c5a6",
                            "#87c5a6"
                        ]
                    },
                    "blending": "lighten",
                    "opacity": {
                        "center": 1,
                        "edge": 0
                    },
                    "skew": -1.9,
                    "shapes": [
                        "c"
                    ]
                });
            }
        });
    </script>
@endpush
