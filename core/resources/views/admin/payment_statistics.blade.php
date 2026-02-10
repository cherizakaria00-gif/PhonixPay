@extends('admin.layouts.app')

@section('panel')
    <div class="row g-4">
        <div class="col-md-6">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card full-view">
                        <div class="card-header">
                            <div class="row g-2 align-items-center">
                                <div class="col-sm-6">
                                    <h5 class="card-title mb-0">@lang('Total Payments')</h5>
                                </div>
                                <div class="col-sm-6 text-sm-end"> 
                                    <div class="d-flex justify-content-sm-end gap-2">
                                        <button class="exit-btn">
                                            <i class="fullscreen-open las la-compress" onclick="openFullscreen();"></i>
                                            <i class="fullscreen-close las la-compress-arrows-alt" onclick="closeFullscreen();"></i>
                                        </button>
                                        <select name="payment_status" class="widget_select">
                                            <option value="all" selected>@lang('All')</option>
                                            <option value="initiated">@lang('Initiated')</option>
                                            <option value="successful">@lang('Successful')</option>
                                            <option value="rejected">@lang('Canceled')</option>
                                        </select> 
                                        <select name="payment_time" class="widget_select">
                                            <option value="week">@lang('Current Week')</option>
                                            <option value="month">@lang('Current Month')</option>
                                            <option value="year" selected>@lang('Current Year')</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body text-center pb-0 px-0">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <p>@lang('This') <span class="time_type"></span> @lang('payment')</p>
                                </div>
                                <div class="col-md-4">
                                    <h3><span>{{ gs('cur_sym') }}</span><span class="total_payment"></span></h3>
                                </div>
                                <div class="col-md-4">
                                    <p class="up_down"></p>
                                </div>
                            </div>
                            <div class="payment_canvas"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-12">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">@lang('Payment Statistics')</h5>
                        </div>
                        <div class="card-body">
                            @if ($totalPayment > 0)
                                <div class="card-container">
                                    <div class="investments-scheme">
                                        <div class="investments-scheme-item">
                                            <p class="mb-0">@lang('Total Payment Requests')</p>
                                            <h3 class="mb-6">
                                                <small>{{ gs('cur_sym') }}</small>{{ showAmount($totalPayment) }}
                                            </h3>
                                        </div>
                                        <div class="investments-scheme-arrow">
                                            <div class="text-end">
                                                <i class="las la-arrow-down text--success" style="transform: rotate(30deg);"></i>
                                            </div>
                                            <div class="text-end">
                                                <i class="las la-arrow-down text--success" style="transform: rotate(0deg);"></i>
                                            </div>
                                            <div class="text-start">
                                                <i class="las la-arrow-down text--success" style="transform: rotate(-30deg);"></i>
                                            </div>
                                        </div>
                                        <div class="investments-scheme-group">
                                            <div class="investments-scheme-content">
                                                <p class="font-12">@lang('Initiated Payments')</p>
                                                <h3 class="deposit-amount text--primary counter">
                                                    {{ gs('cur_sym') }}{{ showAmount($widget['initiated_payment']) }}
                                                </h3>
                                                <p class="mb-0 font-12">
                                                    <i class="feather icon-users"></i>
                                                    <strong>
                                                        {{ showAmount(($widget['initiated_payment'] / $totalPayment) * 100) }}%
                                                    </strong>
                                                </p> 
                                            </div>
                                            <div class="investments-scheme-content">
                                                <p class="font-12">@lang('Successful Payments')</p>
                                                <h3 class="deposit-amount text--success counter">
                                                    {{ gs('cur_sym') }}{{ showAmount($widget['successful_payment']) }}
                                                </h3>
                                                <p class="mb-0 font-12"><i class="feather icon-users"></i>
                                                    <strong>
                                                        {{ showAmount(($widget['successful_payment'] / $totalPayment) * 100) }}%
                                                    </strong>
                                                </p>
                                            </div>
                                            <div class="investments-scheme-content">
                                                <p class="font-12">@lang('Canceled Payments')</p>
                                                <h3 class="deposit-amount text--danger counter">
                                                    {{ gs('cur_sym') }}{{ showAmount($widget['canceled_payment']) }}
                                                </h3>
                                                <p class="mb-0 font-12"><i class="feather icon-users"></i>
                                                    <strong>
                                                        {{ showAmount(($widget['canceled_payment'] / $totalPayment) * 100) }}%
                                                    </strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <h5 class="text-center">@lang('Invest not found')</h5>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0">@lang('Payment Statistics by Gateway')</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="chart-info">
                                    <a href="#" class="chart-info-toggle">
                                        <img src="{{ asset('assets/images/collapse.svg') }}" alt="image" class="chart-info-img">
                                    </a>
                                    <div class="chart-info-content">
                                        <ul class="chart-info-list">
                                            @foreach ($paymentByGateway as $data)  
                                                <li class="chart-info-list-item"> 
                                                    <i class="fas fa-money-bill-wave planPointInterest me-2"></i>{{ __(@$data->gateway->name) }}
                                                    ({{ showAmount(($data->amount / @$widget['successful_payment']) * 100) }}%)
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                <div class="chart-area">
                                    <canvas id="payment_by_plan" height="250" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
        <div class="col-md-6">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-12">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-sm-6">
                                            <h5 class="card-title mb-0">@lang('Payment Growth') - {{ now()->format('Y') }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header">
                            <div class="row align-items-center g-2">
                                <div class="col-sm-6">
                                    <h5 class="card-title mb-0">@lang('Payment Charge')</h5>
                                </div>
                                @if (@$firstPaymentYear->date)
                                    <div class="col-sm-6">
                                        <div class="pair-option"> 
                                            <select name="payment_charge_year" class="widget_select">
                                                @for ($i = $firstPaymentYear->date; $i <= date('Y'); $i++)
                                                    <option value="{{ $i }}" @if (date('Y') == $i) selected @endif>
                                                        {{ $i }}
                                                    </option>
                                                @endfor
                                            </select> 
                                            <select name="payment_charge_month" class="widget_select">
                                                @for($i = 1; $i <= 12; $i++)
                                                    <option value="{{ $i }}" @if (date('m') == $i) selected @endif>
                                                        {{ date("F", mktime(0, 0, 0, $i, 1)) }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="payment_charge"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row g-2 align-items-center">
                                <div class="col-sm-6 col-md-12 col-xl-5">
                                    <h5 class="card-title mb-0">@lang('Payment Statistics by Currency')</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="chart-info">
                                    <a href="#" class="chart-info-toggle">
                                        <img src="{{ asset('assets/images/collapse.svg') }}" alt="image" class="chart-info-img">
                                    </a>
                                    <div class="chart-info-content">
                                        <ul class="chart-info-list plan-info-data">
                                            @foreach (@$paymentByCurrency as $data) 
                                                <li class="chart-info-list-item"> 
                                                    <i class="fas fa-money-bill-wave planPoint me-2"></i>{{ __($data->method_currency) }}
                                                    ({{ showAmount((@$data->amount / @$widget['successful_payment']) * 100) }}%)
                                                </li>
                                            @endforeach 
                                        </ul>
                                    </div>
                                </div>
                                <div class="chart-area chart-area--fixed">
                                    <canvas height="250" id="payment_currency_canvas"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> 
                <div class="col-12">
                    <div class="card">
                        <div class="card-header justify-content-between d-flex flex-wrap">
                            <div class="card-title mb-0">@lang('Recent Payments')</div>
                            <a href="{{ route('admin.deposit.list') }}" class="btn btn-outline--primary btn-sm">@lang('View All')</a>
                        </div>
                        <div class="card-body">
                            <div class="plan-list d-flex flex-wrap flex-xxl-column gap-3 gap-xxl-0">
                                @foreach ($recentPayments as $payment)
                                    <div class="plan-item-two"> 
                                        <div class="plan-start plan-inner-div">
                                            <p class="plan-label">@lang('Gateway')</p>
                                            <p class="plan-value date">
                                                <span class="fw-bold">{{ __($payment->gateway->alias) }}</span> <br>
                                                <a class="fw-bold" href="{{ route('admin.deposit.details', $payment->id) }}">{{ $payment->trx }}</a>
                                            </p>
                                        </div>
                                        <div class="plan-start plan-inner-div">
                                            <p class="plan-label">@lang('Initiated')</p>
                                            <p class="plan-value date">
                                                {{ showDateTime($payment->created_at, 'd M, y h:i A') }}<br>{{ diffForHumans($payment->created_at) }}
                                            </p>
                                        </div>
                                        <div class="plan-end plan-inner-div">
                                            <p class="plan-label">@lang('Merchant')</p>
                                            <p class="plan-value date">
                                                <span>{{ $payment->user->fullname }}</span><br>
                                                <a href="{{ route('admin.users.detail', $payment->user_id) }}">
                                                    <span>@</span>{{ $payment->user->username }}
                                                </a>
                                            </p>
                                        </div> 
                                        <div class="plan-amount plan-inner-div text-end">
                                            <p class="plan-label">@lang('Amount')</p>
                                            <p class="plan-value amount">  
                                                {{ gs('cur_sym') }}{{ showAmount($payment->amount) }} - <span class="text-danger">{{ showAmount($payment->totalCharge)}}
                                                <br>
                                                <strong title="@lang('Amount after total charge')">
                                                {{ showAmount($payment->amount-$payment->totalCharge) }} {{ __(gs('cur_text')) }}
                                                </strong> 
                                            </p>
                                        </div>
                                        <div class="plan-amount plan-inner-div text-end">
                                            <p class="plan-label">@lang('Status')</p>
                                            <p class="plan-value amount"> 
                                                @php echo $payment->statusBadge @endphp
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>  
    </div>
@endsection
 
@push('style')
    <style>
        .widget_select {
            padding: 3px 3px;
            font-size: 13px;
        }
        .my-progressbar {
        height: 5px;
        }
        .plan-item-two {
        width: 100%;
        background-color: #fff;
        border: 1px solid #dfdfdf;
        padding: 15px;
        position: relative;
        }
        .plan-desc {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 1rem;
        }
        .plan-item-two .plan-inner-div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        flex-wrap: wrap;
        }
        .plan-value {
        text-align: right;
        }
        .plan-item-two .plan-label {
        font-weight: 600;
        }
        .interest-scheme {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        }
        .chart-container {
        overflow: hidden;
        }
        .chart-info {
        position: relative;
        isolation: isolate;
        }
        .chart-info-toggle {
        display: inline-block;
        }
        .chart-info-img {
        width: 30px;
        transform: rotate(180deg);
        filter: invert(0.62) sepia(1) saturate(4.5) hue-rotate(199deg);
        }
        .chart-info-content {
        position: absolute;
        top: 30px;
        left: 0;
        border-radius: 3px;
        background: #fff;
        transform: translateX(-100%);
        transition: all 0.3s ease;
        }
        .chart-info-content.is-open {
        transform: translateX(0);
        box-shadow: 0 0 1.5rem rgba(18, 38, 63, 0.1);
        }
        .chart-info-list-item {
        display: flex;
        padding: 5px 15px;
        }
        .chart-info-list-item:first-child {
        padding-top: 10px;
        }
        .chart-info-list-item:last-child {
        padding-bottom: 10px;
        }
        .investments-scheme-arrow {
        display: none;
        }
        .investments-scheme {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        }
        .investments-scheme-group {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        }
        .progress-info {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 5px;
        margin-bottom: 5px;
        }
        .exit-btn {
        padding: 0;
        font-size: 30px;
        line-height: 1;
        color: #5b6e88;
        background: transparent;
        border: none;
        transition: all .3s ease;
        }
        .exit-btn:hover {
        color: #4634ff ;
        }
        .exit-btn .fullscreen-close {
        margin-left: -25px;
        transition: all 0.3s;
        display: none;
        }
        .exit-btn.active .fullscreen-open {
        display: none;
        }
        .exit-btn.active .fullscreen-close {
        display: block;
        }
        @media screen and (min-width: 576px) {
        .interest-scheme {
            justify-content: space-between;
            flex-direction: row;
            gap: 1.5rem;
        }

        .pair-option {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 5px;
        }
        .investments-scheme-item {
            text-align: center;
        }
        .investments-scheme-group {
            width: 100%;
            flex-direction: row;
            justify-content: space-around;
        }
        .investments-scheme-arrow {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-around;
        }
        }
        @media screen and (min-width: 768px) {
        .interest-scheme {
            gap: .5rem;
        }
        }
        @media screen and (min-width: 1200px) {
        .plan-name {
            font-size: 14px;
            font-weight: 500 !important;
        }

        .plan-item-two .plan-inner-div {
            gap: 5px;
        }

        .plan-desc {
            justify-content: flex-start;
            gap: 5px;
            font-size: 14px;
            line-height: 1.2;
        }

        .plan-item-two {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        }
        @media screen and (min-width: 1366px) {
        .plan-item-two {
            flex-direction: row;
            align-items: flex-start;
            gap: 5px;
            padding: 21px 15px;
        }

        .plan-item-two .plan-inner-div {
            flex-wrap: nowrap;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            flex-shrink: 0;
        }

        .plan-value {
            font-size: 12px;
            text-align: left;
        }

        .plan-info {
            width: 40%;
        }

        .plan-start {
            width: 20%;
        }

        .plan-end {
            width: 20%;
        }

        .plan-amount {
            width: 20%;
        }
        .chart-info-toggle {
            display: none;
        }
        .chart-info-content {
            position: unset;
            transform: translateX(0);
        }
        .chart-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chart-info {
            flex-shrink: 0;
        }
        .investments-scheme-group {
            text-align: center;
        }
        }
        @media (min-width: 1400px) {
        .plan-item-two:not(:last-child) {
            border-bottom: 0;
        }

        .plan-item-two {
            padding: 15px;
        }

        .card-container {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .deposit-amount {
            font-size: 18px;
        }
        }
        @media (min-width: 1900px) {
            .card-container {
                padding-top: 10px;
                padding-bottom: 10px;
                gap: 1rem;
            }

            .card-gap {
                padding-top: 20px;
                padding-bottom: 20px;
            }
            .chart-area--fixed {
                max-width: 350px;
            }
            .plan-item-two {
                padding: 14px 15px;
            }
        }
        .badge-danger-inverse {
            background-color: rgba(244, 68, 85, 0.1);
            color: #f44455;
        }
        .badge-success-inverse {
            background-color: rgba(95, 194, 126, 0.1);
            color: #5fc27e;
        }
    </style>
@endpush 
 
@push('script')
    <script src="{{ asset('assets/admin/js/vendor/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/chart.js.2.8.0.js') }}"></script>
    <script>
        'use strict';
        (function($) {
            var options = {
            series: [{
                name: "{{ gs('cur_sym') }}",
                data: [@foreach(array_values($paymentThisYear) as $payment) {{ $payment }}, @endforeach]  
            }],
            chart: {
            toolbar: {
                show: false
            },
            height: 350,
            type: 'line',
            zoom: {
                enabled: false
            }
            },
            dataLabels: {
            enabled: false
            },
            stroke: {
            curve: 'straight'
            },
            title: {
            align: 'left'
            },
            grid: {
            row: {
                colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
                opacity: 0.5
            },
            },
            xaxis: {
            categories: [@foreach(array_keys($paymentThisYear) as $month) '{{ $month }}', @endforeach],
            }
            };
            var chart = new ApexCharts(document.querySelector("#chart"), options);
            chart.render();

            function payments(){
                let time = $('[name=payment_time]').val();
                let status = $('[name=payment_status]').val();
                var url = "{{ route('admin.payment.statistics') }}";
                
                $.get(url, {
                    time: time,
                    payment_status: status,
                }, function(response) {
                    $('.time_type').text(time); 
                    $('.total_payment').text(response.total_payment.toFixed(2));

                    var upDown = `<small>Previous ${time} payment was zero</small>`;
                    if (response.payment_diff != 0) {
                        if (response.up_down == 'up') { 
                            var className = 'success'
                        } else {  
                            var className = 'danger';  
                        }
                        upDown =
                            `<span class="badge badge-${className}-inverse font-16">${response.payment_diff}%<i class="las la-arrow-${response.up_down}"></i></span>`;
                    }
  
                    $('.up_down').html(upDown);
                    
                    $('.payment_canvas').html(
                        '<canvas height="150" id="payment_chart" class="chartjs-chart mt-4"></canvas>'
                    )

                    var ctx = document.getElementById('payment_chart');
                    var myChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: Object.keys(response.payments),
                            datasets: [{
                                data: Object.values(response.payments),
                                backgroundColor: [
                                    @for ($i = 0; $i < 365; $i++)
                                        '#6c5ce7',
                                    @endfor
                                ],
                                borderColor: [
                                    'rgba(231, 80, 90, 0.75)'
                                ],
                                borderWidth: 0,
                            }]
                        },
                        options: {
                            aspectRatio: 1,
                            responsive: true,
                            maintainAspectRatio: true,
                            elements: {
                                line: {
                                    tension: 0 // disables bezier curves
                                }
                            },
                            scales: {
                                xAxes: [{
                                    display: false
                                }],
                                yAxes: [{
                                    display: false
                                }]
                            },
                            legend: {
                                display: false,
                            },
                            tooltips: {
                                callbacks: {
                                    label: (tooltipItem, data) => data.datasets[0].data[
                                        tooltipItem.index] + ' {{ gs('cur_text') }}'
                                }
                            }
                        }
                    });
                });
            }

            payments();
            $('[name=payment_time], [name=payment_status]').on('change', function() {
                payments();
            });

            var pieChartID = document.getElementById("payment_currency_canvas").getContext('2d');
            var pieChart = new Chart(pieChartID, {
                type: 'pie',
                data: {
                    datasets: [{
                        data: [@foreach($paymentByCurrency as $data) {{ @$data->amount }}, @endforeach],
                        borderColor: 'transparent',
                        backgroundColor: planColors()
                    }]
                },
                options: {
                    responsive: true,
                    legend: {
                        display: false
                    },
                    tooltips: {
                        callbacks: {
                            label: (tooltipItem, data) => data.datasets[0].data[
                                tooltipItem.index] + ' {{ gs('cur_text') }}'
                        }
                    }
                }
            });

            var planPoints = $('.planPoint');
            planPoints.each(function(key, planPoint) {
                var planPoint = $(planPoint)
                planPoint.css('color', planColors()[key])
            });

            function paymentCharge(){
                let year = $('[name=payment_charge_year]').val();
                let month = $('[name=payment_charge_month]').val();
                let url = "{{ route('admin.payment.charge') }}"
                $.get(url, {
                    year: year,
                    month: month
                }, function(response) { 

                    $('.payment_charge').html(
                        '<canvas height="110" id="payment_charge_canvas"></canvas>'
                    ) 

                    var ctx = document.getElementById('payment_charge_canvas');
                    var myChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: Object.keys(response.charges),
                            datasets: [{
                                data: Object.values(response.charges),
                                backgroundColor: [
                                    @for ($i = 0; $i < 365; $i++)
                                        '#6c5ce7',
                                    @endfor
                                ],
                                borderColor: [
                                    'rgba(231, 80, 90, 0.75)'
                                ],
                                borderWidth: 0,
                            }]
                        },
                        options: {
                            aspectRatio: 1,
                            responsive: true,
                            maintainAspectRatio: true,
                            elements: {
                                line: {
                                    tension: 0 // disables bezier curves
                                }
                            },
                            scales: {
                                xAxes: [{
                                    display: true
                                }],
                                yAxes: [{
                                    display: true
                                }]
                            },
                            legend: {
                                display: false,
                            },
                            tooltips: {
                                callbacks: {
                                    label: (tooltipItem, data) => data.datasets[0].data[
                                        tooltipItem.index] + ' {{ gs('cur_text') }}'
                                }
                            }
                        }
                    });
                });
            } 
 
            paymentCharge();
            $('[name=payment_charge_year], [name=payment_charge_month]').on('change', function() {
                paymentCharge(); 
            });

            $('[name=payment_charge_month]').on('change', function() {
                $('[name=payment_charge_year]').trigger('change');
            });

            var doughnutChartID = document.getElementById("payment_by_plan").getContext('2d');
            var doughnutChart = new Chart(doughnutChartID, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [@foreach($paymentByGateway as $data) {{ $data->amount }}, @endforeach],
                        borderColor: 'transparent',
                        backgroundColor: planColors(),
                    }],
                },
                options: {
                    responsive: true,
                    cutoutPercentage: 75,
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: false,
                        text: 'Chart.js Doughnut Chart'
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    },
                    tooltips: {
                        callbacks: {
                            label: (tooltipItem, data) => data.datasets[0].data[tooltipItem.index] +
                                ' {{ gs('cur_text') }}'
                        }
                    }
                }
            });
 
            var planPointInterests = $('.planPointInterest');
            planPointInterests.each(function(key, planPointInterest) {
                var planPointInterest = $(planPointInterest)
                planPointInterest.css('color', planColors()[key])
            })

            function planColors() {
                return [
                    '#ff7675',
                    '#6c5ce7',
                    '#ffa62b',
                    '#ffeaa7',
                    '#D980FA',
                    '#fccbcb',
                    '#45aaf2',
                    '#05dfd7',
                    '#FF00F6',
                    '#1e90ff',
                    '#2ed573',
                    '#eccc68',
                    '#ff5200',
                    '#cd84f1',
                    '#7efff5',
                    '#7158e2',
                    '#fff200',
                    '#ff9ff3',
                    '#08ffc8',
                    '#3742fa',
                    '#1089ff',
                    '#70FF61',
                    '#bf9fee',
                    '#574b90'
                ]
            }

            let chartToggle = $('.chart-info-toggle');
            let chartContent = $(".chart-info-content");
            if (chartToggle || chartContent) {
                chartToggle.each(function() {
                    $(this).on("click", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $(this).siblings().toggleClass("is-open");
                    });
                });
                chartContent.each(function() {
                    $(this).on("click", function(e) {
                        e.stopPropagation();
                    });
                });
                $(document).on("click", function() {
                    chartContent.removeClass("is-open");
                });
            }

            $('.exit-btn').on('click', function() {
                $(this).toggleClass('active');
            });
        })(jQuery);
        var elems = document.querySelector(".full-view");

        function openFullscreen() {
            if (elems.requestFullscreen) {
                elems.requestFullscreen();
            } else if (elems.mozRequestFullScreen) {
                /* Firefox */
                elems.mozRequestFullScreen();
            } else if (elems.webkitRequestFullscreen) {
                /* Chrome, Safari & Opera */
                elems.webkitRequestFullscreen();
            } else if (elems.msRequestFullscreen) {
                /* IE/Edge */
                elems.msRequestFullscreen();
            }
        }

        function closeFullscreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                /* Firefox */
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                /* Chrome, Safari and Opera */
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                /* IE/Edge */
                document.msExitFullscreen();
            }
        }
    </script> 
@endpush
