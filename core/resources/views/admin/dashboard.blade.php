@extends('admin.layouts.app')

@section('panel')
    <div class="pf-admin-dashboard">
        <div class="pf-admin-header">
            <h1 class="pf-admin-title">@lang('Admin Overview')</h1>
        </div>

        <div class="pf-admin-stat-grid">
            <a href="{{ route('admin.deposit.list') }}" class="pf-admin-stat-card">
                <div class="pf-admin-stat-icon">
                    <i class="las la-wallet"></i>
                </div>
                <div class="pf-admin-stat-body">
                    <p class="pf-admin-stat-label">@lang('Total Revenue')</p>
                    <h3 class="pf-admin-stat-value">{{ showAmount($deposit['total_deposit_amount']) }}</h3>
                    <span class="pf-admin-stat-change pf-admin-stat-change--positive">@lang('Updated this month')</span>
                </div>
            </a>
            <a href="{{ route('admin.users.active') }}" class="pf-admin-stat-card">
                <div class="pf-admin-stat-icon">
                    <i class="las la-users"></i>
                </div>
                <div class="pf-admin-stat-body">
                    <p class="pf-admin-stat-label">@lang('Active Merchants')</p>
                    <h3 class="pf-admin-stat-value">{{ $widget['verified_users'] }}</h3>
                    <span class="pf-admin-stat-change pf-admin-stat-change--positive">@lang('Active this week')</span>
                </div>
            </a>
            <a href="{{ route('admin.deposit.list') }}" class="pf-admin-stat-card">
                <div class="pf-admin-stat-icon">
                    <i class="las la-exchange-alt"></i>
                </div>
                <div class="pf-admin-stat-body">
                    <p class="pf-admin-stat-label">@lang('Total Transactions')</p>
                    <h3 class="pf-admin-stat-value">{{ $widget['total_transactions'] }}</h3>
                    <span class="pf-admin-stat-change pf-admin-stat-change--positive">@lang('Tracked this month')</span>
                </div>
            </a>
            <a href="{{ route('admin.users.kyc.pending') }}" class="pf-admin-stat-card">
                <div class="pf-admin-stat-icon">
                    <i class="las la-user-clock"></i>
                </div>
                <div class="pf-admin-stat-body">
                    <p class="pf-admin-stat-label">@lang('Pending KYC')</p>
                    <h3 class="pf-admin-stat-value">{{ $widget['pending_kyc'] }}</h3>
                    <span class="pf-admin-stat-change pf-admin-stat-change--negative">@lang('Needs attention')</span>
                </div>
            </a>
            <a href="{{ route('admin.rewards.index') }}" class="pf-admin-stat-card">
                <div class="pf-admin-stat-icon">
                    <i class="las la-gift"></i>
                </div>
                <div class="pf-admin-stat-body">
                    <p class="pf-admin-stat-label">@lang('Rewards Program')</p>
                    <h3 class="pf-admin-stat-value">@lang('Referrals')</h3>
                    <span class="pf-admin-stat-change pf-admin-stat-change--positive">@lang('Manage rewards')</span>
                </div>
            </a>
        </div>

        @if(!empty($subscription['enabled']))
            <div class="pf-admin-panel pf-admin-panel--subscription">
                <div class="pf-admin-panel-header">
                    <h5 class="pf-admin-panel-title">@lang('Subscription Overview')</h5>
                    <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-outline--primary">
                        <i class="las la-layer-group"></i> @lang('Manage Plans')
                    </a>
                </div>
                <div class="pf-admin-subscription-grid">
                    <div class="pf-admin-subscription-item">
                        <p class="pf-admin-subscription-label">@lang('Plans')</p>
                        <h4 class="pf-admin-subscription-value">{{ $subscription['total_plans'] }}</h4>
                        <small class="text-muted">{{ $subscription['active_plans'] }} @lang('active')</small>
                    </div>
                    <div class="pf-admin-subscription-item">
                        <p class="pf-admin-subscription-label">@lang('Active Subscriptions')</p>
                        <h4 class="pf-admin-subscription-value">{{ $subscription['active_merchants'] }}</h4>
                        <small class="text-muted">@lang('Merchants on active plan')</small>
                    </div>
                    <div class="pf-admin-subscription-item">
                        <p class="pf-admin-subscription-label">@lang('Pending Requests')</p>
                        <h4 class="pf-admin-subscription-value">{{ $subscription['pending_requests'] }}</h4>
                        <small class="text-muted">@lang('Awaiting approval')</small>
                    </div>
                    <div class="pf-admin-subscription-item">
                        <p class="pf-admin-subscription-label">@lang('MRR Estimate')</p>
                        <h4 class="pf-admin-subscription-value">${{ number_format($subscription['mrr_estimate'], 2) }}</h4>
                        <small class="text-muted">@lang('From active merchant plans')</small>
                    </div>
                </div>
                <div class="pf-admin-subscription-actions">
                    <a href="{{ route('admin.plans.merchants') }}" class="btn btn-sm btn-outline--dark">@lang('Merchant Plans')</a>
                    <a href="{{ route('admin.plans.requests') }}" class="btn btn-sm btn-outline--warning">@lang('Change Requests')</a>
                </div>
            </div>
        @endif

        <div class="pf-admin-chart-grid">
            <div class="pf-admin-panel">
                <div class="pf-admin-panel-header">
                    <h5 class="pf-admin-panel-title">@lang('Revenue Trend')</h5>
                    <div id="dwDatePicker" class="pf-admin-date-picker">
                        <i class="la la-calendar"></i>
                        <span></span>
                        <i class="la la-caret-down"></i>
                    </div>
                </div>
                <div id="dwChartArea" class="pf-admin-chart-area"></div>
            </div>
            <div class="pf-admin-panel">
                <div class="pf-admin-panel-header">
                    <h5 class="pf-admin-panel-title">@lang('Transactions Report')</h5>
                    <div id="trxDatePicker" class="pf-admin-date-picker">
                        <i class="la la-calendar"></i>
                        <span></span>
                        <i class="la la-caret-down"></i>
                    </div>
                </div>
                <div id="transactionChartArea" class="pf-admin-chart-area"></div>
            </div>
        </div>

        <div class="pf-admin-chart-grid pf-admin-chart-grid--three">
            <div class="pf-admin-panel pf-admin-panel--compact">
                <div class="pf-admin-panel-header">
                    <h5 class="pf-admin-panel-title">@lang('Login By Browser') (@lang('Last 30 days'))</h5>
                </div>
                <div class="pf-admin-mini-chart">
                    <canvas id="userBrowserChart"></canvas>
                </div>
            </div>
            <div class="pf-admin-panel pf-admin-panel--compact">
                <div class="pf-admin-panel-header">
                    <h5 class="pf-admin-panel-title">@lang('Login By OS') (@lang('Last 30 days'))</h5>
                </div>
                <div class="pf-admin-mini-chart">
                    <canvas id="userOsChart"></canvas>
                </div>
            </div>
            <div class="pf-admin-panel pf-admin-panel--compact">
                <div class="pf-admin-panel-header">
                    <h5 class="pf-admin-panel-title">@lang('Login By Country') (@lang('Last 30 days'))</h5>
                </div>
                <div class="pf-admin-mini-chart">
                    <canvas id="userCountryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    @include('admin.partials.cron_modal')
