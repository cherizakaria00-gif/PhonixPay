@extends($activeTemplate.'layouts.master')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="text-center">
                <div class="restricted_icon">
                    <i class="las la-exclamation-triangle text--danger"></i>
                </div>
                <h6>@lang('Unlock the Power of Verified Merchant Status: Explore Your Benefits')</h6>
                <p>
                    @lang('Verified merchants can access exclusive premium content and services on our page, granting them an edge in their business endeavors.')
                </p> 
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .restricted_icon i{
            font-size: 95px;
        }
    </style>
@endpush

@push('script')
<script>
    (function($){
        "use strict";
        $('.merchant-dashboard__body').addClass('restricted');
    })(jQuery)
</script>
@endpush