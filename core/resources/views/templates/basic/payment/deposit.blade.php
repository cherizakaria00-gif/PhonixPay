@extends($activeTemplate.'layouts.app')

@php
    $policyPages = getContent('policy_pages.element', orderById:true);
    $isTestMode = $isTestMode ?? false;
@endphp

@section('app')  
<div class="py-60 checkout {{ @$apiPayment->checkout_theme }}">
    <div class="container"> 
        <div class="row justify-content-center">
            <div class="col-xxl-5 col-xl-6 col-lg-7 col-md-9"> 
                @if(@$apiPayment['status'] == 'error')
                    <h3 class="text-danger text-center">{{ __(@$apiPayment['message']) }}</h3>
                @else
                    <form action="{{ $isTestMode ? route('test.payment.success') : route('deposit.insert') }}" method="post" id="checkout-form">
                        @csrf
                        <input type="hidden" name="payment_trx" required value="{{ @$trx }}">
                        @if(isset($paymentLink))
                            <input type="hidden" name="payment_link_code" value="{{ $paymentLink->code }}">
                        @endif
                        <div class="card custom--card">

                            <div class="card-header border-0">
                                <div class='d-flex flex-wrap justify-content-between align-items-center'>
                                    <h4 class="card-title mb-0">
                                        @lang('Payment')
                                        @if($isTestMode)
                                            <span class="badge badge--warning ms-2">@lang('Test Mode')</span>
                                        @endif
                                    </h4>
                                    <div class='payment-cancel'>
                                        <a href="{{ $isTestMode ? route('test.payment.cancel', $trx) : route('payment.cancel', $trx) }}" class='btn btn-danger btn--sm'>@lang('Cancel')</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                @if(isset($paymentLink))
                                    <div class="payment-link-summary mb-4">
                                        <h5 class="mb-1">{{ __($paymentLink->description ?: 'Payment Link') }}</h5>
                                        <p class="mb-0">@lang('Amount'):
                                            <strong>{{ showAmount($paymentLink->amount, currencyFormat:false) }} {{ __($paymentLink->currency) }}</strong>
                                        </p>
                                        @if($paymentLink->expires_at)
                                            <p class="mb-0 text-muted">@lang('Expires'):
                                                {{ showDateTime($paymentLink->expires_at) }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                                @if(!empty($showCustomerForm))
                                    <div class="row gy-3 mb-4">
                                        <div class="col-12">
                                            <label class="form-label">@lang('Full Name')</label>
                                            <input type="text" name="customer_full_name" class="form--control" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">@lang('Email')</label>
                                            <input type="email" name="customer_email" class="form--control" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">@lang('Mobile')</label>
                                            <input type="text" name="customer_mobile" class="form--control" required>
                                        </div>
                                    </div>
                                @endif
                                <input type="hidden" name="method_code" required>
                                <div class="form-group">
                                    <div class="payment-options-grid">
                                        @foreach($gatewayCurrency as $data)
                                            @php
                                                $isCryptoGateway = (int) ($data->method->crypto ?? 0);
                                                $gatewayAlias = strtolower((string) ($data->gateway_alias ?? $data->method->alias ?? ''));
                                                $gatewayCurrencyCode = strtoupper((string) ($data->currency ?? ''));
                                            @endphp
                                            <button
                                                type="button"
                                                class="payment-option gateway"
                                                data-value="{{ $data->method_code }}"
                                                data-name="{{ __($data->name) }}"
                                                data-crypto="{{ $isCryptoGateway }}"
                                                data-currency="{{ $gatewayCurrencyCode }}"
                                                data-alias="{{ $gatewayAlias }}"
                                            >
                                                <span class="payment-option__content">
                                                    <span class="payment-option__icon">
                                                        <img src="{{ getImage(getFilePath('gateway').'/'. @$data->method->image, getFileSize('gateway')) }}" alt="">
                                                    </span>
                                                    <span class="text">{{ __($data->name) }}</span>
                                                </span>
                                                <span class="payment-option__status" aria-hidden="true"></span>
                                            </button>
                                        @endforeach
                                    </div>
                                    <p class="gateway-auto-hint d-none" id="gateway-auto-hint"></p>
                                </div>
                                @if(gs('agree'))
                                    <div class="form-group terms-condition">
                                        <p class="text">@lang('Choose your preferred payment method — your transaction is fully secure.')</p>
                                    </div>
                                @endif
                                <div id="gateway-error" class="alert alert-danger d-none"></div>
                                <button type="submit" class="btn btn--base w-100 pay-now-btn" id="gateway-continue" disabled>
                                    {{ __('Select a payment method to continue') }}
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="gateway-details" class="mt-4 d-none"></div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function($) {
        "use strict";
        const $form = $('#checkout-form');
        const $methodInput = $('input[name=method_code]');
        const $continueBtn = $('#gateway-continue');
        const $details = $('#gateway-details');
        const $error = $('#gateway-error');
        const $autoHint = $('#gateway-auto-hint');
        const isTestMode = @json($isTestMode);
        const ipCountryCode = @json($ipCountryCode ?? null);
        const preferredMethodCode = @json($preferredMethodCode ?? null);
        const tLoading = @json(__('Loading'));
        const tPayNow = @json(__('Pay Now'));
        const tPayWith = @json(__('Pay with'));
        const tSomethingWrong = @json(__('Something went wrong'));
        const tSelectMethod = @json(__('Please select a payment method'));
        const tSelectMethodToContinue = @json(__('Select a payment method to continue'));
        const tAutoMethodSelected = @json(__('Payment method auto-selected for your location'));
        const tDetectedRegion = @json(__('Region'));
        const tTestPayment = @json(__('Complete Test Payment'));
        const euroAreaCountries = ['AT', 'BE', 'CY', 'DE', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PT', 'SI', 'SK'];

        const getSelectedGateway = () => $('.payment-option.is-active').first();
        const getSelectedMethodName = () => (getSelectedGateway().data('name') || '').toString().trim();

        const getSubmitLabel = () => {
            const selectedMethodName = getSelectedMethodName();
            if (!selectedMethodName) {
                return tSelectMethodToContinue;
            }

            if (isTestMode) {
                return tTestPayment;
            }

            return `${tPayWith} ${selectedMethodName}`;
        };

        const syncContinueButtonState = () => {
            const hasMethod = !!$methodInput.val();
            $continueBtn.removeClass('d-none');
            $continueBtn.prop('disabled', !hasMethod);
            $continueBtn.toggleClass('is-disabled', !hasMethod);
            $continueBtn.text(getSubmitLabel());
        };

        const hideAutoHint = () => {
            $autoHint.addClass('d-none').text('');
        };

        const showAutoHint = () => {
            const region = (ipCountryCode || '').toString().trim().toUpperCase();
            const message = region ? `${tAutoMethodSelected} (${tDetectedRegion}: ${region})` : tAutoMethodSelected;
            $autoHint.removeClass('d-none').text(message);
        };

        const activateGateway = ($gateway, autoSelected = false) => {
            if (!$gateway || !$gateway.length) {
                return;
            }

            $methodInput.val($gateway.data('value'));
            $('.payment-option').removeClass('is-active');
            $gateway.addClass('is-active');
            resetDetails();

            if (autoSelected) {
                showAutoHint();
            } else {
                hideAutoHint();
            }
        };

        const findGatewayByMethodCode = (methodCode) => {
            if (!methodCode) {
                return $();
            }
            const normalized = methodCode.toString();
            return $('.gateway').filter(function () {
                return ($(this).data('value') || '').toString() === normalized;
            }).first();
        };

        const pickGatewayByLocation = () => {
            const $gateways = $('.gateway');
            if (!$gateways.length) {
                return $();
            }

            const $fiatGateways = $gateways.filter(function () {
                return Number($(this).data('crypto') || 0) === 0;
            });

            const $cryptoGateways = $gateways.filter(function () {
                return Number($(this).data('crypto') || 0) === 1;
            });

            if (!$fiatGateways.length) {
                return $gateways.first();
            }

            if (!$cryptoGateways.length) {
                return $fiatGateways.first();
            }

            const region = (ipCountryCode || '').toString().trim().toUpperCase();
            const isEuroRegion = region && euroAreaCountries.includes(region);

            if (isEuroRegion) {
                const $eurFiat = $fiatGateways.filter(function () {
                    return ($(this).data('currency') || '').toString().toUpperCase() === 'EUR';
                }).first();

                if ($eurFiat.length) {
                    return $eurFiat;
                }

                return $fiatGateways.first();
            }

            const allFiatAreEuro = $fiatGateways.toArray().every((gateway) => {
                return (($(gateway).data('currency') || '').toString().toUpperCase() === 'EUR');
            });

            if (allFiatAreEuro) {
                return $cryptoGateways.first();
            }

            return $fiatGateways.first();
        };

        const openPaymentModal = () => {
            const $modal = $details.find('.payment-modal');
            if ($modal.length) {
                $modal.addClass('is-open');
                $('body').addClass('payment-modal-open');
            }
        };

        const closePaymentModal = () => {
            const $modal = $details.find('.payment-modal');
            if ($modal.length) {
                $modal.removeClass('is-open');
            }
            $('body').removeClass('payment-modal-open');
            $details.addClass('d-none').empty();
            syncContinueButtonState();
        };

        const showError = (message) => {
            $error.removeClass('d-none').text(message);
        };

        const resetDetails = () => {
            $details.addClass('d-none').empty();
            $error.addClass('d-none').text('');
            syncContinueButtonState();
        };

        const setLoading = () => {
            $error.addClass('d-none').text('');
            $details.removeClass('d-none').html(`<div class="text-center py-4"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${tLoading}...</div>`);
            $continueBtn.prop('disabled', true).text(`${tLoading}...`);
        };

        const renderStripeJsForm = (payload) => {
            const form = document.createElement('form');
            form.action = payload.url;
            form.method = payload.method || 'post';

            const token = document.querySelector('input[name="_token"]');
            if (token) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = '_token';
                hidden.value = token.value;
                form.appendChild(hidden);
            }

            const script = document.createElement('script');
            script.src = payload.src;
            script.className = 'stripe-button';
            if (payload.val) {
                Object.keys(payload.val).forEach((key) => {
                    script.setAttribute(`data-${key}`, payload.val[key]);
                });
            }
            form.appendChild(script);

            const hidden = document.createElement('div');
            hidden.className = 'stripe-js-hidden';
            hidden.appendChild(form);

            $details.addClass('d-none').empty().append(hidden);

            const triggerStripeCheckout = () => {
                const stripeBtn = hidden.querySelector('.stripe-button-el');
                if (stripeBtn) {
                    stripeBtn.style.display = 'none';
                    stripeBtn.click();
                    return;
                }
                setTimeout(triggerStripeCheckout, 80);
            };

            triggerStripeCheckout();
        };

        const handleResponse = (response) => {
            if (!response || !response.status) {
                showError(tSomethingWrong);
                syncContinueButtonState();
                return;
            }

            if (response.status === 'redirect' && response.redirect_url) {
                window.location.href = response.redirect_url;
                return;
            }

            if (response.status === 'stripe_checkout' && response.session_id && response.publishable_key) {
                const stripe = Stripe(response.publishable_key);
                stripe.redirectToCheckout({ sessionId: response.session_id });
                return;
            }

            if (response.status === 'stripe_js') {
                renderStripeJsForm(response);
                return;
            }

            if (response.status === 'form' && response.html) {
                $details.removeClass('d-none').html(response.html);
                $continueBtn.addClass('d-none');
                openPaymentModal();
                return;
            }

            showError(response.message || tSomethingWrong);
            syncContinueButtonState();
        };

        $(".gateway").on("click", function () {
            activateGateway($(this));
        });

        const preferredGateway = findGatewayByMethodCode(preferredMethodCode);
        if (preferredGateway.length) {
            activateGateway(preferredGateway, true);
        } else {
            const locationGateway = pickGatewayByLocation();
            if (locationGateway.length) {
                activateGateway(locationGateway, true);
            } else {
                syncContinueButtonState();
            }
        }

        $form.on('submit', function(e){
            const methodCode = $methodInput.val();
            if (!methodCode) {
                e.preventDefault();
                showError(tSelectMethod);
                return;
            }

            if (isTestMode) {
                return;
            }

            e.preventDefault();

            setLoading();
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json'
            }).done(function(response){
                handleResponse(response);
            }).fail(function(xhr){
                const resp = xhr.responseJSON || {};
                const message = resp.message || (resp.messages && resp.messages[0]) || tSomethingWrong;
                showError(message);
                syncContinueButtonState();
                $details.addClass('d-none').empty();
            });
        });

        $details.on('click', '.payment-modal__close, .payment-modal__backdrop', function() {
            closePaymentModal();
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaymentModal();
            }
        });
    })(jQuery)
