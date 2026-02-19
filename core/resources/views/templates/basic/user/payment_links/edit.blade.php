@extends($activeTemplate.'layouts.master')

@php
    $showHeaderBalance = true;
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card custom--card">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('Edit Payment Link')</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.payment.links.update', $paymentLink->id) }}" method="post">
                        @php $buttonText = __('Update Link'); @endphp
                        @include($activeTemplate.'user.payment_links.form')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
