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
                    <div class="table-responsive">
                        <table class="table table--light style--two transaction-table">
                            <thead>
                                <tr>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Fee')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Customer')</th>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Description')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $trx)
                                    @php
                                        $statusLabel = 'Succeeded';
                                        $statusClass = 'success';
                                        if (@$trx->remark === 'refund') {
                                            $statusLabel = 'Refunded';
                                            $statusClass = 'warning';
                                        } elseif (@$trx->remark === 'withdraw') {
                                            $statusLabel = 'Pending';
                                            $statusClass = 'warning';
                                        } elseif (@$trx->remark === 'withdraw_reject') {
                                            $statusLabel = 'Rejected';
                                            $statusClass = 'danger';
                                        } elseif (in_array(@$trx->remark, ['gateway_charge', 'payment_charge'])) {
                                            $statusLabel = 'Fee';
                                            $statusClass = 'info';
                                        } elseif (@$trx->trx_type === '-') {
                                            $statusLabel = 'Debit';
                                            $statusClass = 'danger';
                                        }

                                        $amountPrefix = @$trx->trx_type === '-' ? '-' : '+';
                                        $amountClass = @$trx->trx_type === '-' ? 'text--danger' : 'text--success';
                                        $customerLabel = auth()->user()->email ?? auth()->user()->username;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="amount-cell">
                                                <span class="amount {{ $amountClass }}">
                                                    {{ $amountPrefix }}{{ showAmount(@$trx->amount) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($trx->charge > 0)
                                                {{ showAmount(@$trx->charge) }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge--{{ $statusClass }}">{{ __($statusLabel) }}</span>
                                        </td>
                                        <td class="text-muted">
                                            {{ $customerLabel }}
                                        </td>
                                        <td>{{ showDateTime(@$trx->created_at, 'M d, Y @g:i A') }}</td>
                                        <td class="text-muted">{{ __(@$trx->details) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __('Data not found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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

@push('style')
<style>
    .transaction-table th,
    .transaction-table td {
        vertical-align: middle;
        font-size: 14px;
    }
    .transaction-table td {
        padding-top: 16px;
        padding-bottom: 16px;
    }
    .amount-cell .amount {
        font-weight: 600;
        font-size: 15px;
    }
    .transaction-table .badge {
        font-weight: 600;
        font-size: 12px;
    }
    .transaction-table thead th {
        white-space: nowrap;
    }
</style>
@endpush
