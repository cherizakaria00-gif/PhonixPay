@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card custom--card">
                    <div class="card-body p-4 p-md-5 text-center">
                        <h3 class="mb-2">@lang('Setup Fee Transaction')</h3>
                        <p class="text-muted mb-4">@lang('Transaction tracking in progress')</p>

                        <div id="setupFeeStatusLoader" class="mb-4 @if($status === 'approved') d-none @endif">
                            <div class="spinner-border text--base" role="status" style="width: 3.5rem; height: 3.5rem;">
                                <span class="visually-hidden">@lang('Loading')</span>
                            </div>
                        </div>

                        <div id="setupFeeStatusSuccessIcon" class="mb-4 @if($status !== 'approved') d-none @endif">
                            <i class="las la-check-circle text--success" style="font-size: 68px;"></i>
                        </div>

                        <h4 id="setupFeeStatusTitle" class="mb-2">
                            @if($status === 'approved')
                                @lang('Payment Received')
                            @elseif($status === 'rejected')
                                @lang('Transaction Rejected')
                            @else
                                @lang('Processing Transaction')
                            @endif
                        </h4>

                        <p id="setupFeeStatusMessage" class="text-muted mb-3">
                            @if($status === 'approved')
                                @lang('Your transaction has been received successfully. Your account is now activated.')
                            @elseif($status === 'rejected')
                                @lang('Your transaction was not validated. Please retry from the setup fee page.')
                            @else
                                @lang('Do not close this page until your payment is received.')
                            @endif
                        </p>

                        <div class="alert alert--warning border border--warning mb-4 @if($status !== 'pending_review') d-none @endif" id="setupFeeStatusCountdownWrap">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span class="fw-bold">@lang('Time remaining')</span>
                                <span id="setupFeeStatusCountdown" class="fw-bold">00:00:00</span>
                            </div>
                        </div>

                        <a href="{{ route('user.home') }}" class="btn btn--dark px-4">
                            @lang('Back to Dashboard')
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            'use strict';

            const pollUrl = @json(route('user.gateway.setup.fee.status.data'));
            const titleEl = document.getElementById('setupFeeStatusTitle');
            const messageEl = document.getElementById('setupFeeStatusMessage');
            const loaderEl = document.getElementById('setupFeeStatusLoader');
            const successEl = document.getElementById('setupFeeStatusSuccessIcon');
            const countdownWrapEl = document.getElementById('setupFeeStatusCountdownWrap');
            const countdownEl = document.getElementById('setupFeeStatusCountdown');
            let remaining = {{ (int) $countdownSeconds }};

            const renderCountdown = () => {
                if (!countdownEl) {
                    return;
                }

                const safeRemaining = Math.max(0, remaining);
                const hours = String(Math.floor(safeRemaining / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((safeRemaining % 3600) / 60)).padStart(2, '0');
                const seconds = String(safeRemaining % 60).padStart(2, '0');
                countdownEl.textContent = `${hours}:${minutes}:${seconds}`;
            };

            const applyStatus = (status) => {
                if (status === 'approved') {
                    if (loaderEl) loaderEl.classList.add('d-none');
                    if (successEl) successEl.classList.remove('d-none');
                    if (countdownWrapEl) countdownWrapEl.classList.add('d-none');
                    if (titleEl) titleEl.textContent = 'Payment Received';
                    if (messageEl) messageEl.textContent = 'Your transaction has been received successfully. Your account is now activated.';
                    return true;
                }

                if (status === 'rejected') {
                    if (loaderEl) loaderEl.classList.add('d-none');
                    if (successEl) successEl.classList.add('d-none');
                    if (countdownWrapEl) countdownWrapEl.classList.add('d-none');
                    if (titleEl) titleEl.textContent = 'Transaction Rejected';
                    if (messageEl) messageEl.textContent = 'Your transaction was not validated. Please retry from the setup fee page.';
                    return true;
                }

                if (status === 'pending_review') {
                    if (loaderEl) loaderEl.classList.remove('d-none');
                    if (successEl) successEl.classList.add('d-none');
                    if (countdownWrapEl) countdownWrapEl.classList.remove('d-none');
                    if (titleEl) titleEl.textContent = 'Processing Transaction';
                    if (messageEl) messageEl.textContent = 'Do not close this page until your payment is received.';
                }

                return false;
            };

            renderCountdown();
            const localTimer = setInterval(() => {
                if (remaining > 0) {
                    remaining -= 1;
                    renderCountdown();
                } else {
                    clearInterval(localTimer);
                }
            }, 1000);

            const poll = async () => {
                try {
                    const response = await fetch(pollUrl, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    if (!response.ok) {
                        return false;
                    }

                    const data = await response.json();
                    if (typeof data.countdown_seconds === 'number') {
                        remaining = data.countdown_seconds;
                        renderCountdown();
                    }

                    return applyStatus(data.status);
                } catch (error) {
                    return false;
                }
            };

            const pollTimer = setInterval(async () => {
                const completed = await poll();
                if (completed) {
                    clearInterval(pollTimer);
                }
            }, 8000);

            applyStatus(@json($status));
        })();
    </script>
@endpush
