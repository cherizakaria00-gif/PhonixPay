@extends($activeTemplate.'layouts.app')

@section('app')
<div class="py-60">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="quick-redirect-card text-center">
                    <div class="quick-redirect-loader mb-3"></div>
                    <h5 class="mb-2">@lang('Redirecting to secure payment...')</h5>
                    <p class="text-muted mb-4">@lang('Please wait, do not close this page.')</p>
                    <a href="{{ $redirectUrl }}" class="btn btn--base btn-sm">@lang('Continue')</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    setTimeout(function () {
        window.location.href = @json($redirectUrl);
    }, 250);
</script>
@endsection

@push('style')
<style>
    .quick-redirect-card {
        background: #fff;
        border: 1px solid rgba(27, 31, 59, 0.08);
        box-shadow: 0 16px 40px rgba(20, 28, 68, 0.10);
        border-radius: 16px;
        padding: 28px 24px;
    }

    .quick-redirect-loader {
        width: 38px;
        height: 38px;
        border: 3px solid rgba(72, 96, 255, 0.15);
        border-top-color: #5868ff;
        border-radius: 50%;
        margin: 0 auto;
        animation: quick-spin .9s linear infinite;
    }

    @keyframes quick-spin {
        to { transform: rotate(360deg); }
    }
</style>
@endpush
