@extends('admin.layouts.app')

@php
    $apiPayment = $deposit->apiPayment;
@endphp

@section('panel')
    <div class="row mb-none-30 justify-content-center">
        <div class="col-xl-6 col-md-8 mb-30">
            <div class="card b-radius--10 overflow-hidden box--shadow1">
                <div class="card-header border-0 mt-2">
                    <div class="card-title text-center"><h5>@lang('Payment Via') {{ __(@$deposit->gateway->name) }}</h5></div>
                </div>
                <div class="card-body">
                    <nav>
                        <div class="nav nav-tabs mb-3 justify-content-center" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#nav-details" type="button" role="tab" aria-controls="nav-details" aria-selected="true">
                                @lang('Details')
                            </button>
                            <button class="nav-link" id="customer-tab" data-bs-toggle="tab" data-bs-target="#nav-customer" type="button" role="tab" aria-controls="nav-customer" aria-selected="false">
                                @lang('Customer')
                            </button>
                            <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#nav-shipping" type="button" role="tab" aria-controls="nav-shipping" aria-selected="false">
                                @lang('Shipping')
                            </button>
                            <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#nav-billing" type="button" role="tab" aria-controls="nav-billing" aria-selected="false">
                                @lang('Billing')
                            </button>
                            @if($deposit->status == Status::PAYMENT_REJECT && @$apiPayment->cancel_reason)
                            <button class="nav-link" id="reason-tab" data-bs-toggle="tab" data-bs-target="#nav-reason" type="button" role="tab" aria-controls="nav-reason" aria-selected="false">
                                @lang('Reason')
                            </button>
                            @endif
                        </div>
                    </nav>
                    <div class="tab-content ms-2" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-details" role="tabpanel" aria-labelledby="details-tab">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Date')
                                    <span class="fw-bold">{{ showDateTime($deposit->created_at) }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Transaction Number')
                                    <span class="fw-bold">{{ $deposit->trx }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Username')
                                    <span class="fw-bold">
                                        <a href="{{ route('admin.users.detail', $deposit->user_id) }}">{{ @$deposit->user->username }}</a>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Method')
                                    <span class="fw-bold">{{ __(@$deposit->gateway->name) }}</span>
                                </li>
                                @if($deposit->stripeAccount)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                        @lang('Stripe Account')
                                        <span class="fw-bold">{{ __($deposit->stripeAccount->name) }}</span>
                                    </li>
                                @endif
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Amount')
                                    <span class="fw-bold">{{ showAmount($deposit->amount ) }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Charge')
                                    <span class="fw-bold">{{ showAmount($deposit->charge ) }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Payment Charge')
                                    <span class="fw-bold">{{ showAmount($deposit->payment_charge ) }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('After Charge')
                                    <span class="fw-bold">{{ showAmount($deposit->amount-$deposit->totalCharge ) }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Rate')
                                    <span class="fw-bold">1 {{__(gs('cur_text'))}}
                                        = {{ showAmount($deposit->rate, currencyFormat:false) }} {{__($deposit->baseCurrency())}}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Payable')
                                    <span class="fw-bold">{{ showAmount($deposit->final_amount, currencyFormat:false) }} {{__($deposit->method_currency)}}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Site Name')
                                    <span class="fw-bold">{{ @$apiPayment->site_name }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                    @lang('Status')
                                    @php echo $deposit->statusBadge @endphp
                                </li>
                                @if($deposit->admin_feedback)
                                    <li class="list-group-item">
                                        <strong>@lang('Admin Response')</strong>
                                        <br>
                                        <p>{{__($deposit->admin_feedback)}}</p>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <div class="tab-pane fade" id="nav-customer" role="tabpanel" aria-labelledby="customer-tab">
                            <ul class="list-group list-group-flush">
                                @foreach(@$apiPayment->customer ?? [] as $label => $value)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                        {{ keyToTitle($label) }}
                                        <span class="fw-bold">{{ __($value) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="tab-pane fade" id="nav-shipping" role="tabpanel" aria-labelledby="shipping-tab">
                            <ul class="list-group list-group-flush">
                                @foreach(@$apiPayment->shipping_info ?? [] as $label => $value)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                        {{ keyToTitle($label) }}
                                        <span class="fw-bold">{{ $value ? __($value) : __('N/A') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="tab-pane fade" id="nav-billing" role="tabpanel" aria-labelledby="billing-tab">
                            <ul class="list-group list-group-flush">
                                @foreach(@$apiPayment->billing_info ?? [] as $label => $value)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 flex-wrap">
                                        {{ keyToTitle($label) }}
                                        <span class="fw-bold">{{ $value ? __($value) : __('N/A') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @if($deposit->status == Status::PAYMENT_REJECT && @$apiPayment->cancel_reason)
                            <div class="tab-pane fade" id="nav-reason" role="tabpanel" aria-labelledby="reason-tab">
                                @php echo nl2br(@$apiPayment->cancel_reason); @endphp
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
 
@push('style')
    <style>
        @media (max-width: 575px) {
            .nav-link {
                padding: 5px 10px;
                font-size: 15px;
            }
        }
        @media (max-width: 424px) {
            .nav-link {
                padding: 5px;
                font-size: 14px;
            }
        }
    </style>
@endpush
