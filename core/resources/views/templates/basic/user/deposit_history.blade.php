@extends($activeTemplate.'layouts.master')

@php
    $request = request();
    $showHeaderBalance = true;
@endphp

@section('content')
<div class="row justify-content-center">


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
                <div class="table-responsive">
                    <table class="table deposit-history-table">
                        <thead>
                            <tr>
                                <th>@lang('Transaction')</th>
                                <th>@lang('Customer')</th>
                                <th>@lang('Email')</th>
                                <th>@lang('Phone')</th>
                                <th class="text-end">@lang('Amount')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($deposits as $deposit)
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
                                    <td class="fw-semibold">#{{ $deposit->trx }}</td>
                                    <td>{{ $customerName ?: __('N/A') }}</td>
                                    <td>{{ $customerEmail ?: __('N/A') }}</td>
                                    <td>{{ $customerPhone ?: __('N/A') }}</td>
                                    <td class="text-end {{ $deposit->status == Status::PAYMENT_REJECT ? 'amount-negative' : 'amount-positive' }}">
                                        {{ showAmount(@$deposit->amount) }}
                                    </td>
                                    <td><span class="status-badge {{ $statusClass }}">{{ __($statusLabel) }}</span></td>
                                    <td>{{ showDateTime(@$deposit->created_at, 'M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="7">{{ __('Data not found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
    .deposit-history-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .deposit-history-table thead th {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #e5e7eb;
        padding: 12px 10px;
        white-space: nowrap;
    }

    .deposit-history-table tbody td {
        font-size: 13px;
        color: #0f172a;
        padding: 14px 10px;
        border-bottom: 1px solid #eef2f6;
        vertical-align: middle;
    }

    .deposit-history-table tbody tr:last-child td {
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

    .customer-cell {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .customer-name {
        font-weight: 600;
        color: #0f172a;
    }

    .customer-meta {
        font-size: 12px;
        color: #6b7280;
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
