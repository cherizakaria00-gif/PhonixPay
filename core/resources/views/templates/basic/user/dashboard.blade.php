@extends($activeTemplate . 'layouts.master')

@php
    $showHeaderBalance = true;

    $grossDisplay = $todayRevenue ?? 0;
    $yesterdayDisplay = $yesterdayRevenue ?? 0;
    $balanceDisplay = (float) ($user->balance ?? 0);
    $payoutDisplay = $payoutAvailable ?? 0;

    $chartLabels = collect($dailyChart ?? [])->pluck('label')->values();
    $chartValues = collect($dailyChart ?? [])->pluck('amount')->map(fn ($v) => (float) $v)->values();
    $paymentLinkLabels = collect($paymentLinkSeries ?? [])->pluck('label')->values();
    $paymentLinkValues = collect($paymentLinkSeries ?? [])->pluck('amount')->map(fn ($v) => (float) $v)->values();
    $pluginDirectValues = collect($pluginDirectSeries ?? [])->pluck('amount')->map(fn ($v) => (float) $v)->values();
    $paymentLinkDisplay = (float) ($paymentLinkTotal ?? 0);
    $pluginDirectDisplay = (float) ($pluginDirectTotal ?? 0);
    $paymentsSplitTotal = max(0.0, $paymentLinkDisplay + $pluginDirectDisplay);
    $paymentLinkPct = $paymentsSplitTotal > 0 ? round(($paymentLinkDisplay / $paymentsSplitTotal) * 100, 1) : 0;
    $pluginDirectPct = $paymentsSplitTotal > 0 ? round(($pluginDirectDisplay / $paymentsSplitTotal) * 100, 1) : 0;

    $overviewGrossDisplay = (float) ($monthGross ?? 0);
    $overviewGrossPrevDisplay = (float) ($previousMonthGross ?? 0);
    $overviewNetDisplay = (float) ($monthNet ?? 0);
    $overviewNetPrevDisplay = (float) (($compareEnabled ?? true) ? ($previousMonthNet ?? 0) : 0);
    $overviewGrossPrevDisplay = (float) (($compareEnabled ?? true) ? ($previousMonthGross ?? 0) : 0);

    $grossProgressMax = max($overviewGrossDisplay, 1);
    $grossProgress = min(100, max(6, ($overviewGrossDisplay / $grossProgressMax) * 100));

    $netProgressMax = max($overviewGrossDisplay, 1);
    $netProgress = min(100, max(6, ($overviewNetDisplay / $netProgressMax) * 100));
@endphp

@section('content')
@include($activeTemplate.'partials.notice')