</script>
@endpush

@push('script-lib')
    <script src="https://js.stripe.com/v3/"></script>
@endpush

@push('style')
    <style>
        @media (max-width: 450px) {
            .jp-card {
                left: 80%;
                transform: translateX(-50%);
            }
        }

        .payment-modal-open {
            overflow: hidden;
        }

        .payment-modal {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 1050;
            padding: 24px;
        }

        .payment-modal.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .payment-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
        }

        .payment-modal .modal {
            display: block;
            position: relative;
            z-index: 1;
        }

        .payment-modal__close {
            position: absolute;
            top: 10px;
            right: 12px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: rgba(0, 0, 0, 0.08);
            color: #1b1b1b;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .payment-modal .form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
            min-width: 320px;
        }

        .payment-modal .modal {
            width: fit-content;
            height: fit-content;
            background: #f5f5f5;
            box-shadow:
                0px 187px 75px rgba(0, 0, 0, 0.01),
                0px 105px 63px rgba(0, 0, 0, 0.05),
                0px 47px 47px rgba(0, 0, 0, 0.09),
                0px 12px 26px rgba(0, 0, 0, 0.1),
                0px 0px 0px rgba(0, 0, 0, 0.1);
            border-radius: 26px;
            max-width: 450px;
            width: 100%;
        }

        .payment-modal .modal::before {
            content: "";
            position: absolute;
            inset: 10px;
            background: #ffffff;
            border-radius: 20px;
            z-index: 0;
        }

        .payment-modal .form {
            position: relative;
            z-index: 1;
        }

        .payment-modal .payment--options {
            width: calc(100% - 40px);
            display: grid;
            grid-template-columns: 33% 34% 33%;
            gap: 20px;
            padding: 10px;
            margin: 0 auto;
        }

        .payment-modal .payment--options button {
            height: 55px;
            background: #f2f2f2;
            border-radius: 11px;
            padding: 0;
            border: 0;
            outline: none;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .payment-modal .payment--options button svg {
            height: 18px;
        }

        .payment-modal .payment--options button:last-child svg {
            height: 22px;
        }

        .payment-modal .separator {
            width: calc(100% - 20px);
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 10px;
            color: #8b8e98;
            margin: 0 10px;
        }

        .payment-modal .separator > p {
            word-break: keep-all;
            display: block;
            padding-top: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 11px;
            margin: auto;
            text-transform: lowercase;
        }

        .payment-modal .separator .line {
            display: inline-block;
            width: 100%;
            height: 1px;
            border: 0;
            background-color: #e8e8e8;
            margin: auto;
        }

        .payment-modal .credit-card-info--form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .payment-modal .input_container {
            width: 100%;
            height: fit-content;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .payment-modal .split {
            display: grid;
            grid-template-columns: 4fr 2fr;
            gap: 15px;
        }

        .payment-modal .split input {
            width: 100%;
        }

        .payment-modal .input_label {
            font-size: 10px;
            color: #8b8e98;
            font-weight: 600;
        }

        .payment-modal .input_field {
            width: auto;
            height: 40px;
            padding: 0 0 0 16px;
            border-radius: 9px;
            outline: none;
            background-color: #f1f1f1;
            border: 1px solid #e5e5e500;
            transition: all 0.3s cubic-bezier(0.15, 0.83, 0.66, 1);
        }

        .payment-modal .input_field:focus {
            border: 1px solid transparent;
            box-shadow: 0px 0px 0px 2px #242424;
            background-color: transparent;
        }

        .payment-modal .purchase--btn {
            height: 55px;
            border-radius: 11px;
            border: 0;
            outline: none;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            background: linear-gradient(180deg, #2b2b2b 0%, #121212 100%);
            box-shadow:
                0px 0px 0px 0px #ffffff,
                0px 0px 0px 0px #000000;
            transition: all 0.3s cubic-bezier(0.15, 0.83, 0.66, 1);
        }

        .payment-modal .purchase--btn:hover {
            box-shadow:
                0px 0px 0px 2px #ffffff,
                0px 0px 0px 4px #0000003a;
        }

        .payment-modal .input_field::-webkit-outer-spin-button,
        .payment-modal .input_field::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .payment-modal .input_field[type="number"] {
            -moz-appearance: textfield;
        }

        .payment-options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
        }

        .payment-option {
            border: 1px solid transparent;
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 76px;
            position: relative;
            overflow: hidden;
        }

        .payment-option__content {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .payment-option__icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .payment-option img {
            height: 22px;
            width: auto;
            object-fit: contain;
        }

        .payment-option .text {
            font-weight: 600;
            font-size: 14px;
            color: #0f172a;
            text-align: left;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .payment-option__status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1.5px solid #cbd5e1;
            background: #ffffff;
            flex-shrink: 0;
            position: relative;
            transition: all 0.2s ease;
        }

        .payment-option__status::after {
            content: "";
            position: absolute;
            inset: 4px;
            border-radius: 50%;
            background: #4f46e5;
            transform: scale(0);
            transition: transform 0.2s ease;
        }

        .payment-option:hover {
            border-color: #c7d2fe;
            background: #eef2ff;
        }

        .payment-option.is-active {
            border-color: #4f46e5;
            color: #312e81;
            background: #eef2ff;
            box-shadow: 0 0 0 1px rgba(79, 70, 229, 0.1);
        }

        .payment-option.is-active .text {
            color: #312e81;
            font-weight: 700;
        }

        .payment-option.is-active .payment-option__icon {
            border-color: #c7d2fe;
            background: #ffffff;
        }

        .payment-option.is-active .payment-option__status {
            border-color: #4f46e5;
            background: #ffffff;
        }

        .payment-option.is-active .payment-option__status::after {
            transform: scale(1);
        }

        #gateway-continue.pay-now-btn {
            margin-top: 4px;
            min-height: 58px;
            border-radius: 14px;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.01em;
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            border: 1px solid #0f766e;
            color: #ffffff;
            box-shadow: 0 14px 24px rgba(15, 118, 110, 0.24);
            transition: all 0.2s ease;
        }

        #gateway-continue.pay-now-btn:hover,
        #gateway-continue.pay-now-btn:focus-visible {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(15, 118, 110, 0.28);
            color: #ffffff;
        }

        #gateway-continue.pay-now-btn:disabled,
        #gateway-continue.pay-now-btn.is-disabled {
            background: #c7d7d2;
            border-color: #c7d7d2;
            color: #f8fbfa;
            box-shadow: none;
            transform: none;
            cursor: not-allowed;
            opacity: 1;
        }

        .gateway-auto-hint {
            margin-top: 10px;
            margin-bottom: 0;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .payment-gateway-preview .payment-gateway-preview__content {
            position: relative;
            z-index: 1;
            padding: 28px 24px 24px;
        }

        .payment-gateway-preview.modal {
            max-width: min(1080px, 96vw);
            width: 100%;
        }

        .payment-gateway-preview .payment-gateway-preview__eyebrow {
            margin-bottom: 6px;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .payment-gateway-preview .payment-gateway-preview__title {
            margin-bottom: 18px;
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
        }

        .payment-gateway-preview .payment-gateway-preview__row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-size: 14px;
        }

        .payment-gateway-preview .payment-gateway-preview__value {
            max-width: 70%;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .payment-gateway-preview .payment-gateway-preview__row:last-of-type {
            margin-bottom: 20px;
        }

        .payment-gateway-preview .payment-gateway-preview__amount {
            color: #87c5a6;
            font-size: 20px;
            font-weight: 800;
        }

        .payment-gateway-preview .payment-gateway-preview__frame-wrap {
            width: 100%;
            border: 1px solid #dbe1ea;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            margin-bottom: 14px;
            position: relative;
            height: min(76vh, 760px);
        }

        .payment-gateway-preview .payment-gateway-preview__frame-wrap::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 58px;
            background: #ffffff;
            z-index: 3;
            pointer-events: none;
        }

        .payment-gateway-preview .payment-gateway-preview__frame-wrap::after {
            content: "";
            position: absolute;
            top: 18px;
            right: 0;
            width: 42%;
            height: 72px;
            background: #ffffff;
            z-index: 2;
        }

        .payment-gateway-preview .payment-gateway-preview__frame {
            position: absolute;
            top: 0;
            left: 0;
            width: 200%;
            height: calc(100% + 96px);
            border: 0;
            display: block;
            background: #ffffff;
            max-width: none;
            transform: translate(-50%, -96px);
            transform-origin: top left;
        }

        .payment-gateway-preview .payment-gateway-preview__button {
            display: inline-flex;
            width: 100%;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            padding: 14px 16px;
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .payment-gateway-preview .payment-gateway-preview__button:hover {
            background: #1e293b;
            color: #ffffff;
        }

        @media (max-width: 767px) {
            .payment-gateway-preview .payment-gateway-preview__frame-wrap {
                height: 74vh;
            }

            .payment-gateway-preview .payment-gateway-preview__frame-wrap::before {
                height: 52px;
            }

            .payment-gateway-preview .payment-gateway-preview__frame-wrap::after {
                width: 46%;
                top: 14px;
                height: 64px;
            }

            .payment-gateway-preview .payment-gateway-preview__frame {
                width: 200%;
                height: calc(100% + 84px);
                transform: translate(-50%, -84px);
            }

            .payment-gateway-preview .payment-gateway-preview__title {
                font-size: 22px;
                line-height: 1.1;
            }
        }
    </style>
@endpush
