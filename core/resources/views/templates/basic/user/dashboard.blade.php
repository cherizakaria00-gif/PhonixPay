@extends($activeTemplate . 'layouts.master')

@php
    $showHeaderBalance = true;

    $todayCardPresetsCollection = collect($todayCardPresets ?? []);
    $todayCardDefault = $todayCardPresetsCollection->get($todayCardRange ?? 'today', $todayCardPresetsCollection->get('today', []));
    $grossDisplay = (float) data_get($todayCardDefault, 'current_total', 0);
    $yesterdayDisplay = (float) data_get($todayCardDefault, 'compare_total', 0);
    $todayCardTitle = (string) data_get($todayCardDefault, 'title', 'Today');
    $todayCompareLabel = (string) data_get($todayCardDefault, 'compare_label', 'Previous');
    $todayRangeHint = (string) data_get($todayCardDefault, 'range_hint', '00:00 - 23:59');
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
            <h2 id="stripeTodayTitle">{{ $todayCardTitle }}</h2>
        </div>

        <div class="stripe-today-body">
            <div class="stripe-today-main">
                <div class="stripe-today-metrics">
                    <div class="stripe-metric">
                        <label for="stripeTodayRangeSelect" class="stripe-metric-label d-inline-flex align-items-center gap-1">
                            Gross volume <i class="las la-angle-down"></i>
                        </label>
                        <select id="stripeTodayRangeSelect" class="form-control form-control-sm mt-2" style="max-width: 180px;">
                            <option value="today" @selected(($todayCardRange ?? 'today') === 'today')>Today</option>
                            <option value="yesterday" @selected(($todayCardRange ?? 'today') === 'yesterday')>Yesterday</option>
                            <option value="15" @selected(($todayCardRange ?? 'today') === '15')>Last 15 days</option>
                            <option value="30" @selected(($todayCardRange ?? 'today') === '30')>Last 1 month</option>
                        </select>
                        <div class="stripe-metric-value" id="stripeTodayCurrentValue">{{ showAmount($grossDisplay) }}</div>
                        <div class="stripe-metric-sub" id="stripeTodayRangeHint">{{ $todayRangeHint }}</div>
                    </div>
                    <div class="stripe-metric">
                        <span class="stripe-metric-label" id="stripeCompareLabel">{{ $todayCompareLabel }}</span>
                        <div class="stripe-metric-value" id="stripeTodayCompareValue">{{ showAmount($yesterdayDisplay) }}</div>
                    </div>
                </div>

                <div class="stripe-chart-wrap">
                    <div id="stripeChartTooltip" class="stripe-chart-tooltip d-none"></div>
                    <svg id="stripeTodayChart" viewBox="0 0 900 230" preserveAspectRatio="none" aria-label="Today chart">
                        <path id="stripeYesterdayPath" class="line-yesterday" d="" />
                        <path id="stripeTodayPath" class="line-today" d="" />
                        <g id="stripeTodayPoints"></g>
                    </svg>
                    <div class="stripe-chart-time" id="stripeChartTimeLabel">{{ $todayRangeHint }}</div>
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
                <input type="hidden" name="today_range" value="{{ $todayCardRange ?? 'today' }}">
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

@push('style')
<style>
    .stripe-chart-wrap { position: relative; }
    .stripe-chart-tooltip {
        position: absolute;
        top: 0;
        left: 0;
        transform: translate(-50%, -120%);
        background: #0f172a;
        color: #fff;
        border-radius: 8px;
        font-size: 12px;
        line-height: 1.2;
        padding: 8px 10px;
        pointer-events: none;
        white-space: nowrap;
        z-index: 5;
        box-shadow: 0 10px 24px rgba(2, 6, 23, .28);
    }
    .stripe-chart-point {
        fill: #635bff;
        stroke: #fff;
        stroke-width: 2;
        cursor: pointer;
    }
</style>
@endpush

