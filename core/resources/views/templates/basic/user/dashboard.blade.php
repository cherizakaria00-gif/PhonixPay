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
                        <div class="stripe-metric-head-inline">
                            <span class="stripe-metric-label m-0">Gross volume</span>
                            <select id="stripeTodayRangeSelect" class="stripe-range-select-minimal" aria-label="Gross volume period">
                                <option value="today" @selected(($todayCardRange ?? 'today') === 'today')>Today</option>
                                <option value="yesterday" @selected(($todayCardRange ?? 'today') === 'yesterday')>Yesterday</option>
                                <option value="15" @selected(($todayCardRange ?? 'today') === '15')>Last 15 days</option>
                                <option value="30" @selected(($todayCardRange ?? 'today') === '30')>Last 1 month</option>
                            </select>
                        </div>
                        <select id="stripeTodayRangeSelectLegacy" class="d-none">
                            <option value="today" @selected(($todayCardRange ?? 'today') === 'today')>Today</option>
                            <option value="yesterday" @selected(($todayCardRange ?? 'today') === 'yesterday')>Yesterday</option>
                            <option value="15" @selected(($todayCardRange ?? 'today') === '15')>Last 15 days</option>
                            <option value="30" @selected(($todayCardRange ?? 'today') === '30')>Last 1 month</option>
                        </select>
                        <div class="stripe-metric-value" id="stripeTodayCurrentValue">{{ showAmount($grossDisplay) }}</div>
                        <div class="stripe-metric-sub" id="stripeTodayRangeHint">{{ $todayRangeHint }}</div>
                    </div>
                    <div class="stripe-metric">
                        <div class="stripe-metric-compare-head">
                            <span class="stripe-metric-label" id="stripeCompareLabel">{{ $todayCompareLabel }}</span>
                            <i class="las la-angle-down" aria-hidden="true"></i>
                        </div>
                        <div class="stripe-metric-value" id="stripeTodayCompareValue">{{ showAmount($yesterdayDisplay) }}</div>
                    </div>
                </div>

                <div class="stripe-chart-wrap">
                    <div id="stripeChartTooltip" class="stripe-chart-tooltip d-none"></div>
                    <svg id="stripeTodayChart" viewBox="0 0 900 230" preserveAspectRatio="none" aria-label="Today chart">
                        <path id="stripeYesterdayPath" class="line-yesterday" d="" />
                        <path id="stripeTodayPath" class="line-today" d="" />
                        <line id="stripeHoverGuide" class="stripe-hover-guide d-none" x1="0" y1="8" x2="0" y2="212" />
                        <circle id="stripeCompareDot" class="stripe-compare-dot d-none" cx="0" cy="0" r="5" />
                        <circle id="stripeCurrentDot" class="stripe-current-dot d-none" cx="0" cy="0" r="5" />
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
        transform: translate(-28%, -18%);
        min-width: 290px;
        background: #ffffff;
        color: #0f172a;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 12px;
        line-height: 1.2;
        padding: 10px 14px 12px;
        pointer-events: none;
        white-space: nowrap;
        z-index: 5;
        box-shadow: 0 10px 22px rgba(2, 6, 23, 0.14);
    }
    .stripe-tooltip-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid #dbe3ef;
        margin-bottom: 8px;
        font-size: 17px;
        font-weight: 700;
        color: #374151;
    }
    .stripe-tooltip-badge {
        font-size: 14px;
        line-height: 1;
        font-weight: 600;
        border-radius: 8px;
        padding: 5px 10px;
        border: 1px solid transparent;
    }
    .stripe-tooltip-badge.is-pos {
        color: #166534;
        background: #dcfce7;
        border-color: #86efac;
    }
    .stripe-tooltip-badge.is-neg {
        color: #c2410c;
        background: #fef3c7;
        border-color: #fcd34d;
    }
    .stripe-tooltip-badge.is-flat {
        color: #334155;
        background: #e2e8f0;
        border-color: #cbd5e1;
    }
    .stripe-tooltip-row {
        display: grid;
        grid-template-columns: 18px 1fr auto;
        align-items: center;
        gap: 9px;
        font-size: 15px;
        color: #4b5563;
        margin: 5px 0;
    }
    .stripe-tooltip-dot {
        width: 8px;
        height: 18px;
        border-radius: 0;
        display: inline-block;
    }
    .stripe-tooltip-dot.today { background: #7c5cff; }
    .stripe-tooltip-dot.compare { background: #b7bfcb; }
    .stripe-tooltip-value {
        font-weight: 700;
        font-size: 18px;
        color: #374151;
    }
    .stripe-chart-hit {
        fill: transparent;
        cursor: crosshair;
    }
    .stripe-chart-point {
        fill: #635bff;
        stroke: #fff;
        stroke-width: 2;
        cursor: pointer;
    }
    .stripe-hover-guide {
        stroke: #94a3b8;
        stroke-width: 1.5;
        stroke-dasharray: 4 4;
    }
    .stripe-current-dot {
        fill: #7c5cff;
        stroke: #7c5cff;
        stroke-width: 1;
    }
    .stripe-compare-dot {
        fill: #b7bfcb;
        stroke: #ffffff;
        stroke-width: 2;
    }
    .stripe-metric-compare-head {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .stripe-metric-compare-head i {
        color: #475569;
        font-size: 14px;
    }
    .stripe-metric-head-inline {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .stripe-range-select-minimal {
        border: 0 !important;
        outline: none !important;
        box-shadow: none !important;
        background-color: transparent !important;
        color: #394b63 !important;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.2;
        height: auto !important;
        width: auto !important;
        min-width: 0 !important;
        padding: 0 18px 0 0 !important;
        margin: 0 !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        background-image: linear-gradient(45deg, transparent 50%, #6b7280 50%), linear-gradient(135deg, #6b7280 50%, transparent 50%);
        background-position: calc(100% - 10px) 8px, calc(100% - 5px) 8px;
        background-size: 5px 5px, 5px 5px;
        background-repeat: no-repeat;
        cursor: pointer;
        vertical-align: baseline;
    }
    .stripe-range-select-minimal:focus {
        outline: none !important;
        box-shadow: none !important;
        color: #111827 !important;
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
        const hoverGuide = document.getElementById('stripeHoverGuide');
        const compareDot = document.getElementById('stripeCompareDot');
        const currentDot = document.getElementById('stripeCurrentDot');
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
            if (hoverGuide) hoverGuide.classList.add('d-none');
            if (compareDot) compareDot.classList.add('d-none');
            if (currentDot) currentDot.classList.add('d-none');
        }

        function percentageDiff(currentValue, compareValue) {
            const current = Number(currentValue || 0);
            const compare = Number(compareValue || 0);
            if (compare === 0) return current === 0 ? 0 : 100;
            return ((current - compare) / Math.abs(compare)) * 100;
        }

        function tooltipMarkup(label, compareLabel, currentValue, compareValue) {
            const diff = percentageDiff(currentValue, compareValue);
            const diffText = `${diff >= 0 ? '+' : ''}${diff.toFixed(1)}%`;
            const badgeClass = diff > 0 ? 'is-pos' : (diff < 0 ? 'is-neg' : 'is-flat');
            return `
                <div class="stripe-tooltip-head">
                    <span>Gross volume</span>
                    <span class="stripe-tooltip-badge ${badgeClass}">${diffText}</span>
                </div>
                <div class="stripe-tooltip-row">
                    <span class="stripe-tooltip-dot today"></span>
                    <span>${label || '-'}</span>
                    <span class="stripe-tooltip-value">${money(currentValue)}</span>
                </div>
                <div class="stripe-tooltip-row">
                    <span class="stripe-tooltip-dot compare"></span>
                    <span>${compareLabel || '-'}</span>
                    <span class="stripe-tooltip-value">${money(compareValue)}</span>
                </div>
            `;
        }

        function showHoverState(index, labels, compareLabels, currentSeries, compareSeries, max) {
            if (index < 0 || index >= currentSeries.length) return;
            const stepX = width / Math.max(currentSeries.length - 1, 1);
            const x = index * stepX;
            const currentVal = Number(currentSeries[index] || 0);
            const compareVal = Number(compareSeries[index] || 0);
            const currentY = height - ((currentVal / max) * (height - 20)) - 10;
            const compareY = height - ((compareVal / max) * (height - 20)) - 10;

            if (hoverGuide) {
                hoverGuide.setAttribute('x1', x.toFixed(2));
                hoverGuide.setAttribute('x2', x.toFixed(2));
                hoverGuide.classList.remove('d-none');
            }
            if (currentDot) {
                currentDot.setAttribute('cx', x.toFixed(2));
                currentDot.setAttribute('cy', currentY.toFixed(2));
                currentDot.classList.remove('d-none');
            }
            if (compareDot) {
                compareDot.setAttribute('cx', x.toFixed(2));
                compareDot.setAttribute('cy', compareY.toFixed(2));
                compareDot.classList.remove('d-none');
            }

            if (!tooltip) return;
            tooltip.innerHTML = tooltipMarkup(
                labels[index] || '',
                compareLabels[index] || '',
                currentVal,
                compareVal
            );
            tooltip.classList.remove('d-none');
            tooltip.style.left = `${Math.min(90, Math.max(10, (x / width) * 100 + 6)).toFixed(2)}%`;
            tooltip.style.top = `${Math.min(90, Math.max(32, (currentY / height) * 100 + 28)).toFixed(2)}%`;
        }

        function renderToday(rangeKey) {
            const preset = todayCardPresets[rangeKey] || todayCardPresets.today;
            if (!preset) return;

            const currentSeriesRaw = Array.isArray(preset.current_series) ? preset.current_series : [];
            const compareSeriesRaw = Array.isArray(preset.compare_series) ? preset.compare_series : [];
            const currentSeries = currentSeriesRaw.map(item => Number(item.amount || 0));
            const compareSeries = compareSeriesRaw.map(item => Number(item.amount || 0));
            const labels = currentSeriesRaw.map(item => item.label || '');
            const compareLabels = compareSeriesRaw.map(item => item.label || '');

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
                const hit = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                hit.setAttribute('cx', x.toFixed(2));
                hit.setAttribute('cy', y.toFixed(2));
                hit.setAttribute('r', '10');
                hit.setAttribute('class', 'stripe-chart-hit');
                hit.addEventListener('mouseenter', () => showHoverState(idx, labels, compareLabels, currentSeries, compareSeries, max));
                hit.addEventListener('mousemove', () => showHoverState(idx, labels, compareLabels, currentSeries, compareSeries, max));
                hit.addEventListener('mouseleave', hideTooltip);
                pointsWrap.appendChild(hit);
            });

            if (titleEl) titleEl.textContent = preset.title || 'Today';
            if (compareLabelEl) compareLabelEl.textContent = preset.compare_label || 'Previous';
            if (currentValueEl) currentValueEl.textContent = money(preset.current_total || 0);
            if (compareValueEl) compareValueEl.textContent = money(preset.compare_total || 0);
            if (hintEl) hintEl.textContent = preset.range_hint || '';
            if (chartTimeEl) chartTimeEl.textContent = preset.range_hint || '';
            showHoverState(Math.max(0, currentSeries.length - 1), labels, compareLabels, currentSeries, compareSeries, max);
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
