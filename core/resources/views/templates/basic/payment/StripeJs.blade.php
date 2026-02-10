@extends($activeTemplate.'layouts.app')

@section('app')  
<div class="py-60 checkout {{ @$deposit->apiPayment->checkout_theme }}">
    <div class="container"> 
        <div class="row justify-content-center">
            <div class="col-xxl-5 col-xl-6 col-lg-7 col-md-9"> 

                @php 
                    $html = '<form action="' . $data->url . '" method="' . $data->method . '">
                                <script src="' . $data->src . '" class="stripe-button"';
                                    foreach ($data->val as $key => $value) {
                                        $html .= ' data-' . $key . '="' . $value . '"';
                                    }
                    $html .= '></script>
                            </form>';
                @endphp
                @include($activeTemplate.'partials.payment_preview', ['html'=>$html])

            </div>
        </div>
    </div>
</div>
@endsection

@push('script-lib')
    <script src="https://js.stripe.com/v3/"></script>
@endpush
@push('script')
    <script>
        (function ($) {
            "use strict";
            $('.stripe-button-el').addClass("btn btn--base w-100 mt-3").removeClass('stripe-button-el')
            $('button[type="submit"]').text("Pay Now");
        })(jQuery);
    </script>
@endpush
