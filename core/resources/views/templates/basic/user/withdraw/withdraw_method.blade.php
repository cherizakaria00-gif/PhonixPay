@extends($activeTemplate . 'layouts.master')

@section('content')
<form action="{{route('user.withdraw.method.submit')}}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="row justify-content-center gy-4">
        <div class="col-12">
            <div class="page-heading mb-4">
                <h3 class="mb-2">{{ __($pageTitle) }}</h3>
                <p>
                    @lang('Take control of your withdrawals with our comprehensive withdraw method page, allowing you to customize and select your preferred method based on limits, charges, and conversion rates. Optimize your earnings and choose the best withdrawal method for you.')
                </p>
            </div>
            <hr>
        </div>
        <div class="col-12">
            <div class="card custom--card style--two">
                <div class="card-header card-title mb-0 bg-white">
                    <h5>@lang('Setup Withdraw Method')</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>@lang('Method')</label>
                                <select class="form-select form--control" name="method_code" required>
                                    <option value="">@lang('Select One')</option>
                                    @foreach($withdrawMethod as $data) 
                                        <option value="{{ $data->id }}" 
                                            data-resource="{{$data}}"
                                            data-form='<x-withdraw-form identifier="id" identifierValue="{{ $data->form_id }}"/>'
                                            {{ $data->id == @$user->withdrawSetting->withdraw_method_id ? 'selected' : null }}
                                        >
                                            {{__($data->name)}} 
                                            ({{ showAmount($data->min_limit, currencyFormat:false) }} - {{ showAmount($data->max_limit, currencyFormat:false) }})
                                        </option>
                                    @endforeach
                                </select>
                                <p>
                                    <small class="fst-italic">
                                        <i class="las la-info-circle"></i> 
                                        @lang('Withdraw Time'): <span class="schedule_type capitalize"></span> <span class="schedule"></span>
                                    </small>
                                </p>
                                <p class="d-none rate-element"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Amount')</label>
                                <div class="input--group">
                                    <input type="number" step="any" name="amount" value="{{ getAmount(@$user->withdrawSetting->amount) }}" class="form-control form--control" required>
                                    <span class="input-group--text">{{ gs("cur_text") }}</span>
                                </div>
                                <p>
                                    <small class="fst-italic">
                                        <i class="las la-info-circle"></i> 
                                        @lang('Withdraw Charge'): {{ gs('cur_sym') }}<span class="charge">0</span> | @lang('Receivable Amount'): {{ gs('cur_sym') }}<span class="receivable">0</span>
                                    </small>
                                </p>
                                <p class="in-site-cur d-none">
                                    <small class="fst-italic"><i class="las la-info-circle"></i> @lang('in') <span class="base-currency"></span> <span class="final_amo">0</span> <span class="base-currency"></span></small>
                                </p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="withdraw_form"></div>
                            <button type="submit" class="btn btn--base w-100 mt-3">@lang('Submit')</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('script')
<script>
(function ($) {
    "use strict";

    $('select[name=method_code]').change(function(){
        
        if(!$('select[name=method_code]').val()){
            $('.charge').text(0);
            $('.receivable').text(0);
            $('.schedule_type').text('');
            $('.schedule').text('');
            $('.in-site-cur').addClass('d-none');
            $('.rate-element').addClass('d-none');
            return false;
        }
        
        var resource = $('select[name=method_code] option:selected').data('resource');
        var form = $('select[name=method_code] option:selected').data('form');

        var fixed_charge = parseFloat(resource.fixed_charge);
        var percent_charge = parseFloat(resource.percent_charge);
        var rate = parseFloat(resource.rate)
        var toFixedDigit = 2;

        $('.min').text(parseFloat(resource.min_limit).toFixed(2));
        $('.max').text(parseFloat(resource.max_limit).toFixed(2));
        var amount = parseFloat($('input[name=amount]').val());

        if (!amount) {
            amount = 0;
        }

        $('.preview-details').removeClass('d-none');

        var charge = parseFloat(fixed_charge + (amount * percent_charge / 100)).toFixed(2);
        $('.charge').text(charge);

        if (resource.currency != '{{ gs("cur_text") }}') {
            var rateElement = `<small class="fst-italic"><i class="las la-info-circle"></i><span>@lang('Conversion Rate'):</span> <span>1 {{__(gs('cur_text'))}} = <span class="rate">${rate}</span>  <span class="base-currency">${resource.currency}</span></span></small>`;
            $('.rate-element').html(rateElement);
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

        var receivable = parseFloat((parseFloat(amount) - parseFloat(charge))).toFixed(2);
        $('.receivable').text(receivable);
        var final_amo = parseFloat(parseFloat(receivable)*rate).toFixed(toFixedDigit);

        $('.final_amo').text(final_amo);
        $('.base-currency').text(resource.currency);
        $('.method_currency').text(resource.currency);
        
        $('.schedule_type').text(resource.schedule_type);

        if(resource.schedule_type == 'daily'){
            $('.schedule').text('');
        }else{
            $('.schedule').text(' - '+resource.showSchedule);
        }

        $('.withdraw_form').html(form);
        defaultBehavior();

        $('input[name=amount]').on('input');
    }).change();

    $('input[name=amount]').on('input',function(){
        var data = $('select[name=method_code]').change();
        $('.amount').text(parseFloat($(this).val()).toFixed(2));
    });
})(jQuery);
</script>
@endpush