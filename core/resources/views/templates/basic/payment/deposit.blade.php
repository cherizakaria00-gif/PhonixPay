@extends($activeTemplate.'layouts.app')

@php
    $policyPages = getContent('policy_pages.element', orderById:true);
@endphp

@section('app')  
<div class="py-60 checkout {{ @$apiPayment->checkout_theme }}">
    <div class="container"> 
        <div class="row justify-content-center">
            <div class="col-xxl-5 col-xl-6 col-lg-7 col-md-9"> 
                @if(@$apiPayment['status'] == 'error')
                    <h3 class="text-danger text-center">{{ __(@$apiPayment['message']) }}</h3>
                @else
                    <div class="checkout-logo-wrapp">
                        <div class="checkout-logo">
                            @if($apiPayment->site_logo)
                                <img class="img-fluid" src="{{ $apiPayment->site_logo }}">
                            @else 
                                <img class="img-fluid" src="{{ siteLogo() }}">
                            @endif
                        </div>
                    </div>

                    <form action="{{route('deposit.insert')}}" method="post">
                        @csrf
                        <input type="hidden" name="payment_trx" required value="{{ @$trx }}">
                        <div class="card custom--card">

                            <div class="card-header border-0">
                                <div class='d-flex flex-wrap justify-content-between align-items-center'>
                                    <h4 class="card-title mb-0">@lang('Payment')</h4>
                                    <div class='payment-cancel'>
                                        <a href="{{ route('payment.cancel', $trx) }}" class='btn btn-danger btn--sm'>@lang('Cancel')</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <h3 class="text-center mb-4 mt-3">{{ $apiPayment->site_name }}</h3>
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
                                <input type="hidden" name="method_code" required>
                                <div class="form-group">
                                    <div class="custom--dropdown">
                                        <div class="custom--dropdown__selected dropdown-list__item">
                                            <span class="text">@lang('Select One')</span>
                                        </div>
                                        <ul class="dropdown-list">   
                                            @foreach($gatewayCurrency as $data)
                                            <li class="dropdown-list__item gateway" data-value="{{ $data->method_code }}">
                                                    <img src="{{ getImage(getFilePath('gateway').'/'. @$data->method->image, getFileSize('gateway')) }}" alt=""> 
                                                    <span class="text">{{ __($data->name) }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                @if(gs('agree'))
                                    <div class="form-group terms-condition">
                                        <p class="text">@lang('Read our')
                                            @foreach($policyPages as $policy) 
                                                <a href="{{ route('policy.pages',[slug($policy->data_values->title)]) }}" class="anchor-color small" target="_blank">
                                                    {{ __($policy->data_values->title) }}
                                                </a>@if(!$loop->last), @endif 
                                            @endforeach
                                        </p>
                                    </div>
                                @endif
                                <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function($) {
        "use strict";
        $(".gateway").on("click", function () {
            $('input[name=method_code]').val($(this).data('value'));
        });
    })(jQuery)
</script>
@endpush