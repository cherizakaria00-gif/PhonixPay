<div class="payment-modal">
    <div class="payment-modal__backdrop"></div>
    <div class="modal payment-gateway-preview">
        <button type="button" class="payment-modal__close" aria-label="@lang('Close')">&times;</button>
        <div class="payment-gateway-preview__content">
            <p class="payment-gateway-preview__eyebrow">@lang('Secure Checkout')</p>
            <h4 class="payment-gateway-preview__title">@lang('Review Payment Before Redirect')</h4>

            <div class="payment-gateway-preview__row">
                <span>@lang('Gateway')</span>
                <strong>{{ $gatewayName }}</strong>
            </div>
            <div class="payment-gateway-preview__row">
                <span>@lang('Reference')</span>
                <strong>{{ $reference }}</strong>
            </div>
            <div class="payment-gateway-preview__row">
                <span>@lang('Amount')</span>
                <strong class="payment-gateway-preview__amount">{{ $amountText }}</strong>
            </div>

            <a href="{{ $redirectUrl }}" class="payment-gateway-preview__button">
                @lang('Continue to Payment')
            </a>
        </div>
    </div>
</div>
