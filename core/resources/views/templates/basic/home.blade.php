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

        <section class="relative py-20 border-t border-white/10 bg-slate-950/60">
            <div class="mx-auto max-w-6xl px-6">
                <div class="text-center max-w-3xl mx-auto">
                    <p class="text-xs uppercase tracking-[0.22em] text-[#87c5a6] font-semibold">Rewards</p>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-white">Grow with FlujiPay Rewards</h2>
                    <p class="mt-4 text-slate-300">Invite merchants, unlock milestones, and keep earning as your referral network processes payments.</p>
                </div>

                <div class="mt-12 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-emerald-300/20 bg-slate-900/70 p-6">
                        <h3 class="text-xl font-semibold text-white">$5 per qualified merchant</h3>
                        <p class="mt-2 text-sm text-slate-300">Paid once when each referred merchant completes their first successful sale.</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-300/20 bg-slate-900/70 p-6">
                        <h3 class="text-xl font-semibold text-white">Level 1 discount</h3>
                        <p class="mt-2 text-sm text-slate-300">Get 50% off all plans for 3 months after 10 qualified referrals.</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-300/20 bg-slate-900/70 p-6">
                        <h3 class="text-xl font-semibold text-white">0.5% revenue share</h3>
                        <p class="mt-2 text-sm text-slate-300">At 50+ qualified referrals, earn ongoing 0.5% share on direct referral transactions.</p>
                    </div>
                </div>

                <div class="mt-10 text-center">
                    <a href="{{ route('signup') }}" class="inline-flex items-center justify-center rounded-full bg-[#87c5a6] px-8 py-3 text-sm font-semibold text-slate-900 hover:bg-[#9ad8bf] transition-colors">
                        Start earning
                    </a>
                </div>
            </div>
        </section>

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
