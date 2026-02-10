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
                    <div class="card-header card-header-bg">
                        <h4 class="text-center">@lang('Payment Preview')</h4>
                    </div>
                    <div class="card-body card-body-deposit text-center">
                        <h4 class="my-2"> @lang('PLEASE SEND EXACTLY') <span class="text-success"> {{ $data->amount }}</span> {{__($data->currency)}}</h4>
                        <h5 class="mb-2">@lang('TO') <span class="text-success"> {{ $data->sendto }}</span></h5>
                        <img src="{{$data->img}}" alt="@lang('Image')">
                        <h4 class="text-white bold my-4">@lang('SCAN TO SEND')</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