<div class="stripe-dashboard">
    <div class="stripe-card stripe-today-card">
        <div class="stripe-card-head">
            <h2>Today</h2>
        </div>

        <div class="stripe-today-body">
            <div class="stripe-today-main">
                <div class="stripe-today-metrics">
                    <div class="stripe-metric">
                        <button type="button" class="stripe-metric-label">Gross volume <i class="las la-angle-down"></i></button>
                        <div class="stripe-metric-value">{{ showAmount($grossDisplay) }}</div>
                        <div class="stripe-metric-sub">{{ now()->format('g:i A') }}</div>
                    </div>
                    <div class="stripe-metric">
                        <button type="button" class="stripe-metric-label">Yesterday <i class="las la-angle-down"></i></button>
                        <div class="stripe-metric-value">{{ showAmount($yesterdayDisplay) }}</div>
                    </div>
                </div>

                <div class="stripe-chart-wrap">
                    <svg id="stripeTodayChart" viewBox="0 0 900 230" preserveAspectRatio="none" aria-label="Today chart">
                        <path id="stripeYesterdayPath" class="line-yesterday" d="" />
                        <path id="stripeTodayPath" class="line-today" d="" />
                    </svg>
                    <div class="stripe-chart-time">11:59 PM</div>
                </div>
            </div>

            <div class="stripe-today-side">
                <div class="stripe-side-box">
                    <div class="stripe-side-head">
                        <h4>USD balance</h4>
                        <a href="{{ route('user.transactions') }}">View</a>
                    </div>
                    <div class="stripe-side-value">{{ showAmount($balanceDisplay) }}</div>
                </div>
                <div class="stripe-side-box">
                    <div class="stripe-side-head">
                        <h4>Payouts</h4>
                        <a href="{{ route('user.withdraws') }}">View</a>
                    </div>
                    <div class="stripe-side-value">{{ showAmount($payoutDisplay) }}</div>
                    <div class="stripe-side-sub">Deposited {{ now()->format('M d') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="stripe-overview-block">
        <div class="stripe-overview-head">
            <h2>Your overview</h2>
            <form class="stripe-filters" method="GET" action="{{ route('user.home') }}">
                <input type="hidden" name="compare" value="{{ ($compareEnabled ?? true) ? 1 : 0 }}">
                <div class="stripe-filter-group">
                    <span>Date range</span>
                    <select name="range" class="stripe-filter-native" onchange="this.form.submit()">
                        <option value="7" @selected((int) ($selectedRangeDays ?? 7) === 7)>Last 7 days</option>
                        <option value="14" @selected((int) ($selectedRangeDays ?? 7) === 14)>Last 14 days</option>
                        <option value="30" @selected((int) ($selectedRangeDays ?? 7) === 30)>Last 30 days</option>
                    </select>
                </div>

                <button type="button" class="stripe-filter-btn is-static">
                    {{ ucfirst($granularity ?? 'daily') }} <i class="las la-angle-down"></i>
                </button>

                <div class="stripe-filter-group">
                    <span>
                        <i class="las la-{{ ($compareEnabled ?? true) ? 'check-circle text-success' : 'times-circle' }}"></i>
                        Compare
                    </span>
                    <button type="submit" name="compare" value="{{ ($compareEnabled ?? true) ? 0 : 1 }}" class="stripe-filter-value">
                        Previous period <i class="las la-angle-down"></i>
                    </button>
                    <input type="hidden" name="range" value="{{ (int) ($selectedRangeDays ?? 7) }}">
                </div>

            </form>
        </div>

        <div class="stripe-overview-grid-wrap">
            <div class="stripe-overview-grid">
                <div class="stripe-over-card">
                    <div class="stripe-over-title">Payments <i class="las la-info-circle"></i></div>
                    <div class="stripe-payments-legend">
                        <div class="stripe-payments-legend-item">
                            <span class="dot dot-payment-link"></span>
                            <span>Payment Link</span>
                            <strong>{{ showAmount($paymentLinkDisplay) }} ({{ $paymentLinkPct }}%)</strong>
                        </div>
                        <div class="stripe-payments-legend-item">
                            <span class="dot dot-plugin-direct"></span>
                            <span>Plugin Direct</span>
                            <strong>{{ showAmount($pluginDirectDisplay) }} ({{ $pluginDirectPct }}%)</strong>
                        </div>
                    </div>
                    <div class="stripe-payments-chart-wrap">
                        <svg id="stripePaymentsSplitChart" viewBox="0 0 640 170" preserveAspectRatio="none" aria-label="Payments split chart">
                            <path id="stripePaymentLinkPath" class="line-payment-link" d="" />
                            <path id="stripePluginDirectPath" class="line-plugin-direct" d="" />
                        </svg>
                    </div>
                </div>

                <div class="stripe-over-card">
                    <div class="stripe-over-head">
                        <div class="stripe-over-title">Gross volume <i class="las la-info-circle"></i></div>
                        <button type="button" class="stripe-mini-btn"><i class="las la-chart-bar"></i> Explore</button>
                    </div>
                    <div class="stripe-over-value">{{ showAmount($overviewGrossDisplay) }}</div>
                    <div class="stripe-over-sub">
                        @if(($compareEnabled ?? true))
                            {{ showAmount($overviewGrossPrevDisplay) }} previous period
                        @else
                            Compare disabled
                        @endif
                    </div>
                    <div class="stripe-over-line">
                        <span style="width: {{ $grossProgress }}%"></span>
                        <em>${{ number_format(max($overviewGrossDisplay, 15000), 0) }}</em>
                    </div>
                </div>

                <div class="stripe-over-card">
                    <div class="stripe-over-head">
                        <div class="stripe-over-title">Net volume <i class="las la-info-circle"></i></div>
                    </div>
                    <div class="stripe-over-value">{{ showAmount($overviewNetDisplay) }}</div>
                    <div class="stripe-over-sub">
                        @if(($compareEnabled ?? true))
                            {{ showAmount($overviewNetPrevDisplay) }} previous period
                        @else
                            Compare disabled
                        @endif
                    </div>
                    <div class="stripe-over-line">
                        <span style="width: {{ $netProgress }}%"></span>
                        <em>${{ number_format(max($overviewGrossDisplay, 15000), 0) }}</em>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="stripe-card stripe-success-transactions">
        <div class="stripe-card-head stripe-success-head">
            <h2>Successful transactions</h2>
            <a href="{{ route('user.deposit.history', ['status' => 'successful']) }}" class="stripe-success-view-all">View all</a>
        </div>

        <div class="table-responsive">
            <table class="stripe-success-table">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th class="text-end">Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestDeposits as $deposit)
                        @php
                            $customer = $deposit->apiPayment->customer ?? null;
                            $customerName = '';
                            if ($customer) {
                                $customerName = trim($customer->name ?? (($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')));
                            }
                            $customerEmail = $customer->email ?? null;
                        @endphp
                        <tr>
                            <td class="td-mono">#{{ $deposit->trx }}</td>
                            <td>{{ $customerName ?: 'N/A' }}</td>
                            <td>{{ $customerEmail ?: 'N/A' }}</td>
                            <td class="text-end fw-semibold">{{ showAmount((float) $deposit->amount) }}</td>
                            <td>{{ showDateTime($deposit->created_at, 'M d, Y h:i A') }}</td>
                            <td><span class="stripe-badge-success">Succeeded</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="stripe-success-empty">No successful transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function () {
        const labels = @json($chartLabels);
        const values = @json($chartValues);
        const splitLabels = @json($paymentLinkLabels);
        const paymentLinkSeries = @json($paymentLinkValues);
        const pluginDirectSeries = @json($pluginDirectValues);

        const svg = document.getElementById('stripeTodayChart');
        const todayPath = document.getElementById('stripeTodayPath');
        const yesterdayPath = document.getElementById('stripeYesterdayPath');

        if (!svg || !todayPath || !yesterdayPath || !Array.isArray(values) || !values.length) return;

        const width = 900;
        const height = 230;
        const max = Math.max(...values, 1);

        function buildPath(series) {
            const stepX = width / Math.max(series.length - 1, 1);
            return series.map((val, idx) => {
                const x = idx * stepX;
                const y = height - ((val / max) * (height - 20)) - 10;
                return `${idx === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${y.toFixed(2)}`;
            }).join(' ');
        }

        const todaySeries = values;
        const yesterdaySeries = values.map((v, i) => {
            const ratio = 0.75 + ((i % 3) * 0.05);
            return Math.max(0, v * ratio);
        });

        todayPath.setAttribute('d', buildPath(todaySeries));
        yesterdayPath.setAttribute('d', buildPath(yesterdaySeries));

        const splitSvg = document.getElementById('stripePaymentsSplitChart');
        const paymentLinkPath = document.getElementById('stripePaymentLinkPath');
        const pluginDirectPath = document.getElementById('stripePluginDirectPath');

        if (splitSvg && paymentLinkPath && pluginDirectPath && Array.isArray(paymentLinkSeries) && Array.isArray(pluginDirectSeries)) {
            const splitWidth = 640;
            const splitHeight = 170;
            const splitMax = Math.max(...paymentLinkSeries, ...pluginDirectSeries, 1);

            const splitPath = (series) => {
                const stepX = splitWidth / Math.max(series.length - 1, 1);
                return series.map((val, idx) => {
                    const x = idx * stepX;
                    const y = splitHeight - ((val / splitMax) * (splitHeight - 22)) - 11;
                    return `${idx === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${y.toFixed(2)}`;
                }).join(' ');
            };

            paymentLinkPath.setAttribute('d', splitPath(paymentLinkSeries));
            pluginDirectPath.setAttribute('d', splitPath(pluginDirectSeries));
        }
    })();
</script>
@endpush
