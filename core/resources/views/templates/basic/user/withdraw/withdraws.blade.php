@extends($activeTemplate . 'layouts.master')

@php
    $request = request();
@endphp 

@section('content')
<div class="row justify-content-center gy-4">
    <div class="col-12">
        <div class="page-heading mb-4">
            <h3 class="mb-2">{{ __($pageTitle) }}</h3>
            <p>
                @lang('Take control of your earnings with our user-friendly withdraw page, featuring up-to-date information on your balance, next payout date, and withdrawal history. Stay informed and on top of your finances with ease')
            </p>
        </div>
        <hr>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 withdraw-detail-border">
                        <div class="withdraw-detail">
                            <h3 class="text-muted title">@lang('Your current balance is') 
                                <span class="text--success withdraw-detail__balance">
                                    {{ showAmount($user->balance) }}
                                </span>
                                <br>
                                @if(@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)
                                @lang('You\'ve') <span class="text--success withdraw-detail__balance">@if(@$user->withdrawSetting->amount > $user->balance){{ showAmount($user->balance) }}@else{{ showAmount(@$user->withdrawSetting->amount) }}@endif </span> @lang('for payout to your wallet.')
                                @else
                                <p class="mt-2 withdraw-detail__desc">
                                    @lang('Please, setup the payout method for withdrawals.')
                                </p>
                                @endif
                            </h3>
                            @if(@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)
                                <h4 class="text-muted mt-3 withdraw-detail__desc">@lang('Next payout request will create') : 
                                    <span class="text--primary" id="countdown">{{ showDateTime(@$user->withdrawSetting->next_withdraw_date, 'M d, Y') }}</span>
                                </h4>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="withdraw-method">
                            <h5 class="title mb-2">@lang('Payout Method')</h5>
                            @if(@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)
                                @if(@$user->withdrawSetting->withdrawMethod->image)
                                   <div class="withdraw-method-image">
                                        <img 
                                        src="{{ getImage(getFilePath('withdrawMethod').'/'. @$user->withdrawSetting->withdrawMethod->image,getFileSize('withdrawMethod'))}}" 
                                        alt="@lang('Image')" 
                                        class="w-25"
                                    >
                                   </div>
                                @endif
                            @else 
                                <h6 class="mt-2 text-muted withdraw-detail__desc">@lang('You\'ve no payout method')</h6>
                            @endif
                            <a class="btn btn--primary btn-sm mt-3" href="{{ route('user.withdraw.method') }}">@lang('Set Payout Method')</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mt-5">
            <div class="filter-area mb-3">
                <form action="" class="form">
                    <div class="d-flex flex-wrap gap-4">
                        <div class="flex-grow-1">
                            <div class="custom-input-box trx-search">
                                <label>@lang('Trx Number')</label>
                                <input type="text" name="search" value="{{ $request->search }}" placeholder="@lang('Trx Number')">
                                <button type="submit" class="icon-area">
                                    <i class="las la-search"></i>
                                </button>
                            </div>
                        </div> 
                        <div class="flex-grow-1">
                            <div class="custom-input-box trx-search">
                                <label>@lang('Date')</label>
                                <input name="date" type="search" class="datepicker-here date-range" placeholder="@lang('Start Date - End Date')" autocomplete="off" value="{{ request()->date }}">
                                <button type="submit" class="icon-area">
                                    <i class="las la-search"></i>
                                </button>
                            </div>
                        </div> 
                        <div class="flex-grow-1"> 
                            <div class="custom-input-box">
                                <label>@lang('Gateway')</label> 
                                <select name="method_id">
                                    <option value="">@lang('All')</option> 
                                    @foreach ($gateways as $data) 
                                        <option value="{{ @$data->method_id }}" @selected($request->method_id == @$data->method_id)>
                                            {{ __(@$data->method->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="custom-input-box">
                                <label>@lang('Status')</label>
                                <select name="status">
                                    <option value="">@lang('All')</option> 
                                    <option value="pending" @selected($request->status == 'pending')>@lang('Pending')</option> 
                                    <option value="approved" @selected($request->status == 'approved')>@lang('Approved')</option> 
                                    <option value="rejected" @selected($request->status == 'rejected')>@lang('Rejected')</option> 
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="row text-end mb-3">
                <div class="col-lg-12 d-flex flex-wrap justify-content-end">
                    <x-export />
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card custom--card border-0">
                <div class="card-body p-0">
                    <div class="accordion table--acordion" id="transactionAccordion">
                        @forelse ($withdraws as $withdraw)
                        <div class="accordion-item transaction-item 
                        @if($withdraw->status == Status::PAYMENT_PENDING)
                            trx-warning-badge
                        @elseif($withdraw->status == Status::PAYMENT_SUCCESS)
                            trx-success-badge
                        @elseif($withdraw->status == Status::PAYMENT_REJECT)
                            trx-danger-badge
                        @endif
                        ">
                            <h2 class="accordion-header" id="h-{{ $loop->iteration }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#c-{{ $loop->iteration }}" aria-expanded="false"
                                    aria-controls="c-1">
                                    <div class="col-lg-3 col-sm-4 col-6 order-1 icon-wrapper">
                                        <div class="left">
                                            <div class="icon rotate-none">
                                                @if($withdraw->status == Status::PAYMENT_PENDING)
                                                    <i class="las la-spinner text--warning"></i>
                                                @elseif($withdraw->status == Status::PAYMENT_SUCCESS)
                                                    <i class="las la-check text--success"></i>
                                                @elseif($withdraw->status == Status::PAYMENT_REJECT)
                                                    <i class="las la-times text--danger"></i>
                                                @endif
                                            </div>
                                            <div class="content">
                                                <h6 class="trans-title">{{ $withdraw->trx }}</h6>
                                                <span class="text-muted font-size--14px mt-2">{{ showDateTime(@$withdraw->created_at, 'M d Y @g:i:a') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-5 col-12 order-sm-2 order-3 content-wrapper mt-sm-0 mt-3">
                                        <p class="text-muted font-size--14px">
                                            <b>@lang('Withdraw processed via') {{ __(@$withdraw->method->name) }}</b>
                                            <p class="mt-1"><small>@lang('Requestd by System')</small></p>
                                        </p>
                                    </div>
                                    <div class="col-lg-3 col-sm-3 col-6 order-sm-3 order-2 text-end amount-wrapper">
                                        <p><b>{{ showAmount(@$withdraw->amount) }}</b></p>
                                    </div>
                                </button>
                            </h2> 
                            <div id="c-{{ $loop->iteration }}" class="accordion-collapse collapse" aria-labelledby="h-1" data-bs-parent="#transactionAccordion">
                                <div class="accordion-body">
                                    <ul class="caption-list">                           
                                        <li>
                                            <span class="caption">@lang('Gateway | Currency')</span>
                                            <span class="value">{{ __(@$withdraw->method->name) }} - {{ __($withdraw->currency) }}</span>
                                        </li>  
                                        <li>
                                            <span class="caption">@lang('Amount')</span>
                                            <span class="value">{{ showAmount($withdraw->amount) }}</span>
                                        </li>
                                        <li>
                                            <span class="caption">@lang('Charge')</span>
                                            <span class="value">{{ showAmount($withdraw->charge) }}</span>
                                        </li>
                                        <li>
                                            <span class="caption">@lang('After Charge')</span>
                                            <span class="value">{{ showAmount($withdraw->amount - $withdraw->charge) }}</span>
                                        </li>
                                        <li>
                                            <span class="caption">@lang('Currency Conversion')</span>
                                            <span class="value">{{ showAmount($withdraw->amount - $withdraw->charge) }} x {{ showAmount($withdraw->rate, currencyFormat:false) }} {{ __($withdraw->currency) }} = {{ showAmount($withdraw->final_amount, currencyFormat:false) }} {{ __($withdraw->currency) }}</span>
                                        </li>
                                        <li>
                                            <span class="caption">@lang('Withdraw Details')</span>
                                            <span class="value">
                                                <a href="javascript:void(0)" class="detailBtn"
                                                    data-user_data="{{ json_encode($withdraw->withdraw_information) }}"
                                                    @if ($withdraw->status == Status::PAYMENT_REJECT)
                                                        data-admin_feedback="{{ $withdraw->admin_feedback }}"
                                                    @endif
                                                >@lang('See Withdraw Details')</a>
                                            </span>
                                        </li>
                                        <li>
                                            <span class="caption">@lang('Status')</span>
                                            <span class="value">@php echo $withdraw->statusBadge @endphp</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="accordion-body text-center">
                            <x-empty-message h4="{{ true }}" />
                        </div>
                        @endforelse
                    </div>
                </div>
            </div><!-- custom--card end -->
        </div>

        <div class="col-12">
            <div class="mt-3">
                @if ($withdraws->hasPages())
                    {{ paginatelinks($withdraws) }}
                @endif
            </div>
        </div>
    </div>
</div>

<div id="detailModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">@lang('Withdraw Details')</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group list-group-flush userData mb-2">
                </ul>
                <div class="feedback"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--dark btn-sm" data-bs-dismiss="modal">@lang('Close')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}">
@endpush

@push('style')
<style>
    .border-line-area.style-two{
        text-align: center;
        position: relative;
        z-index: 1;
    }
    .border-line-area.style-two .border-line-title {
        display: inline-block;
        margin-bottom: 0 !important;
        background: #fff;
        padding: 10px;
        padding-bottom: 5px;
    }
    .border-line-title-wrapper {
        position: relative;
    }
    .border-line-title-wrapper::before {
        position: absolute;
        content: "";
        width: 100%;
        height: 0.1px;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        background-color: #e5e5e5;
        z-index: -1;
    }

    .withdraw-detail__balance {
        font-size: 36px; 
    }
    .withdraw-detail-border {
        border-right: 1px solid #dee2e6; 
    }
    @media (max-width: 1399px) {
        .withdraw-detail__desc {
            font-weight: 500;
            font-size: 17px
        }
        .text-muted.title {
            font-size: 20px;
        }
        .withdraw-detail__balance {
            font-size: 32px; 
        }
    }
    @media (max-width: 767px) {
        .withdraw-detail-border {
            border-right: 0; 
            border-bottom: 1px solid #dee2e6!important; 
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .withdraw-detail__desc {
            font-size: 16px
        }
    }
    .withdraw-method-image img{
        max-width: 80px;
        max-height: 80px;
    }


</style>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
@endpush

@push('script')
    <script>
        (function ($) {
            "use strict";

            @if(@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)

                var countDownDate = new Date("{{ showDateTime(@$user->withdrawSetting->next_withdraw_date, 'M d, Y') }}").getTime();
                var x = setInterval(function() {
                var now = new Date().getTime();
                var distance = countDownDate - now;
                    
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                document.getElementById("countdown").innerHTML = days + "d " + hours + "h "+ minutes + "m " + seconds + "s ";
                    
                if (distance < 0) {
                    clearInterval(x);
                    document.getElementById("countdown").innerHTML = "Created";
                }
                }, 1000);

            @endif

            $('.detailBtn').on('click', function () {
                var modal = $('#detailModal');
                var userData = $(this).data('user_data');
                var html = ``;
                userData.forEach(element => {
                    if(element.type != 'file'){
                        html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <span class='fw-bold'>${element.name}</span>
                            <span">${element.value}</span>
                        </li>`;
                    }
                });

                if(!html){
                    html = `<span class='text-center'>@lang('No data found')</span>`;
                }

                modal.find('.userData').html(html);

                if($(this).data('admin_feedback') != undefined){
                    var adminFeedback = `
                        <div class="ms-3 border-line-area style-two">
                            <div class="border-line-title-wrapper">
                                <strong class="border-line-title">@lang('Admin Feedback')</strong>
                            </div>
                            <p class="text-start">${$(this).data('admin_feedback')}</p>
                        </div>
                    `;
                }else{
                    var adminFeedback = '';
                }

                modal.find('.feedback').html(adminFeedback);
                modal.modal('show');
            });

            $('[name=method_currency], [name=method_id], [name=status]').on('change', function(){
                $('.form').submit();
            })

            const datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                },
                showDropdowns: true,
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
            });

            const changeDatePickerText = (event, startDate, endDate) => {
                $(event.target).val(startDate.format('MMMM DD, YYYY') + ' - ' + endDate.format('MMMM DD, YYYY'));
            }

            $('.date-range').on('apply.daterangepicker', (event, picker) => changeDatePickerText(event, picker.startDate, picker.endDate));

            if ($('.date-range').val()) {
                let dateRange = $('.date-range').val().split(' - ');
                $('.date-range').data('daterangepicker').setStartDate(new Date(dateRange[0]));
                $('.date-range').data('daterangepicker').setEndDate(new Date(dateRange[1]));
            }
        })(jQuery);
    </script>
@endpush