@push('script')
<script>
    (function () {
        const todayCardPresets = @json($todayCardPresets ?? []);
        const currencyCode = @json(gs('cur_text'));
        const defaultRange = @json($todayCardRange ?? 'today');
        const splitLabels = @json($paymentLinkLabels);
        const paymentLinkSeries = @json($paymentLinkValues);
        const pluginDirectSeries = @json($pluginDirectValues);

        const svg = document.getElementById('stripeTodayChart');
        const todayPath = document.getElementById('stripeTodayPath');
        const yesterdayPath = document.getElementById('stripeYesterdayPath');
        const pointsWrap = document.getElementById('stripeTodayPoints');
        const tooltip = document.getElementById('stripeChartTooltip');
        const rangeSelect = document.getElementById('stripeTodayRangeSelect');
        const titleEl = document.getElementById('stripeTodayTitle');
        const compareLabelEl = document.getElementById('stripeCompareLabel');
        const currentValueEl = document.getElementById('stripeTodayCurrentValue');
        const compareValueEl = document.getElementById('stripeTodayCompareValue');
        const hintEl = document.getElementById('stripeTodayRangeHint');
        const chartTimeEl = document.getElementById('stripeChartTimeLabel');

        if (!svg || !todayPath || !yesterdayPath || !pointsWrap || !rangeSelect) return;

        const width = 900;
        const height = 230;
        const money = (value) => {
            const safe = Number(value || 0);
            return `$${safe.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currencyCode}`;
        };

        function buildPath(series, max) {
            const stepX = width / Math.max(series.length - 1, 1);
            return series.map((val, idx) => {
                const x = idx * stepX;
                const y = height - ((val / max) * (height - 20)) - 10;
                return `${idx === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${y.toFixed(2)}`;
            }).join(' ');
        }

        function animatePath(pathEl) {
            const length = pathEl.getTotalLength();
            pathEl.style.strokeDasharray = `${length}`;
            pathEl.style.strokeDashoffset = `${length}`;
            pathEl.getBoundingClientRect();
            pathEl.style.transition = 'stroke-dashoffset 560ms ease';
            pathEl.style.strokeDashoffset = '0';
        }

        function hideTooltip() {
            if (!tooltip) return;
            tooltip.classList.add('d-none');
        }

        function renderToday(rangeKey) {
            const preset = todayCardPresets[rangeKey] || todayCardPresets.today;
            if (!preset) return;

            const currentSeriesRaw = Array.isArray(preset.current_series) ? preset.current_series : [];
            const compareSeriesRaw = Array.isArray(preset.compare_series) ? preset.compare_series : [];
            const currentSeries = currentSeriesRaw.map(item => Number(item.amount || 0));
            const compareSeries = compareSeriesRaw.map(item => Number(item.amount || 0));
            const labels = currentSeriesRaw.map(item => item.label || '');

            const max = Math.max(...currentSeries, ...compareSeries, 1);
            todayPath.setAttribute('d', buildPath(currentSeries, max));
            yesterdayPath.setAttribute('d', buildPath(compareSeries, max));
            animatePath(todayPath);
            animatePath(yesterdayPath);

            pointsWrap.innerHTML = '';
            const stepX = width / Math.max(currentSeries.length - 1, 1);

            currentSeries.forEach((val, idx) => {
                const x = idx * stepX;
                const y = height - ((val / max) * (height - 20)) - 10;
                const point = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                point.setAttribute('cx', x.toFixed(2));
                point.setAttribute('cy', y.toFixed(2));
                point.setAttribute('r', '4');
                point.setAttribute('class', 'stripe-chart-point');
                point.dataset.label = labels[idx] || '';
                point.dataset.value = String(val);
                point.dataset.x = x.toFixed(2);
                point.dataset.y = y.toFixed(2);

                point.addEventListener('mouseenter', () => {
                    if (!tooltip) return;
                    tooltip.classList.remove('d-none');
                    tooltip.innerHTML = `${point.dataset.label}<br><strong>${money(point.dataset.value)}</strong>`;
                });

                point.addEventListener('mousemove', () => {
                    if (!tooltip) return;
                    const px = Number(point.dataset.x || 0) / width;
                    const py = Number(point.dataset.y || 0) / height;
                    tooltip.style.left = `${(px * 100).toFixed(2)}%`;
                    tooltip.style.top = `${(py * 100).toFixed(2)}%`;
                });

                point.addEventListener('mouseleave', hideTooltip);
                pointsWrap.appendChild(point);
            });

            if (titleEl) titleEl.textContent = preset.title || 'Today';
            if (compareLabelEl) compareLabelEl.textContent = preset.compare_label || 'Previous';
            if (currentValueEl) currentValueEl.textContent = money(preset.current_total || 0);
            if (compareValueEl) compareValueEl.textContent = money(preset.compare_total || 0);
            if (hintEl) hintEl.textContent = preset.range_hint || '';
            if (chartTimeEl) chartTimeEl.textContent = preset.range_hint || '';
        }

        rangeSelect.addEventListener('change', function () {
            renderToday(this.value);
        });

        renderToday(defaultRange);

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
