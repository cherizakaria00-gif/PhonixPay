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
                        <h4 class="text-center">@lang('Authorize')</h4>
                    </div>
                    <form role="form" class="disableSubmission appPayment" id="payment-form" method="{{$data->method}}" action="{{$data->url}}">
                        @csrf
                        <div class="card-body">
                            <div class="row gy-4">
                                <div class="col-xxl-6">
                                    <div class="card-wrapper mb-md-5 mb-4"></div>
                                </div>
                                <div class="col-xxl-6">
                                    <div class="row gy-4">
                                        <div class="col-xxl-12 col-sm-6">
                                            <div class="form-group">
                                                <label class="floating-label required" for="subject">@lang('Name on Card')</label>
                                                <input type="text" class="form-control form--control" name="name" value="{{ old('name') }}" required autocomplete="off" autofocus/>
                                            </div>
                                        </div>
                                        <div class="col-xxl-12 col-sm-6">
                                            <div class="form-group">
                                                <label class="floating-label required" for="subject">@lang('Card Number')</label>
                                                <input type="tel" class="form-control form--control" name="cardNumber" autocomplete="off" value="{{ old('cardNumber') }}" required autofocus/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" value="{{$data->track}}" name="track">
                            <div class="row gy-3">

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="floating-label required" for="subject">@lang('Expiration Date')</label>
                                        <div class="input-group">
                                            <input type="tel" class="form-control form--control" name="cardExpiry" value="{{ old('cardExpiry') }}" autocomplete="off" required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="floating-label required" for="subject">@lang('CVC Code')</label>
                                        <div class="input-group">
                                            <input type="tel" class="form-control form--control" name="cardCVC" value="{{ old('cardCVC') }}" autocomplete="off" required/>
                                        </div>
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


@push('script')
    <script src="{{ asset('assets/global/js/card.js') }}"></script>

    <script>
        (function ($) {
            "use strict";
            var card = new Card({
                form: '#payment-form',
                container: '.card-wrapper',
                formSelectors: {
                    numberInput: 'input[name="cardNumber"]',
                    expiryInput: 'input[name="cardExpiry"]',
                    cvcInput: 'input[name="cardCVC"]',
                    nameInput: 'input[name="name"]'
                }
            });

            @if($deposit->from_api)
            $('.appPayment').on('submit',function(){
                $(this).find('[type=submit]').html('<i class="las la-spinner fa-spin"></i>');
            })
            @endif
        })(jQuery);
    </script>
@endpush


@push('style')
    <style>
        @media (max-width: 450px) {
            .jp-card {
                left: 80%;
                transform: translateX(-50%);
            }
        }
    </style>
@endpush
