@extends($activeTemplate.'layouts.app')

@section('app')  
<div class="py-60 checkout {{ @$deposit->apiPayment->checkout_theme }}">
    <div class="container"> 
        <div class="row justify-content-center">
            <div class="col-xxl-5 col-xl-6 col-lg-7 col-md-9"> 

                @php 
                    $html = '<form action="' . route('ipn.'.$deposit->gateway->alias) . '" method="POST" class="text-center">
                                '.csrf_field().'
                                <button type="button" class="btn btn--base w-100 mt-3" id="btn-confirm">'.__("Pay Now").'</button>
                                <script
                                    src="//js.paystack.co/v1/inline.js"
                                    data-key="' . $data->key . '"
                                    data-email="' . $data->email . '"
                                    data-amount="' . round($data->amount) . '"
                                    data-currency="' . $data->currency . '"
                                    data-ref="' . $data->ref . '"
                                    data-custom-button="btn-confirm"
                                >
                                </script>
                            </form>';
                @endphp
                @include($activeTemplate.'partials.payment_preview', ['html'=>$html])
                
            </div>
        </div>
    </div>
</div>
@endsection

