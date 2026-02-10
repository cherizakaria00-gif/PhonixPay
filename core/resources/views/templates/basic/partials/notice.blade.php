@php 
    $user = auth()->user();
    $kyc = getContent('kyc.content', true);
@endphp

<div class="mb-4">
    <div class="notice"></div>

    @if ($user->kv == Status::KYC_UNVERIFIED && auth()->user()->kyc_rejection_reason)
        <div class="alert border border--warning" role="alert">
            <div class="alert__icon d-flex align-items-center text--warning">
                <i class="fas fa-user-times"></i>
            </div>
            <p class="alert__message">
                <span class="fw-bold title">@lang('Your request has been rejected.')</span> 
                    <a href="javascript:void(0)" class="reasonBtn small">
                        <u>@lang('Rejection reason')</u>
                    </a>
                <br>
                <small class="content">
                    {{ __(@$kyc->data_values->reject) }}
                    <a href="{{ route('user.kyc.form') }}" class="link-color">@lang('Re-submit now')</a>, <a href="{{ route('user.kyc.data') }}" class="link-color">@lang('Submitted Data')</a>
                </small>
            </p>
        </div>
    @elseif($user->kv == Status::KYC_UNVERIFIED)
        <div class="alert border border--warning" role="alert">
            <div class="alert__icon d-flex align-items-center text--warning">
                <i class="fas fa-paper-plane"></i>
            </div>
            <p class="alert__message">
                <span class="fw-bold title">@lang('Merchant Request Form')</span>
                <br>
                <small class="content">
                    {{ __(@$kyc->data_values->required) }}
                    <a href="{{ route('user.kyc.form') }}" class="link-color">@lang('Submit Now')</a>
                </small>
            </p>
        </div>
    @elseif($user->kv == Status::KYC_PENDING)
        <div class="alert border border--warning" role="alert">
            <div class="alert__icon d-flex align-items-center text--warning">
                <i class="fas fa-spinner"></i>
            </div>
            <p class="alert__message">
                <span class="fw-bold title">@lang('Pending Merchant Request.')</span>
                <br>
                <small class="content">
                    {{ __(@$kyc->data_values->pending) }}
                    <a href="{{ route('user.kyc.data') }}" class="link-color">@lang('Submitted Data')</a>
                </small>
            </p>
        </div>
    @endif 
</div>

@if($user->kv == 0 && $user->kyc_rejection_reason)
    <div id="reasonModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">@lang('Document Rejection Reason')</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>{{ __($user->kyc_rejection_reason) }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark btn-sm" data-bs-dismiss="modal">@lang('Close')</button>
                </div>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        (function ($) {
            "use strict";
            $('.reasonBtn').on('click', function () {
                var modal = $('#reasonModal');
                modal.modal('show');
            });
        })(jQuery);
    </script>
    @endpush
@endif