@endsection
@push('breadcrumb-plugins')
    <a href="{{ route('admin.payment.index') }}" class="btn btn-sm btn-outline--primary">
        <i class="las la-signal"></i>@lang('Statistics')
    </a>
    <button class="btn btn-outline--primary btn-sm" data-bs-toggle="modal" data-bs-target="#cronModal">
        <i class="las la-server"></i>@lang('Cron Setup')
    </button>
@endpush


@push('script-lib')
    <script src="{{ asset('assets/admin/js/vendor/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/chart.js.2.8.0.js') }}"></script>
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/charts.js') }}"></script>
@endpush

@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}">
@endpush

@push('script')
    <script>
        "use strict";
        document.body.classList.add('pf-admin-dashboard');

        const start = moment().subtract(14, 'days');
        const end = moment();

        const dateRangeOptions = {
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
                'This Year': [moment().startOf('year'), moment().endOf('year')],
            },
            maxDate: moment()
        }

        const changeDatePickerText = (element, startDate, endDate) => {
            $(element).html(startDate.format('MMMM D, YYYY') + ' - ' + endDate.format('MMMM D, YYYY'));
        }

        let dwChart = barChart(
            document.querySelector("#dwChartArea"),
            @json(__(gs('cur_text'))),
            [{
                    name: 'Deposited',
                    data: []
                },
                {
                    name: 'Withdrawn',
                    data: []
                }
            ],
            [],
        );

        let trxChart = lineChart(
            document.querySelector("#transactionChartArea"),
            [{
                    name: "Plus Transactions",
                    data: []
                },
                {
                    name: "Minus Transactions",
                    data: []
                }
            ],
            []
        );


        const depositWithdrawChart = (startDate, endDate) => {

            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }

            const url = @json(route('admin.chart.deposit.withdraw'));

            $.get(url, data,
                function(data, status) {
                    if (status == 'success') {
                        dwChart.updateSeries(data.data);
                        dwChart.updateOptions({
                            xaxis: {
                                categories: data.created_on,
                            }
                        });
                    }
                }
            );
        }

        const transactionChart = (startDate, endDate) => {

            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }

            const url = @json(route('admin.chart.transaction'));


            $.get(url, data,
                function(data, status) {
                    if (status == 'success') {


                        trxChart.updateSeries(data.data);
                        trxChart.updateOptions({
                            xaxis: {
                                categories: data.created_on,
                            }
                        });
                    }
                }
            );
        }



        $('#dwDatePicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#dwDatePicker span', start, end));
        $('#trxDatePicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#trxDatePicker span', start, end));

        changeDatePickerText('#dwDatePicker span', start, end);
        changeDatePickerText('#trxDatePicker span', start, end);

        depositWithdrawChart(start, end);
        transactionChart(start, end);

        $('#dwDatePicker').on('apply.daterangepicker', (event, picker) => depositWithdrawChart(picker.startDate, picker.endDate));
        $('#trxDatePicker').on('apply.daterangepicker', (event, picker) => transactionChart(picker.startDate, picker.endDate));

        piChart(
            document.getElementById('userBrowserChart'),
            @json(@$chart['user_browser_counter']->keys()),
            @json(@$chart['user_browser_counter']->flatten())
        );

        piChart(
            document.getElementById('userOsChart'),
            @json(@$chart['user_os_counter']->keys()),
            @json(@$chart['user_os_counter']->flatten())
        );

        piChart(
            document.getElementById('userCountryChart'),
            @json(@$chart['user_country_counter']->keys()),
            @json(@$chart['user_country_counter']->flatten())
        );
    </script>
