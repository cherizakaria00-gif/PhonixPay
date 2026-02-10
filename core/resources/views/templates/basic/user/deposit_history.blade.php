@extends($activeTemplate.'layouts.master')

@php
    $request = request();
@endphp

@section('content')
<div class="row justify-content-center">

    <div class="col-12">
        <div class="page-heading mb-4">
            <h3 class="mb-2">{{ __($pageTitle) }}</h3>
            <p>
                @lang('View and manage your transaction history with ease, and keep track of your payment over time. Stay informed and in control of your finances with our comprehensive payment history page.')
            </p>
        </div>
        <hr>
    </div>

    <div class="col-12">
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
                            <label>@lang('Currency')</label>
                            <select name="method_currency">
                                <option value="">@lang('All')</option> 
                                @foreach ($currencies as $currency) 
                                    <option value="{{ $currency }}" @selected($request->method_currency == $currency)>
                                        {{ __($currency) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex-grow-1"> 
                        <div class="custom-input-box">
                            <label>@lang('Gateway')</label> 
                            <select name="method_code">
                                <option value="">@lang('All')</option> 
                                @foreach ($gateways as $data) 
                                    <option value="{{ @$data->method_code }}" @selected($request->method_code == @$data->method_code)>
                                        {{ __(@$data->gateway->name) }}
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
                                <option value="initiated" @selected($request->status == 'initiated')>@lang('Initiated')</option> 
                                <option value="successful" @selected($request->status == 'successful')>@lang('Succeed')</option> 
                                <option value="rejected" @selected($request->status == 'rejected')>@lang('Canceled')</option> 
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="row mb-3">
            <div class="col-lg-12 justify-content-end d-flex">
                <x-export />
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card custom--card border-0">
            <div class="card-body p-0">
                <div class="accordion table--acordion" id="transactionAccordion">
                    @forelse ($deposits as $deposit)
                    <div class="accordion-item transaction-item
                    @if($deposit->status == Status::PAYMENT_INITIATE)
                    trx-dark-badge
                    @elseif($deposit->status == Status::PAYMENT_SUCCESS)
                    trx-success-badge
                    @elseif($deposit->status == Status::PAYMENT_REJECT)
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
                                            @if($deposit->status == Status::PAYMENT_INITIATE)
                                                <i class="las la-plus text--dark"></i>
                                            @elseif($deposit->status == Status::PAYMENT_SUCCESS)
                                                <i class="las la-check text--success"></i>
                                            @elseif($deposit->status == Status::PAYMENT_REJECT)
                                                <i class="las la-times text--danger"></i>
                                            @endif
                                        </div>
                                        <div class="content">
                                            <h6 class="trans-title">{{ $deposit->trx }}</h6>
                                            <span class="text-muted font-size--14px mt-2">{{ showDateTime(@$deposit->created_at, 'M d Y @g:i:a') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="col-lg-6 col-sm-5 col-12 order-sm-2 order-3 content-wrapper mt-sm-0 mt-3">
                                    <p class="text-muted font-size--14px">
                                        <b>@lang('Payment processed via') {{ __(@$deposit->gateway->name) }}</b>
                                        <p class="mt-1"><small>@lang('Requestd by') <b>{{ @$deposit->apiPayment->customer->first_name.' '.@$deposit->apiPayment->customer->first_name }}</b> (<a href="mailto:{{ @$deposit->apiPayment->customer->email }}">{{ @$deposit->apiPayment->customer->email }}</a>)</small></p>
                                    </p>
                                </div>
                                <div class="col-lg-3 col-sm-3 col-6 order-sm-3 order-2 text-end amount-wrapper">
                                    <p><b>{{ showAmount(@$deposit->amount) }}</b></p>
                                </div>
                            </button>
                        </h2>
                        <div id="c-{{ $loop->iteration }}" class="accordion-collapse collapse" aria-labelledby="h-1" data-bs-parent="#transactionAccordion">
                            <div class="accordion-body">
                                <ul class="caption-list">
                                    <li>
                                        <span class="caption">@lang('Gateway | Currency')</span>
                                        <span class="value">{{ __(@$deposit->gateway->name) }} - {{ __($deposit->method_currency) }}</span>
                                    </li>
                                    <li>
                                        <span class="caption">@lang('Amount')</span>
                                        <span class="value">{{ showAmount($deposit->amount) }}</span>
                                    </li>
                                    <li>
                                        <span class="caption">@lang('Charge')</span>
                                        <span class="value">{{ showAmount($deposit->totalCharge) }}</span>
                                    </li>
                                    <li>
                                        <span class="caption">@lang('After Charge')</span>
                                        <span class="value">{{ showAmount($deposit->amount - $deposit->totalCharge) }}</span>
                                    </li>
                                    <li>
                                        <span class="caption">@lang('Currency Conversion')</span>
                                        <span class="value">{{ showAmount($deposit->amount - $deposit->totalCharge) }} x {{ showAmount($deposit->rate, currencyFormat:false) }} {{ __($deposit->method_currency) }} = {{ showAmount($deposit->final_amount, currencyFormat:false) }} {{ __($deposit->method_currency) }}</span>
                                    </li>
                                    <li>
                                        <span class="caption">@lang('Site Name')</span>
                                        <span class="value">{{ @$deposit->apiPayment->site_name }}</span>
                                    </li>
                                    <li>
                                        <span class="caption">@lang('Payment Details')</span>
                                        <span class="value"><a href="javascript:void(0)" class="detailBtn" data-payment="{{ $deposit->apiPayment }}">@lang('See Payment Details')</a></span>
                                    </li>
                                    <li>
                                        <span class="caption">@lang('Status')</span>
                                        <span class="value">@php echo $deposit->statusBadge @endphp</span>
                                    </li>
                                    @if($deposit->status == Status::PAYMENT_REJECT && @$deposit->apiPayment->cancel_reason)
                                    <li>
                                        <span class="caption">@lang('Reason')</span>
                                        <span class="value">{{ @$deposit->apiPayment->cancel_reason }}</span>
                                    </li>
                                    @endif
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
            @if ($deposits->hasPages())
                {{ paginatelinks($deposits) }}
            @endif
        </div>
    </div>

</div>

<div id="detailModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title">@lang('Payment Details')</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <nav>
                    <div class="nav nav-tabs mb-3" id="nav-tab" role="tablist">
                        <button class="nav-link active" id="customer-tab" data-bs-toggle="tab" data-bs-target="#nav-customer" type="button" role="tab" aria-controls="nav-customer" aria-selected="true">
                            @lang('Customer')
                        </button>
                        <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#nav-shipping" type="button" role="tab" aria-controls="nav-shipping" aria-selected="false">
                            @lang('Shipping')
                        </button>
                        <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#nav-billing" type="button" role="tab" aria-controls="nav-billing" aria-selected="false">
                            @lang('Billing')
                        </button>
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-customer" role="tabpanel" aria-labelledby="customer-tab">
                        <ul class="list-group list-group-flush customerData"> 
                        </ul>
                    </div>
                    <div class="tab-pane fade" id="nav-shipping" role="tabpanel" aria-labelledby="shipping-tab">
                        <ul class="list-group list-group-flush shippingData"> 
                        </ul>
                    </div>
                    <div class="tab-pane fade" id="nav-billing" role="tabpanel" aria-labelledby="billing-tab">
                        <ul class="list-group list-group-flush billingData"> 
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark btn-sm" data-bs-dismiss="modal">@lang('Close')</button>
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
    .capitalize{
        text-transform: capitalize;
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
            $('.detailBtn').on('click', function () {
                var modal = $('#detailModal');

                var customer = $(this).data('payment').customer;
                var shippingInfo = $(this).data('payment').shipping_info;
                var billingInfo = $(this).data('payment').billing_info;

                var customerData = $('.customerData');
                var shippingData = $('.shippingData');
                var billingData = $('.billingData');

                customerData.html('');
                shippingData.html('');
                billingData.html('');

                $.each(customer, function(key, value) {
                    var data = `
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <span class='fw-bold capitalize'>${key.replaceAll('_', ' ')}</span>
                            <span">${value}</span>
                        </li>`;

                    customerData.append(data);
                });

                $.each(shippingInfo, function(key, value) {
                    var data = `
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <span class='fw-bold capitalize'>${key.replaceAll('_', ' ')}</span>
                            <span">${value ?? 'N/A'}</span>
                        </li>`;

                    shippingData.append(data);
                });

                $.each(billingInfo, function(key, value) {
                    var data = `
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <span class='fw-bold capitalize'>${key.replaceAll('_', ' ')}</span>
                            <span">${value ?? 'N/A'}</span>
                        </li>`;

                    billingData.append(data);
                });

                modal.modal('show');
            });

            $('[name=method_currency], [name=method_code], [name=status]').on('change', function(){
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




