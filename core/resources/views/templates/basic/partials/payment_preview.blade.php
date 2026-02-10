@php
    $policyPages = getContent('policy_pages.element', orderById:true);
    $apiPayment = @$deposit->apiPayment;
@endphp

<div class="checkout-logo-wrapp">
    <div class="checkout-logo">
        @if(@$apiPayment->site_logo)
            <img class="img-fluid" src="{{ @$apiPayment->site_logo }}">
        @else 
            <img class="img-fluid" src="{{ siteLogo() }}">
        @endif
    </div>
</div>
<div class="card custom--card">
    <div class="card-header border-0">
        <div class='d-flex flex-wrap justify-content-between align-items-center'>
            <h4 class="card-title mb-0">@lang('Preview')</h4>
            <div class='payment-cancel'>
                <a href="{{ route('payment.cancel', encrypt(@$apiPayment->trx)) }}" class='btn btn-danger btn--sm'>@lang('Cancel')</a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <h3 class="text-center mb-4 mt-3">{{ @$apiPayment->site_name }}</h3>
        <div class="page-links">
            <div class="page-links__item">
                <a href="#" class="page-links__icon"><i class="las la-shield-alt"></i></a>
                <a href="#" class="page-links__text">@lang('Trusted')</a>
            </div>
            <div class="page-links__item">
                <a href="#" class="page-links__icon"><i class="las la-lock"></i></a>
                <a href="#" class="page-links__text">@lang('Security')</a>
            </div>
            <div class="page-links__item">
                <a href="#" class="page-links__icon"><i class="las la-headset"></i></a>
                <a href="#" class="page-links__text">@lang('Support')</a>
            </div>
            <div class="page-links__item">
                <a href="#" class="page-links__icon"><i class="las la-credit-card"></i></a>
                <a href="#" class="page-links__text">@lang('Payment')</a>
            </div>
        </div>

        <ul class="list-group list-group-flush text-center">
            <li class="list-group-item d-flex justify-content-between flex-wrap">
                @lang('Amount')
                <strong>{{showAmount($deposit->gateway_amount, currencyFormat:false)}} {{__($deposit->method_currency)}}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between flex-wrap">
                @lang('Gateway')
                <strong>{{__($deposit->gateway->name)}}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between flex-wrap">
                @lang('Trx')
                <strong>{{ $deposit->trx }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between flex-wrap">
                @lang('Date')
                <strong>{{ showDateTime($deposit->created_at) }}</strong>
            </li>
        </ul>
        @if(gs('agree'))
            <div class="form-group terms-condition mb-0">
                <p class="text">@lang('Read our')
                    @foreach($policyPages as $policy) 
                        <a href="{{ route('policy.pages',[slug($policy->data_values->title)]) }}" class="anchor-color small" target="_blank">
                            {{ __($policy->data_values->title) }}
                        </a>@if(!$loop->last), @endif 
                    @endforeach
                </p>
            </div>
        @endif

        @php echo @$html; @endphp
            
    </div>
</div>