@endpush
@push('style')
    <style>
        .pf-admin-dashboard {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .pf-admin-title {
            font-size: 24px;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }

        .pf-admin-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .pf-admin-stat-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .pf-admin-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        .pf-admin-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #64748b;
        }

        .pf-admin-stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
        }

        .pf-admin-stat-value {
            font-size: 22px;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 6px;
        }

        .pf-admin-stat-change {
            font-size: 12px;
            font-weight: 500;
        }

        .pf-admin-stat-change--positive {
            color: #16a34a;
        }

        .pf-admin-stat-change--negative {
            color: #dc2626;
        }

        .pf-admin-chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
        }

        .pf-admin-subscription-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
        }

        .pf-admin-subscription-item {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
            background: #ffffff;
        }

        .pf-admin-subscription-label {
            margin: 0 0 8px;
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
        }

        .pf-admin-subscription-value {
            margin: 0 0 4px;
            font-size: 24px;
            color: #0f172a;
            font-weight: 700;
        }

        .pf-admin-subscription-actions {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .pf-admin-chart-grid--three {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .pf-admin-panel {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        }

        .pf-admin-panel--compact {
            padding: 16px;
        }

        .pf-admin-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .pf-admin-panel-title {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }

        .pf-admin-date-picker {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            cursor: pointer;
        }

        .pf-admin-chart-area {
            min-height: 280px;
        }

        .pf-admin-mini-chart {
            min-height: 220px;
        }

        .pf-admin-panel canvas {
            max-width: 100%;
        }

        body.pf-admin-dashboard .page-wrapper {
            background: #f8fafc;
        }

        body.pf-admin-dashboard .navbar-wrapper {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }

        body.pf-admin-dashboard .navbar-wrapper .navbar-search-field,
        body.pf-admin-dashboard .navbar-wrapper .navbar-search-field::placeholder {
            color: #475569;
        }

        body.pf-admin-dashboard .navbar-wrapper .navbar__action-list i,
        body.pf-admin-dashboard .navbar-wrapper .navbar-user__name,
        body.pf-admin-dashboard .navbar-wrapper .navbar-search i {
            color: #475569;
        }

        body.pf-admin-dashboard .sidebar {
            background-color: #0f172a !important;
        }

        body.pf-admin-dashboard .sidebar__menu .sidebar-menu-item > a {
            color: #cbd5e1;
        }

        body.pf-admin-dashboard .sidebar__menu .sidebar-menu-item > a .menu-icon {
            color: #94a3b8;
        }

        body.pf-admin-dashboard .sidebar__menu .sidebar-menu-item.active > a,
        body.pf-admin-dashboard .sidebar__menu .sidebar-menu-item > a:hover {
            background: #1e293b;
            color: #ffffff;
        }

        body.pf-admin-dashboard .sidebar__menu .sidebar-menu-item.active > a .menu-icon,
        body.pf-admin-dashboard .sidebar__menu .sidebar-menu-item > a:hover .menu-icon {
            color: #a5b4fc;
        }

        body.pf-admin-dashboard .sidebar__menu-header {
            color: #94a3b8;
        }

        .apexcharts-menu {
            min-width: 120px !important;
        }

        @media (max-width: 767px) {
            .pf-admin-stat-card {
                padding: 16px;
            }

            .pf-admin-panel {
                padding: 16px;
            }
        }
    </style>
@endpush
