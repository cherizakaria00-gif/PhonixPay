@extends($activeTemplate . 'layouts.master')

@php
    $showHeaderBalance = true;
@endphp

@section('content')
@include($activeTemplate.'partials.notice')

<div class="dashboard-page">
    <div class="dashboard-toolbar pf-merchant-toolbar">
        <div class="pf-merchant-toolbar__left">
            <h1 class="pf-merchant-title">@lang('Dashboard')</h1>
            <span class="pf-merchant-subtitle" id="dashboardRangeLabel">@lang('This Month')</span>
        </div>
        <div class="dashboard-toolbar__right pf-merchant-toolbar__right">
            <select name="payment_statistics" class="dashboard-select">
                <option value="today">@lang('Today')</option>
                <option value="week">@lang('This Week')</option>
                <option value="month" selected>@lang('This Month')</option>
            </select>
            <select name="payment_status" class="dashboard-select">
                <option value="" selected>@lang('All')</option>
                <option value="initiated">@lang('Initiated')</option> 
                <option value="successful">@lang('Succeed')</option> 
                <option value="rejected">@lang('Canceled')</option> 
            </select>
        </div>
    </div>

    <div class="html"></div>

    <div class="dashboard-activity-grid">
        <div class="dashboard-panel dashboard-panel--history">
            <div class="dashboard-panel__header dashboard-panel__header--tight">
                <div>
                    <h6 class="mb-1">@lang('Payment History')</h6>
                </div>
                <div>
                    <x-export />
                </div>
            </div>
            <div class="dashboard-table-wrapper">
                <table class="table dashboard-history-table">
                    <thead>
                        <tr>
                            <th>@lang('Customer')</th>
                            <th>@lang('Email')</th>
                            <th>@lang('Phone')</th>
                            <th class="text-end">@lang('Amount')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Date')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestDeposits as $deposit)
                            @php
                                $statusLabel = 'Initiated';
                                $statusClass = 'status-badge--warning';
                                if ($deposit->status == Status::PAYMENT_SUCCESS) {
                                    $statusLabel = 'Succeeded';
                                    $statusClass = 'status-badge--success';
                                } elseif ($deposit->status == Status::PAYMENT_REFUNDED) {
                                    $statusLabel = 'Refunded';
                                    $statusClass = 'status-badge--warning';
                                } elseif ($deposit->status == Status::PAYMENT_REJECT) {
                                    $statusLabel = 'Canceled';
                                    $statusClass = 'status-badge--danger';
                                }
                                $customer = $deposit->apiPayment->customer ?? null;
                                $customerName = '';
                                if ($customer) {
                                    $customerName = trim($customer->name ?? (($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')));
                                }
                                $customerEmail = $customer->email ?? null;
                                $customerPhone = $customer->mobile ?? ($customer->phone ?? null);
                            @endphp
                            <tr>
                                <td>{{ $customerName ?: __('N/A') }}</td>
                                <td>{{ $customerEmail ?: __('N/A') }}</td>
                                <td>{{ $customerPhone ?: __('N/A') }}</td>
                                <td class="text-end {{ $deposit->status == Status::PAYMENT_REJECT ? 'amount-negative' : 'amount-positive' }}">
                                    {{ showAmount($deposit->amount) }}
                                </td>
                                <td><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                <td>{{ showDateTime($deposit->created_at, 'M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">
                                    <x-empty-message h4="{{ true }}" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-panel dashboard-panel--activity">
            <div class="dashboard-panel__header">
                <h6 class="mb-0">@lang('Recent Activity')</h6>
            </div>
            <div class="activity-list">
                @forelse ($latestTrx->take(6) as $trx)
                    @php
                        $remark = strtolower($trx->remark ?? '');
                        $activityTone = 'activity-icon--success';
                        $activityIcon = 'las la-check';
                        $activityTitle = __('Payment Successful');

                        if (str_contains($remark, 'withdraw')) {
                            $activityTone = 'activity-icon--warning';
                            $activityIcon = 'las la-arrow-up';
                            $activityTitle = __('Withdrawal Pending');
                        } elseif (str_contains($remark, 'refund') || str_contains($remark, 'chargeback') || str_contains($remark, 'reject')) {
                            $activityTone = 'activity-icon--danger';
                            $activityIcon = 'las la-times';
                            $activityTitle = __('Payment Chargeback');
                        }
                    @endphp
                    <div class="activity-item">
                        <div class="activity-icon {{ $activityTone }}">
                            <i class="{{ $activityIcon }}"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-title">{{ $activityTitle }}</p>
                            <p class="activity-desc">{{ \Illuminate\Support\Str::limit($trx->details ?? __('Transaction update'), 48) }}</p>
                            <span class="activity-time">{{ \Carbon\Carbon::parse($trx->created_at)->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">@lang('No recent activity found.')</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@if (auth()->user()->kv == Status::KYC_UNVERIFIED && auth()->user()->kyc_rejection_reason)
    <div class="modal fade" id="kycRejectionReason">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('KYC Document Rejection Reason')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ auth()->user()->kyc_rejection_reason }}</p>
                </div>
            </div>
        </div>
    </div>
@endif

@push('style')    
<style>
    .dashboard-page {
        background: transparent;
        padding: 0;
        border-radius: 0;
        box-shadow: none;
    }

    .pf-merchant-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .pf-merchant-toolbar__left {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .pf-merchant-title {
        font-size: 24px;
        font-weight: 600;
        color: #0f172a;
        margin: 0;
    }

    .pf-merchant-subtitle {
        font-size: 12px;
        color: #6b7280;
    }

    .dashboard-toolbar__right {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .dashboard-select {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        color: #0f172a;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
    }

    .dashboard-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .stat-card__content {
        flex: 1;
    }

    .stat-card__label {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 6px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .stat-card__value {
        font-size: 22px;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 6px;
    }

    .stat-card__delta {
        font-size: 12px;
        font-weight: 500;
    }

    .stat-card__delta--positive {
        color: #16a34a;
    }

    .stat-card__delta--negative {
        color: #ef4444;
    }

    .stat-card__delta--neutral {
        color: #64748b;
    }

    .stat-card__icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #ffffff;
        order: -1;
    }

    .stat-card--payment-succeed .stat-card__icon {
        background: #10b981;
    }

    .stat-card--withdraw-total .stat-card__icon {
        background: #6366f1;
    }

    .stat-card--withdraw-pending .stat-card__icon {
        background: #f59e0b;
    }

    .stat-card--payment-chargeback .stat-card__icon {
        background: #ef4444;
    }

    .dashboard-chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 18px;
        margin-bottom: 22px;
    }

    .dashboard-panel {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }

    .dashboard-panel__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        gap: 12px;
        flex-wrap: wrap;
    }

    .dashboard-panel__header--tight {
        margin-bottom: 16px;
    }

    .dashboard-activity-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .dashboard-table-wrapper {
        overflow-x: auto;
    }

    .dashboard-history-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .dashboard-history-table thead th {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #e5e7eb;
        padding: 12px 10px;
        white-space: nowrap;
    }

    .dashboard-history-table tbody td {
        font-size: 13px;
        color: #0f172a;
        padding: 14px 10px;
        border-bottom: 1px solid #eef2f6;
        vertical-align: middle;
    }

    .dashboard-history-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-badge--success {
        background: #dcfce7;
        color: #16a34a;
    }

    .status-badge--warning {
        background: #fef9c3;
        color: #a16207;
    }

    .status-badge--danger {
        background: #fee2e2;
        color: #ef4444;
    }

    .amount-positive {
        color: #16a34a;
        font-weight: 600;
    }

    .amount-negative {
        color: #ef4444;
        font-weight: 600;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .activity-icon {
        width: 42px;
        height: 42px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        color: #ffffff;
    }

    .activity-icon--success {
        background: #10b981;
    }

    .activity-icon--warning {
        background: #3b82f6;
    }

    .activity-icon--danger {
        background: #ef4444;
    }

    .activity-title {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .activity-desc {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
    }

    .activity-time {
        font-size: 11px;
        color: #9ca3af;
    }

    @media (max-width: 1199px) {
        .dashboard-activity-grid {
            grid-template-columns: 1fr;
        }
    }

    .dashboard-panel--chart {
        margin-bottom: 0;
    }

    .payment-overview-canvas,
    .withdraw-weekly-canvas {
        position: relative;
        height: 260px;
        min-height: 260px;
    }

    .payment-statistics {
        font-weight: 600;
        color: #0f172a;
    }

    .dashboard-chart__legend {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 12px;
        color: #64748b;
        margin-top: 8px;
        flex-wrap: wrap;
    }

    .legend-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 6px;
    }

    .legend-dot--success {
        background: #22c55e;
    }

    .legend-dot--danger {
        background: #ef4444;
    }

    .dashboard-status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .dashboard-panel .card-body {
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dashboard-panel canvas {
        max-width: 100%;
    }

    .no-data-found {
        position: static;
        transform: none;
        padding: 0;
        color: #94a3b8;
    }

    .dashboard-panel--transactions {
        margin-top: 6px;
    }

    .dashboard-panel--transactions .accordion-item {
        border: 0;
        border-bottom: 1px solid #eef2f6;
    }

    .dashboard-panel--transactions .accordion-item:last-child {
        border-bottom: 0;
    }

    .dashboard-panel--transactions .accordion-button {
        background: transparent;
        box-shadow: none;
    }

    .dashboard-panel--transactions .accordion-button:not(.collapsed) {
        background: #f8fafc;
    }

    .chart-empty {
        font-size: 13px;
        color: #94a3b8;
        margin-top: 6px;
    }

    @media (max-width: 767px) {
        .stat-card,
        .dashboard-panel {
            padding: 16px;
        }
    }

    body.pf-merchant-dashboard {
        background: #f8fafc;
    }

    body.pf-merchant-dashboard .merchant-dashboard .d-sidebar {
        background: #0f172a;
        border-right: 1px solid #1e293b;
    }

    body.pf-merchant-dashboard .merchant-dashboard .d-sidebar .sidebar-menu__link {
        color: #cbd5e1;
    }

    body.pf-merchant-dashboard .merchant-dashboard .d-sidebar .sidebar-menu__link i {
        color: #94a3b8;
    }

    body.pf-merchant-dashboard .merchant-dashboard .d-sidebar .sidebar-menu__item.active .sidebar-menu__link,
    body.pf-merchant-dashboard .merchant-dashboard .d-sidebar .sidebar-menu__link:hover {
        background: #1e293b;
        color: #ffffff;
    }

    body.pf-merchant-dashboard .merchant-dashboard .d-sidebar .sidebar-menu__item.active .sidebar-menu__link i,
    body.pf-merchant-dashboard .merchant-dashboard .d-sidebar .sidebar-menu__link:hover i {
        color: #a5b4fc;
    }

    body.pf-merchant-dashboard .merchant-dashboard .dashboard-top-nav {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
    }

    body.pf-merchant-dashboard .merchant-dashboard .header-profile {
        background: #e0e7ff;
        color: #4338ca;
    }

    body.pf-merchant-dashboard .merchant-dashboard .header-action-btn {
        background: #f1f5f9;
        color: #475569;
    }
</style>
@endpush

@push('script')
    <script src="{{ asset('assets/admin/js/vendor/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/chart.js.2.8.0.js') }}"></script>

    <script>
        document.body.classList.add('pf-merchant-dashboard');

        function updateRangeLabel() {
            var label = $('[name=payment_statistics] option:selected').text();
            $('#dashboardRangeLabel').text(label);
        }

        statistics();
        updateRangeLabel();

        $(document).on('change', '[name=payment_statistics], [name=payment_status]', function() {
            updateRangeLabel();
            statistics();
        });

        function statistics() {
            var url = "{{ route('user.dashboard.statistics') }}";
            var time = $('[name=payment_statistics] option:selected').val();
            var status = $('[name=payment_status] option:selected').val();

            $.get(url, {
                time: time,
                status: status,
            }, function(response) {
                $('.html').html(response.view);

                renderPaymentOverview(response);
                renderWeeklyWithdrawals(response);
                renderStatusCharts(response);
            });
        }

        function renderPaymentOverview(response) {
            var labels = response.series_labels || [];
            var series = response.payment_series || {};
            var succeedSeries = series['Payments Succeed'] || [];
            var chargebackSeries = series['Payment Chargeback'] || [];

            var wrapper = $('.payment-overview-canvas');
            if (!wrapper.length) {
                return;
            }

            wrapper.html('<canvas height="260" id="payment_overview_chart"></canvas>');
            $('.payment-overview-title').removeClass('no-data-found').text('Payment Overview');

            if (!labels.length) {
                $('.payment-overview-title').addClass('no-data-found').text('No Data Found');
                return;
            }

            var ctx = document.getElementById('payment_overview_chart');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Payments Succeed',
                            data: succeedSeries,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.15)',
                            borderWidth: 2,
                            fill: false,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            tension: 0.35
                        },
                        {
                            label: 'Payment Chargeback',
                            data: chargebackSeries,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.15)',
                            borderWidth: 2,
                            fill: false,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            tension: 0.35
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
                            display: true,
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                fontColor: '#94a3b8'
                            }
                        }],
                        yAxes: [{
                            display: true,
                            gridLines: {
                                color: '#e5e7eb',
                                drawBorder: false
                            },
                            ticks: {
                                beginAtZero: true,
                                fontColor: '#94a3b8',
                                callback: function(value) {
                                    return value + ' {{ gs("cur_text") }}';
                                }
                            }
                        }]
                    },
                    legend: {
                        display: false
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var label = data.datasets[tooltipItem.datasetIndex].label || '';
                                var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index] || 0;
                                return label + ': ' + value + ' {{ gs("cur_text") }}';
                            }
                        }
                    }
                }
            });
        }

        function renderWeeklyWithdrawals(response) {
            var labels = response.series_labels || [];
            var series = response.payment_series || {};
            var withdrawSeries = series['Total Withdraws'] || [];

            var wrapper = $('.withdraw-weekly-canvas');
            if (!wrapper.length) {
                return;
            }

            wrapper.html('<canvas height="260" id="withdraw_weekly_chart"></canvas>');

            if (!labels.length) {
                wrapper.html('<p class="chart-empty text-muted mt-2 mb-0">No Data Found</p>');
                return;
            }

            var ctx = document.getElementById('withdraw_weekly_chart');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Withdraws',
                        data: withdrawSeries,
                        backgroundColor: '#3b82f6',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                fontColor: '#94a3b8'
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                color: '#e5e7eb',
                                drawBorder: false
                            },
                            ticks: {
                                beginAtZero: true,
                                fontColor: '#94a3b8',
                                callback: function(value) {
                                    return value + ' {{ gs("cur_text") }}';
                                }
                            }
                        }]
                    },
                    legend: {
                        display: false
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.yLabel + ' {{ gs("cur_text") }}';
                            }
                        }
                    }
                }
            });
        }

        function renderStatusCharts(response) {
            var paymentSummary = response.payment_summary || {};
            var withdrawSummary = response.withdraw_summary || {};

            $('.reporting-charts .chart-empty').remove();
            $('.payment-status-canvas').html('<canvas height="180" id="payment_status_chart"></canvas>');
            $('.withdraw-status-canvas').html('<canvas height="180" id="withdraw_status_chart"></canvas>');

            var paymentData = [
                Number(paymentSummary.total_refunded || 0),
                Number(paymentSummary.total_succeed || 0),
                Number(paymentSummary.total_canceled || 0)
            ];

            var withdrawData = [
                Number(withdrawSummary.total_pending || 0),
                Number(withdrawSummary.total_approved || 0),
                Number(withdrawSummary.total_rejected || 0)
            ];

            buildDoughnutChart(
                'payment_status_chart',
                paymentData,
                ["@lang('Chargeback')", "@lang('Succeed')", "@lang('Canceled')"]
            );

            buildDoughnutChart(
                'withdraw_status_chart',
                withdrawData,
                ["@lang('Pending')", "@lang('Approved')", "@lang('Rejected')"]
            );
        }

        function buildDoughnutChart(canvasId, data, labels) {
            var total = data.reduce(function(sum, value) {
                return sum + value;
            }, 0);

            var chartData = data;
            var chartLabels = labels;
            var baseChartColors = ['#87c5a6', '#323444'];
            var chartColors = chartLabels.map(function(_, index) {
                return baseChartColors[index % baseChartColors.length];
            });
            var showLegend = true;

            if (total === 0) {
                chartData = [1];
                chartLabels = ['No Data'];
                chartColors = ['#e5e5e5'];
                showLegend = false;
                $('#' + canvasId).closest('.card-body')
                    .append('<p class="chart-empty text-muted mt-2 mb-0">No Data Found</p>');
            }

            var ctx = document.getElementById(canvasId);
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        data: chartData,
                        backgroundColor: chartColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutoutPercentage: 70,
                    legend: {
                        display: showLegend,
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            fontSize: 11
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                if (!showLegend) {
                                    return data.labels[tooltipItem.index];
                                }

                                var label = data.labels[tooltipItem.index] || '';
                                var value = data.datasets[0].data[tooltipItem.index] || 0;
                                return label + ': ' + value + ' {{ gs("cur_text") }}';
                            }
                        }
                    }
                }
            });
        }
    </script>
@endpush
