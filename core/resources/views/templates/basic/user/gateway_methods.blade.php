@extends($activeTemplate . 'layouts.master')

@php
    $search = request()->search;
@endphp

@section('content')
    <div class="row justify-content-center">

        <div class="col-12">
            <div class="page-heading mb-4">
                <h3 class="mb-2">{{ __($pageTitle) }}</h3>
                <p>
                    @lang('Discover the ideal payment gateway for your business needs by comparing gateway limits and charges on our comprehensive list. Make informed decisions to optimize your transactions and maximize your profits.')
                </p>
            </div>
        </div>

        <div class="col-md-12">

            @if($gateways->where('crypto', 0)->count() && $gateways->where('crypto', 1)->count())
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="fiat-tab" data-bs-toggle="tab" href="#fiat" role="tab"
                            aria-controls="fiat" aria-selected="true">@lang('Fiat Gateways')</a>
                    </li> 
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="crypto-tab" data-bs-toggle="tab" href="#crypto" role="tab"
                            aria-controls="crypto" aria-selected="false">@lang('Crypto Gateways')</a>
                    </li>
                </ul>
            @endif

            <div class="tab-content mt-4" id="myTabContent">
                <div class="tab-pane {{ $gateways->where('crypto', 0)->count() ? 'fade show active' : null }}" id="fiat" role="tabpanel" aria-labelledby="fiat-tab">
                    <div class="card custom--card border-0">
                        <div class="card-body p-0">
                            <div class="accordion table--acordion" id="transactionAccordion">
                                @forelse ($gateways->sortBy('alias')->where('crypto', 0) as $gateway)
                                @php
                                    $displayAlias = $gateway->alias;
                                    $displayName = $gateway->name;

                                    if (strcasecmp($gateway->alias, 'StripeJs') === 0 || strcasecmp($gateway->name, 'Stripe Storefront') === 0) {
                                        $displayAlias = 'Credit card';
                                        $displayName = 'Credit card';
                                    } elseif (strcasecmp($gateway->alias, 'NowPaymentsCheckout') === 0 || strcasecmp($gateway->name, 'Now payments checkout') === 0) {
                                        $displayAlias = 'Cryptocurrency';
                                        $displayName = 'Cryptocurrency';
                                    }
                                @endphp
                                <div class="accordion-item transaction-item {{ @$trx->trx_type == '-' ? 'sent-item' : 'rcv-item' }}">
                                    <h2 class="accordion-header" id="h-{{ $loop->iteration }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#c-{{ $loop->iteration }}" aria-expanded="false"
                                            aria-controls="c-1">
                                            <div class="col-lg-3 col-sm-4 col-6 order-1 icon-wrapper">
                                                <div class="left">
                                                    <div class="icon rotate-none">
                                                        <i class="las la-credit-card"></i>
                                                    </div>
                                                    <div class="content">
                                                        <h6 class="trans-title">{{ __($displayAlias) }}</h6>
                                                        <span class="text-muted font-size--14px mt-2">@lang('Gateway method')</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-5 col-12 order-sm-2 order-3 content-wrapper mt-sm-0 mt-3">
                                                <p class="text-muted font-size--14px">
                                                    <b>@lang('Supported Currency') {{ __(@$deposit->gateway->name) }} ({{ @$gateway->currencies->count() }})</b>
                                                </p>
                                            </div>
                                            <div class="col-lg-3 col-sm-3 col-6 order-sm-3 order-2 text-end amount-wrapper">
                                                <p><b>{{ __($displayName) }}</b></p>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="c-{{ $loop->iteration }}" class="accordion-collapse collapse" aria-labelledby="h-1" data-bs-parent="#transactionAccordion">
                                        <div class="accordion-body">
                                            <ul class="caption-list">
                                                @foreach($gateway->currencies as $currency) 
                                                    <li>
                                                        <span class="caption">@lang('Currency')</span>
                                                        <span class="value">{{ __($currency->currency) }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="caption">@lang('Payment Range')</span>
                                                        <span class="value">
                                                            {{ showAmount($currency->min_amount) }} - 
                                                            {{ showAmount($currency->max_amount) }}
                                                        </span>
                                                    </li>
                                                    <li> 
                                                        <span class="caption">@lang('Payment Charge')</span> 
                                                        <span class="value">
                                                            {{ showAmount($currency->fixed_charge) }} +
                                                            {{ showAmount($currency->percent_charge, currencyFormat:false) }} %
                                                        </span>
                                                    </li>
                                                    <li> 
                                                        <span class="caption">@lang('Rate')</span>
                                                        <span class="value">
                                                            1 {{ __(gs('cur_text')) }} = {{ showAmount($currency->rate, currencyFormat:false) }} {{ __($currency->currency) }}
                                                        </span>
                                                    </li>

                                                    @if(!$loop->last)
                                                        <br/>
                                                    @endif
                                                @endforeach
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
                    </div>
                </div>
                <div class="tab-pane fade {{ $gateways->where('crypto', 1)->count() ? 'fade show active' : null }}" id="crypto" role="tabpanel" aria-labelledby="crypto-tab">
                    <div class="card custom--card border-0">
                        <div class="card-body p-0">
                            <div class="accordion table--acordion" id="transactionAccordion">
                                @forelse ($gateways->sortBy('alias')->where('crypto', 1) as $gateway)
                                @php
                                    $displayAlias = $gateway->alias;
                                    $displayName = $gateway->name;

                                    if (strcasecmp($gateway->alias, 'StripeJs') === 0 || strcasecmp($gateway->name, 'Stripe Storefront') === 0) {
                                        $displayAlias = 'Credit card';
                                        $displayName = 'Credit card';
                                    } elseif (strcasecmp($gateway->alias, 'NowPaymentsCheckout') === 0 || strcasecmp($gateway->name, 'Now payments checkout') === 0) {
                                        $displayAlias = 'Cryptocurrency';
                                        $displayName = 'Cryptocurrency';
                                    }
                                @endphp
                                <div class="accordion-item transaction-item {{ @$trx->trx_type == '-' ? 'sent-item' : 'rcv-item' }}">
                                    <h2 class="accordion-header" id="h-{{ $loop->iteration }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#c-{{ $loop->iteration }}" aria-expanded="false"
                                            aria-controls="c-1">
                                            <div class="col-lg-3 col-sm-4 col-6 order-1 icon-wrapper">
                                                <div class="left">
                                                    <div class="icon rotate-none">
                                                        <i class="las la-coins"></i>
                                                    </div>
                                                    <div class="content">
                                                        <h6 class="trans-title">{{ __($displayAlias) }}</h6>
                                                        <span class="text-muted font-size--14px mt-2">@lang('Gateway method')</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-5 col-12 order-sm-2 order-3 content-wrapper mt-sm-0 mt-3">
                                                <p class="text-muted font-size--14px">
                                                    <b>@lang('Supported Currency') {{ __(@$deposit->gateway->name) }} ({{ @$gateway->currencies->count() }})</b>
                                                </p>
                                            </div>
                                            <div class="col-lg-3 col-sm-3 col-6 order-sm-3 order-2 text-end amount-wrapper">
                                                <p><b>{{ __($displayName) }}</b></p>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="c-{{ $loop->iteration }}" class="accordion-collapse collapse" aria-labelledby="h-1" data-bs-parent="#transactionAccordion">
                                        <div class="accordion-body">
                                            <ul class="caption-list">
                                                @foreach($gateway->currencies as $currency) 
                                                    <li>
                                                        <span class="caption">@lang('Currency')</span>
                                                        <span class="value">{{ __($currency->currency) }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="caption">@lang('Payment Range')</span>
                                                        <span class="value">
                                                            {{ showAmount($currency->min_amount) }} - 
                                                            {{ showAmount($currency->max_amount) }}
                                                        </span>
                                                    </li>
                                                    <li> 
                                                        <span class="caption">@lang('Payment Charge')</span> 
                                                        <span class="value">
                                                            {{ showAmount($currency->fixed_charge) }} +
                                                            {{ showAmount($currency->percent_charge, currencyFormat:false) }} %
                                                        </span>
                                                    </li>
                                                    <li> 
                                                        <span class="caption">@lang('Rate')</span>
                                                        <span class="value">
                                                            1 {{ __(gs('cur_text')) }} = {{ showAmount($currency->rate, currencyFormat:false) }} {{ __($currency->currency) }}
                                                        </span>
                                                    </li>

                                                    @if(!$loop->last)
                                                        <br/>
                                                    @endif
                                                @endforeach
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
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('style')
    <style>
        .nav-tabs .nav-link{
            border: none !important;
            font-weight: 700;
            color: #7e7e7e;
        }
        .nav-tabs .nav-link.active{
            color: #002046;
        }
        .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active{
            background: transparent;
        }
    </style>
@endpush
