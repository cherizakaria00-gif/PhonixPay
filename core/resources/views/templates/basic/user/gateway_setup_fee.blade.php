@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card custom--card">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                            <div>
                                <h3 class="mb-1">@lang('Gateway Setup Fee')</h3>
                                <p class="text-muted mb-0">
                                    @lang('Pay the setup fee using USDT to activate your merchant gateway access.')
                                </p>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">@lang('Amount')</div>
                                <div class="h4 mb-0">{{ number_format((float) $paymentLink->amount, 2) }} {{ strtoupper((string) $paymentLink->currency) }}</div>
                            </div>
                        </div>

                        <div class="alert alert--warning border border--warning mb-4" role="alert">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span class="fw-bold">@lang('Time remaining')</span>
                                <span id="setupFeeCountdown" class="fw-bold">00:00</span>
                            </div>
                        </div>

                        @if(($user->setup_fee_status ?? 'unpaid') === 'pending_review')
                            <div class="alert alert--info mb-4" role="alert">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <span>@lang('Your setup fee transfer is under manual review.')</span>
                                    @if(!is_null($reviewCountdownSeconds))
                                        <span class="fw-bold" id="setupFeeReviewCountdown">00:00:00</span>
                                    @endif
                                </div>
                                <small class="d-block mt-2 text-muted">
                                    @lang('If your wallet does not show a TxID immediately, use this payment reference as your default transaction identifier.')
                                </small>
                            </div>
                        @elseif(($user->setup_fee_status ?? 'unpaid') === 'rejected')
                            <div class="alert alert--danger mb-4" role="alert">
                                {{ $user->setup_fee_rejection_reason ?: __('Your setup fee payment was rejected. Please submit again after making the transfer.') }}
                            </div>
                        @elseif(($user->setup_fee_status ?? 'unpaid') === 'approved')
                            <div class="alert alert--success mb-4" role="alert">
                                @lang('Your gateway setup fee has been approved.')
                            </div>
                        @endif

                        @if(!$walletAddress)
                            <div class="alert alert--danger" role="alert">
                                @lang('USDT TRC20 wallet address is not configured yet. Please contact support.')
                            </div>
                        @else
                            <div class="row g-4 align-items-start">
                                <div class="col-lg-5 col-md-6 text-center">
                                    <div class="setup-fee-qr-wrap border rounded-3 p-3 bg-white d-inline-block">
                                        <img src="{{ $qrCodeUrl }}" alt="@lang('USDT wallet QR code')" class="setup-fee-qr-img d-block mx-auto">
                                    </div>
                                    <small class="text-muted d-block mt-2">@lang('Scan this QR code with your TRC20-compatible wallet')</small>
                                </div>

                                <div class="col-lg-7 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">@lang('Wallet Address')</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{{ $walletAddress }}" id="setupFeeWalletAddress" readonly>
                                            <button class="btn btn--base" type="button" id="copySetupFeeWallet">@lang('Copy')</button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">@lang('Network')</label>
                                        <input type="text" class="form-control" value="{{ $walletNetwork ?: 'TRC20' }}" readonly>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">@lang('Reference')</label>
                                        <input type="text" class="form-control" value="{{ $paymentLink->code }}" readonly>
                                    </div>

                                    <form method="POST" action="{{ route('user.gateway.setup.fee.confirm') }}">
                                        @csrf
                                        <input type="hidden" name="code" value="{{ $paymentLink->code }}">
                                        <button type="submit" class="btn btn--base w-100" id="setupFeeConfirmBtn" @disabled(($user->setup_fee_status ?? 'unpaid') === 'pending_review' || ($user->setup_fee_status ?? 'unpaid') === 'approved')>
                                            @lang('Submit Payment For Review')
                                        </button>
                                    </form>

                                    <a href="{{ route('user.home') }}" class="btn btn--dark w-100 mt-2">
                                        @lang('Back to Dashboard')
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .setup-fee-qr-wrap {
            max-width: 320px;
            margin: 0 auto;
        }

        .setup-fee-qr-img {
            width: 100%;
            height: auto;
            max-width: 280px;
        }
    </style>
@endpush

@push('script')
    <script>
        (function () {
            'use strict';

            const countdownEl = document.getElementById('setupFeeCountdown');
            const reviewCountdownEl = document.getElementById('setupFeeReviewCountdown');
            const confirmBtn = document.getElementById('setupFeeConfirmBtn');
            const copyBtn = document.getElementById('copySetupFeeWallet');
            const walletInput = document.getElementById('setupFeeWalletAddress');
            let remaining = {{ (int) $countdownSeconds }};
            let reviewRemaining = {{ (int) ($reviewCountdownSeconds ?? 0) }};

            const renderCountdown = () => {
                if (!countdownEl) {
                    return;
                }

                const safeRemaining = Math.max(0, remaining);
                const minutes = String(Math.floor(safeRemaining / 60)).padStart(2, '0');
                const seconds = String(safeRemaining % 60).padStart(2, '0');
                countdownEl.textContent = `${minutes}:${seconds}`;

                if (safeRemaining <= 0 && confirmBtn) {
                    confirmBtn.disabled = true;
                    confirmBtn.classList.add('disabled');
                }
            };

            const renderReviewCountdown = () => {
                if (!reviewCountdownEl) {
                    return;
                }

                const safeRemaining = Math.max(0, reviewRemaining);
                const hours = String(Math.floor(safeRemaining / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((safeRemaining % 3600) / 60)).padStart(2, '0');
                const seconds = String(safeRemaining % 60).padStart(2, '0');
                reviewCountdownEl.textContent = `${hours}:${minutes}:${seconds}`;
            };

            renderCountdown();
            renderReviewCountdown();

            if (remaining > 0) {
                const timer = setInterval(() => {
                    remaining -= 1;
                    renderCountdown();

                    if (remaining <= 0) {
                        clearInterval(timer);
                    }
                }, 1000);
            }

            if (reviewRemaining > 0) {
                const reviewTimer = setInterval(() => {
                    reviewRemaining -= 1;
                    renderReviewCountdown();

                    if (reviewRemaining <= 0) {
                        clearInterval(reviewTimer);
                    }
                }, 1000);
            }

            if (copyBtn && walletInput) {
                copyBtn.addEventListener('click', async function () {
                    try {
                        await navigator.clipboard.writeText(walletInput.value);
                        copyBtn.textContent = 'Copied';
                        setTimeout(() => {
                            copyBtn.textContent = 'Copy';
                        }, 1500);
                    } catch (error) {
                        walletInput.select();
                        document.execCommand('copy');
                    }
                });
            }
        })();
    </script>
@endpush
