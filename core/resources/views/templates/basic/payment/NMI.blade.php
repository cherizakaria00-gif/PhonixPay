@extends($activeTemplate.'layouts.app')

@section('app')
<div class="py-60 checkout {{ @$deposit->apiPayment->checkout_theme }}">
    <div class="container">
        <div class="row gy-4">
            <div class="col-xxl-5 col-lg-6">
                @include($activeTemplate.'partials.payment_preview')
            </div>

            <div class="col-xxl-7 col-lg-6">
                <div class="card custom--card h-100">
                    <div class="card-header">
                        <h4 class="text-center">@lang('NMI')</h4>
                    </div>
                    <form role="form" class="disableSubmission appPayment" id="payment-form" method="{{$data->method}}" action="{{$data->url}}">
                        @csrf
                        <div class="card-body">
                            <div class="row gy-4">
                                <div class="col-xxl-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="floating-label required" for="subject">@lang('Card Number')</label>
                                        <input type="tel" class="form-control form--control" name="billing-cc-number" autocomplete="off" value="{{ old('billing-cc-number') }}" required autofocus/>
                                    </div>
                                </div>
                                <div class="col-xxl-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="floating-label required" for="subject">@lang('Expiration Date')</label>
                                        <input type="tel" class="form-control form--control" name="billing-cc-exp" value="{{ old('billing-cc-exp') }}" placeholder="e.g. MM/YY" autocomplete="off" required/>
                                    </div>
                                </div>
                                <div class="col-xxl-12 col-sm-12">
                                    <div class="form-group">
                                        <label class="floating-label required" for="subject">@lang('CVC Code')</label>
                                        <input type="tel" class="form-control form--control" name="billing-cc-cvv" value="{{ old('billing-cc-cvv') }}" autocomplete="off" required/>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button class="btn btn--base w-100" type="submit"> @lang('Pay Now')</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if($deposit->from_api)
    @push('script')
    <script>
        (function($){
            "use strict";

            $('.appPayment').on('submit',function(){
                $(this).find('[type=submit]').html('<i class="las la-spinner fa-spin"></i>');
            })


        })(jQuery);

    </script>
    @endpush
@endif