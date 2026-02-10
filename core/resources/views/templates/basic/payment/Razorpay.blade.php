@extends($activeTemplate.'layouts.app')

@section('app')  
<div class="py-60 checkout {{ @$deposit->apiPayment->checkout_theme }}">
    <div class="container"> 
        <div class="row justify-content-center">
            <div class="col-xxl-5 col-xl-6 col-lg-7 col-md-9"> 
               
                @php 
                    $html = '<form action="' . $data->url . '" method="' . $data->method . '">
                                <input type="hidden" custom="' . $data->custom . '" name="hidden">
                                <script src="' . $data->checkout_js . '"';
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

@push('script')
    <script>
        (function ($) {
            "use strict";
            $('input[type="submit"]').addClass("mt-4 btn btn--base w-100");
        })(jQuery);
    </script>
@endpush
