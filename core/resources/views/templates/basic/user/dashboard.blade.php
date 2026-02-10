@extends($activeTemplate . 'layouts.master')

@section('content')
@include($activeTemplate.'partials.notice')

<div class="row mb-3 gy-4 mb-5">
    <div class="col-12 text-end">
        <select name="payment_statistics" class="widget_select text--white">
            <option value="today">@lang('Today')</option>
            <option value="week">@lang('This Week')</option>
            <option value="month">@lang('This Month')</option>
            <option value="year" selected>@lang('This Year')</option>
        </select>
        <select name="payment_status" class="widget_select text--white ms-1">
            <option value="" selected>@lang('All')</option>
            <option value="initiated">@lang('Initiated')</option> 
            <option value="successful">@lang('Succeed')</option> 
            <option value="rejected">@lang('Canceled')</option> 
        </select>
    </div>
    <div class="html"></div>
</div>

<div class="row align-items-center mb-3">
    <div class="col-12">
        <div class="justify-content-between d-flex flex-wrap align-items-center">
            <h6>@lang('Latest Transactions')</h6>
            <div class="row">
                <x-export />
            </div>
        </div>
    </div>
</div>

<div class="accordion table--acordion" id="transactionAccordion">
    @forelse ($latestTrx as $trx)
        <div class="accordion-item transaction-item {{@$trx->trx_type == '-' ? 'sent-item':'rcv-item'}}">
            <h2 class="accordion-header" id="h-{{$loop->iteration}}">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-{{$loop->iteration}}" aria-expanded="false" aria-controls="c-1">
                <div class="col-lg-3 col-sm-4 col-6 order-1 icon-wrapper">
                    <div class="left">
                        <div class="icon">
                            <i class="las la-long-arrow-alt-right text--{{ @$trx->trx_type == '+' ? 'success' : 'danger' }}"></i>
                        </div>
                        <div class="content">
                            <h6 class="trans-title">{{__(ucwords(str_replace('_',' ',@$trx->remark)))}}</h6>
                            <span class="text-muted font-size--14px mt-2">{{showDateTime(@$trx->created_at,'M d Y @g:i:a')}}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-sm-5 col-12 order-sm-2 order-3 content-wrapper mt-sm-0 mt-3">
                    <p class="text-muted font-size--14px"><b>{{__(@$trx->details)}}</b></p>
                </div>
                <div class="col-lg-3 col-sm-3 col-6 order-sm-3 order-2 text-end amount-wrapper">
                    <p><b>{{showAmount(@$trx->amount)}}</b></p>
                </div>
            </button>
            </h2>
            <div id="c-{{$loop->iteration}}" class="accordion-collapse collapse" aria-labelledby="h-1" data-bs-parent="#transactionAccordion">
                <div class="accordion-body">
                    <ul class="caption-list">
                        <li>
                            <span class="caption">@lang('Transaction ID')</span>
                            <span class="value">{{@$trx->trx}}</span>
                        </li>
                        @if($trx->charge > 0)
                            <li>
                                <span class="caption">@lang('Charge')</span>
                                <span class="value">{{showAmount(@$trx->charge)}}</span>
                            </li>
                        @endif
                        <li>
                            <span class="caption">@lang('Transacted Amount')</span>
                            <span class="value">{{showAmount(@$trx->amount)}}</span>
                        </li>
                        <li>
                            <span class="caption">@lang('Remaining Balance')</span>
                            <span class="value">{{showAmount(@$trx->post_balance)}}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div><!-- transaction-item end -->
    @empty
        <div class="accordion-body text-center">
            <x-empty-message h4="{{ true }}" />
        </div>
    @endforelse
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
    .widget-wrapper{
         background: #fff;
         border: 1px solid #e5e5e5;
         border-radius: 5px;
     }
    .payment-statistics {
        padding: 8px 20px;
        text-align: center;
    }
   .widget_select {
        border: 1px solid #d3d3d3;
        border-radius: 3px;
    }
    .border--end {
        border-right: 1px solid #e5e5e5; 
    }
    @media (max-width: 1199px) {
        .border--end {
            border-right: 0; 
        }
    }
    .reports {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        margin-right: 11px;
    }
    @media (max-width: 1199px) {
        .reports { 
            border-top: 1px solid #e5e5e5;
        margin-right: 0;
        }
    }
    @media (max-width: 1199px) {
        .payment-statistics { 
            margin-bottom: 22px;
        }
    }
    @media (max-width: 575px) {
        .payment-statistics { 
            margin-bottom: 12px;
        }
    }
    .reports::before {
        position: absolute;
        content: ""; 
        width: .1px; 
        height: 100%; 
        left: 50%;
        top: 50%; 
        transform: translate(-50%, -50%); 
        background: #ddd; 
    }
    .reports::after {
        position: absolute;
        content: ""; 
        width: 100%; 
        height: 2px; 
        left: 50%;
        top: 50%; 
        transform: translate(-50%, -50%); 
        background: #c5c5c5; 
    }
    .widget-card-wrapper {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }
    .widget-card {
        width: 50%;
        border-bottom: 1px solid #e5e5e5;
        padding: 31px 15px;
    }
    @media (max-width: 424px) {
        .widget-card { 
            padding: 15px 10px;  
        }
    }
    .widget-card-success .widget-card {
        background-color: rgba(40, 199, 111, 0.1); 
    }
    .widget-card-warning .widget-card {
        background-color: rgba(255, 159, 67, 0.1); 
    }
    .widget-card:nth-of-type(4n+2), .widget-card:nth-of-type(4n+4) {
        border-right: 0;
    }
    .widget-card:nth-of-type(4n+3), .widget-card:nth-of-type(4n+4) {
        border-bottom: 0;
    }
    .widget-card__number {
        margin-bottom: 5px;
    }
    .widget-card p {
        font-size: 14px; 
    }
    @media (max-width: 424px) {
        .widget-card__number {
            font-size: 15px;
        }
        .widget-card p {
            font-size: 12px; 
        }
    }
    .widget_select {
        padding: 3px 3px;
        font-size: 13px;
    }
    .payment-statistics {
        padding: 8px 20px;
        text-align: center;
    }

    .no-data-found {
        position: absolute;
        left: 52%;
        top: 50%;
        transform: translate(-50%, -50%);
        padding-left: 0;
        padding-right: 0;
    }
</style>
@endpush

@push('script')
    <script src="{{ asset('assets/admin/js/vendor/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/chart.js.2.8.0.js') }}"></script>

    <script>
        statistics();

        $(document).on('change', '[name=payment_statistics], [name=payment_status]', function() {
            statistics();
        });

        function statistics() {
            var url = "{{ route('user.dashboard.statistics') }}";
            var time = $('[name=payment_statistics] option:selected').val();
            var text = $('[name=payment_statistics] option:selected').text();
            var status = $('[name=payment_status] option:selected').val();

            $.get(url, {
                time: time,
                status: status,
            }, function(response) {
        
                $('.html').html(response.view);

                $('.payment_canvas').html(
                    '<canvas height="190" id="payment_chart" class="mt-4"></canvas>'
                )
                $('.payment-statistics').removeClass('no-data-found').text('Payment Statistics');

                if(Object.keys(response.payments).length == 0){
                    return $('.payment-statistics').addClass('no-data-found').text('No Data Found');
                }
                
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
                                    tooltipItem.index] + ' {{ gs("cur_text") }}'
                            }
                        }
                    }
                });
            });
        }
    </script>
@endpush

