@extends($activeTemplate.'layouts.master')

@section('content')
<div class="container">
    <div class="row justify-content-center gy-4">
        <div class="col-12">
            <div class="page-heading mb-4">
                <h3 class="mb-2">{{ __($pageTitle) }}</h3>
                <p>
                    @lang('Calculate payment gateway charges for your transactions quickly and easily with our user-friendly page. Enter the transaction amount and get an instant breakdown of the charges, helping you make informed decisions and streamline your payment process. Try it now!')
                </p>
            </div>
            <hr>
        </div>  
        <div class="col-lg-12"> 
            <div class="card style--two">
                <div class="card-header">
                    <h5 class="card-title text-center">{{ __($pageTitle) }}</h5>
                </div>
                <div class="card-body">
                    <div class="card-body__inner">
                        <div class="card-body__left">
                            <form action="" class="form exclude">
                                <div class="form-group">
                                    <label class="form-label">@lang('Select Gateway')</label>
                                    <select class="form--control form-select" name="gateway" required>
                                        <option value="">@lang('Select One')</option>
                                        @foreach($gatewayCurrency as $data)
                                            <option value="{{$data->method_code}}" @selected(old('gateway') == $data->method_code) data-gateway="{{ $data }}">{{$data->name}}</option>
                                        @endforeach
                                    </select>
                                </div> 
                                <div class="form-group">
                                    <label class="form-label">@lang('Amount')</label>
                                    <div class="input--group">
                                        <input type="number" step="any" name="amount" class="form-control form--control" value="{{ old('amount') }}" autocomplete="off" required>
                                        <span class="input-group--text currencyCode">{{ __(gs('cur_text')) }}</span>
                                    </div>
                                </div>
                                <div class="form-group mb-0 mt-4 pt-1">
                                    <button type="submit" class="btn btn--base w-100 submit">
                                        @lang('Calculate')
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="card-body__right">
                            <div class="preview-details">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>
                                            <span class="list-group-item__icon"><i class="las la-hand-paper"></i></span>
                                            <span class="fw-bold fs-14">@lang('Limit')</span>
                                        </span>
                                        <span class="fs-15"><span class="min">0</span> {{__(gs('cur_text'))}} - <span class="max">0</span> {{__(gs('cur_text'))}}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>
                                            <span class="list-group-item__icon"><i class="las la-money-bill-wave"></i></span>
                                            <span class="fw-bold fs-14">@lang('Gateway Charge')</span>
                                        </span>
                                        <span class="fs-15"><span class="charge">0</span> {{__(gs('cur_text'))}}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>
                                            <span class="list-group-item__icon"><i class="las la-money-bill"></i></span>
                                            <span class="fw-bold fs-14">@lang('Payment Charge')</span>
                                        </span>
                                        <span class="fs-15"><span class="payment_charge">0</span> {{__(gs('cur_text'))}}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between border-bottom-0">
                                        <span>
                                            <span class="list-group-item__icon"><i class="las la-hand-holding-usd"></i></span>
                                            <span class="fw-bold fs-14">@lang('Receivable Amount')</span>
                                        </span>
                                        <span class="fs-15"><span class="payable"> 0</span> {{__(gs('cur_text'))}}</span>
                                    </li>
                                    <li class="list-group-item justify-content-between d-none rate-element border-top">
                                    </li>
                                    <li class="list-group-item justify-content-between d-none in-site-cur">
                                        <span>
                                            <span class="list-group-item__icon"><i class="las la-hand-holding-usd"></i></span>
                                            <span class="fw-bold fs-14">@lang('In') <span class="method_currency"></span></span>
                                        </span>
                                        <span>
                                            <span class="final_amo fs-15">0</span> <span class="method_currency"></span>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>                  
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
@endsection

@push('script') 
    <script>
        (function ($) {
            "use strict";

            $('.form').on('submit', function(e){
                e.preventDefault();
                
                $('.submit').html(`<span class="spinner-border spinner-border-sm" role="status"></span>`);
                $('.amount').text(parseFloat($(this).val()).toFixed(2));
                
                setTimeout(function(){
                    $('.submit').text(`@lang('Calculate')`);
                    calculateCharge();
                }, 500); 
            });

            $('select[name=gateway]').on('change', function(){
                calculateCharge(true);
            });

            function calculateCharge(showCurrency = false){
                try{
                    var resource = $('select[name=gateway] option:selected').data('gateway');
                    var fixed_charge = parseFloat(resource.fixed_charge);
                    var percent_charge = parseFloat(resource.percent_charge);
                    var rate = parseFloat(resource.rate);

                    if(showCurrency){
                        var currency = resource.currency; 
                        return $('.currencyCode').text(currency);
                    }

                    if(resource.method.crypto == 1){
                        var toFixedDigit = 8;
                        $('.crypto_currency').removeClass('d-none');
                    }else{
                        var toFixedDigit = 2;
                        $('.crypto_currency').addClass('d-none');
                    }
                    
                    $('.min').text(parseFloat(resource.min_amount).toFixed(2));
                    $('.max').text(parseFloat(resource.max_amount).toFixed(2));

                    var amount = parseFloat($('input[name=amount]').val());
                    if (!amount) {
                        amount = 0;
                    } 

                    var charge = parseFloat((fixed_charge * rate) + (amount * percent_charge / 100)).toFixed(2);
                    $('.charge').text((charge/rate).toFixed(toFixedDigit));

                    var paymentCharge = parseFloat(({{ $user->payment_fixed_charge }} * rate) + (amount * {{ $user->payment_percent_charge }} / 100));
                    $('.payment_charge').text((paymentCharge/rate).toFixed(toFixedDigit)); 
                    
                    var payable = parseFloat((parseFloat(amount) - (parseFloat(charge) + parseFloat(paymentCharge)))).toFixed(toFixedDigit);
                    $('.payable').text((payable/rate).toFixed(toFixedDigit));
                
                    var final_amo = (parseFloat((parseFloat(payable)))*rate).toFixed(toFixedDigit);
                    $('.final_amo').text(payable);

                    if (resource.currency != '{{ gs("cur_text") }}') {
                        var rateElement = ` 
                            <span>
                                <span class="list-group-item__icon"><i class="las la-percentage"></i></span>
                                <span class="fw-bold fs-14">@lang('Conversion Rate')</span> 
                            </span>
                            <span><span class="fs-15">1 {{__(gs('cur_text'))}} = <span class="rate">${rate}</span>
                            <span class="method_currency">${resource.currency}</span></span></span>
                        `;
                        $('.rate-element').html(rateElement)
                        $('.rate-element').removeClass('d-none');
                        $('.in-site-cur').removeClass('d-none');
                        $('.rate-element').addClass('d-flex');
                        $('.in-site-cur').addClass('d-flex');
                    }else{
                        $('.rate-element').html('')
                        $('.rate-element').addClass('d-none');
                        $('.in-site-cur').addClass('d-none');
                        $('.rate-element').removeClass('d-flex');
                        $('.in-site-cur').removeClass('d-flex');
                    }

                    $('.method_currency').text(resource.currency);
                    $('input[name=currency]').val(resource.currency);
                    $('input[name=amount]').on('input');
                }catch(message){
                    $('.currencyCode').text('{{ gs("cur_text") }}');
                }
            }

        })(jQuery);
    </script>
@endpush