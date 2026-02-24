@extends($activeTemplate.'layouts.app')

@push('style')
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue.'user/css/main.css') }}">
    <style>
        .policy-page {
            min-height: 100vh;
            background: #f8fafc;
        }
        .policy-topbar {
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
            padding: 14px 0;
        }
        .policy-topbar__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .policy-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #ffffff;
            font-weight: 700;
            font-size: 18px;
            letter-spacing: 0.2px;
        }
        .policy-brand img {
            height: 36px;
        }
        .policy-domain {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }
        .policy-domain:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.18);
        }
        .policy-hero {
            padding: 36px 0 24px;
        }
        .policy-hero__card {
            background: #ffffff;
            border-radius: 18px;
            padding: 26px 28px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }
        .policy-hero__badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #eef2ff;
            color: #1d4ed8;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .policy-hero__title {
            margin: 12px 0 8px;
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
        }
        .policy-hero__subtitle {
            margin: 0 0 14px;
            color: #64748b;
            font-size: 15px;
        }
        .policy-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #1d4ed8;
            font-weight: 600;
            text-decoration: none;
        }
        .policy-main {
            padding: 18px 0 60px;
        }
        .policy-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        }
        .policy-content {
            color: #334155;
            font-size: 15px;
            line-height: 1.8;
        }
        .policy-content h1,
        .policy-content h2,
        .policy-content h3,
        .policy-content h4 {
            color: #0f172a;
            font-weight: 700;
            margin-top: 22px;
            margin-bottom: 10px;
        }
        .policy-content p {
            margin-bottom: 14px;
        }
        .policy-content ul,
        .policy-content ol {
            padding-left: 20px;
            margin-bottom: 16px;
        }
        .policy-content li {
            margin-bottom: 8px;
        }
        .policy-content a {
            color: #1d4ed8;
            font-weight: 600;
        }
        .policy-footer {
            padding: 24px 0 40px;
            color: #64748b;
            font-size: 13px;
        }
        .policy-footer__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        @media (max-width: 575px) {
            .policy-hero__card,
            .policy-card {
                padding: 20px;
            }
            .policy-hero__title {
                font-size: 22px;
            }
            .policy-topbar__inner {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('app')
<div class="policy-page">
    <header class="policy-topbar">
        <div class="container policy-topbar__inner">
            <a href="{{ route('home') }}" class="policy-brand">
                <img src="{{ siteLogo() }}" alt="PhonixPay">
                <span>PhonixPay</span>
            </a>
            <a href="https://phonixpay.com" class="policy-domain" target="_blank" rel="noopener">
                phonixpay.com
            </a>
        </div>
    </header>

    <section class="policy-hero">
        <div class="container">
            <div class="policy-hero__card">
                <span class="policy-hero__badge">Legal</span>
                <h1 class="policy-hero__title">{{ __($pageTitle) }}</h1>
                <p class="policy-hero__subtitle">Official PhonixPay policy page for services provided at phonixpay.com.</p>
                <a href="{{ route('home') }}" class="policy-back">Back to PhonixPay</a>
            </div>
        </div>
    </section>

    <main class="policy-main">
        <div class="container">
            <article class="policy-card">
                <div class="policy-content">
                    @php
                        echo @$policy->data_values->details
                    @endphp
                </div>
            </article>
        </div>
    </main>

    <footer class="policy-footer">
        <div class="container policy-footer__inner">
            <span>© {{ date('Y') }} PhonixPay. All rights reserved.</span>
            <span>phonixpay.com</span>
        </div>
    </footer>
</div>
@endsection
