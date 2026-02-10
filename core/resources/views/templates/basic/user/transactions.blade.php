@extends($activeTemplate . 'layouts.master')

@php
    $request = request();
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="page-heading mb-4">
                <h3 class="mb-2">{{ __($pageTitle) }}</h3>
                <p>
                    @lang('Get a clear view of your account\'s financial activity with our transaction history page, providing detailed insights into your past transactions. Stay informed, monitor your spending, and keep control of your finances at all times.')
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
                                <label>@lang('Type')</label>
                                <select name="trx_type">
                                    <option value="">@lang('All')</option>
                                    <option value="+" @selected($request->trx_type == '+')>@lang('Plus')</option>
                                    <option value="-" @selected($request->trx_type == '-')>@lang('Minus')</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="custom-input-box">
                                <label>@lang('Remark')</label>
                                <select name="remark">
                                    <option value="">@lang('All')</option>
                                    @foreach ($remarks as $remark)
                                        <option value="{{ $remark->remark }}" @selected($request->remark == $remark->remark)>
                                            {{ __(keyToTitle($remark->remark)) }}
                                        </option>
                                    @endforeach
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
        <div class="col-12">
            <div class="card custom--card border-0">
                <div class="card-body p-0">
                    <div class="accordion table--acordion" id="transactionAccordion">
                        @forelse ($transactions as $trx)
                            <div class="accordion-item transaction-item {{ @$trx->trx_type == '-' ? 'sent-item' : 'rcv-item' }}">
                                <h2 class="accordion-header" id="h-{{ $loop->iteration }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#c-{{ $loop->iteration }}" aria-expanded="false"
                                        aria-controls="c-1">
                                        <div class="col-lg-3 col-sm-4 col-6 order-1 icon-wrapper">
                                            <div class="left">
                                                <div class="icon">
                                                    <i class="las la-long-arrow-alt-right text--{{ @$trx->trx_type == '+' ? 'success' : 'danger' }}"></i>
                                                </div>
                                                <div class="content">
                                                    <h6 class="trans-title">
                                                        {{ __(ucwords(str_replace('_', ' ', @$trx->remark))) }}</h6>
                                                    <span
                                                        class="text-muted font-size--14px mt-2">{{ showDateTime(@$trx->created_at, 'M d Y @g:i:a') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-5 col-12 order-sm-2 order-3 content-wrapper mt-sm-0 mt-3">
                                            <p class="text-muted font-size--14px"><b>{{ __(@$trx->details) }}</b></p>
                                        </div>
                                        <div class="col-lg-3 col-sm-3 col-6 order-sm-3 order-2 text-end amount-wrapper">
                                            <p><b>{{ showAmount(@$trx->amount) }}</b></p>
                                        </div>
                                    </button>
                                </h2>
                                <div id="c-{{ $loop->iteration }}" class="accordion-collapse collapse" aria-labelledby="h-1"
                                    data-bs-parent="#transactionAccordion">
                                    <div class="accordion-body">
                                        <ul class="caption-list">
                                            <li>
                                                <span class="caption">@lang('Transaction ID')</span>
                                                <span class="value">{{ @$trx->trx }}</span>
                                            </li>
                                            @if ($trx->charge > 0)
                                                <li>
                                                    <span class="caption">@lang('Charge')</span>
                                                    <span class="value">{{ showAmount(@$trx->charge) }}</span>
                                                </li>
                                            @endif
                                            <li>
                                                <span class="caption">@lang('Transacted Amount')</span>
                                                <span class="value">{{ showAmount(@$trx->amount) }}</span>
                                            </li>
                                            <li>
                                                <span class="caption">@lang('Remaining Balance')</span>
                                                <span class="value">{{ showAmount(@$trx->post_balance) }}</span>
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
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="mt-3">
                @if ($transactions->hasPages())
                    {{ paginatelinks($transactions) }}
                @endif
            </div>
        </div>
    </div>
@endsection

@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
@endpush

@push('script')
    <script> 
        (function ($) {
            "use strict";
            $('[name=trx_type], [name=remark]').on('change', function(){
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
