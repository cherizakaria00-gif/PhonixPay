@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card custom--card">
                    <div class="card-body p-4 p-md-5 text-center">
                        <h3 class="mb-2">@lang('Setup Fee Review')</h3>

                        @if($status === 'approved')
                            <div class="mb-3">
                                <i class="las la-check-circle text--success" style="font-size: 68px;"></i>
                            </div>
                            <h4 class="mb-2">@lang('Payment Approved')</h4>
                            <p class="text-muted mb-4">
                                @lang('Your setup fee has been approved. Your merchant account is now active.')
                            </p>
                        @elseif($status === 'rejected')
                            <div class="mb-3">
                                <i class="las la-times-circle text--danger" style="font-size: 68px;"></i>
                            </div>
                            <h4 class="mb-2">@lang('Payment Rejected')</h4>
                            <p class="text-muted mb-4">
                                @lang('Your transfer was not validated. Please return to the setup fee page and submit again.')
                            </p>
                        @else
                            <div class="mb-3">
                                <i class="las la-hourglass-half text--warning" style="font-size: 68px;"></i>
                            </div>
                            <h4 class="mb-2">@lang('Under Manual Review')</h4>
                            <p class="text-muted mb-3">
                                @lang('Your setup fee transfer has been submitted and is waiting for manual validation.')
                            </p>
                            <div class="alert alert--warning border border--warning mb-4 text-start">
                                @lang('If no TxID is available yet, your submitted reference is used as the default transaction identifier for review.')
                            </div>
                        @endif

                        <a href="{{ route('user.home') }}" class="btn btn--dark px-4">
                            @lang('Back to Dashboard')
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
