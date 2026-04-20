@extends($activeTemplate.'layouts.app')

@section('app')
<div class="payment-modal is-open">
    <div class="payment-modal__backdrop"></div>
    <div class="modal payment-gateway-preview">
        <button type="button" class="payment-modal__close" aria-label="@lang('Close')">&times;</button>
        <div class="payment-gateway-preview__content">
            <div class="payment-gateway-preview__frame-wrap">
                <iframe
                    src="{{ $redirectUrl }}"
                    class="payment-gateway-preview__frame payment-gateway-preview__frame--white-only"
                    title="@lang('Payment Checkout')"
                    loading="eager"
                    allow="payment *"
                ></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .payment-modal {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9998;
        padding: 18px;
    }

    .payment-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(3px);
    }

    .payment-gateway-preview {
        position: relative;
        z-index: 1;
        width: min(1150px, 98vw);
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 30px 90px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(15, 23, 42, 0.08);
    }

    .payment-modal .modal {
        display: block;
    }

    .payment-modal__close {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 34px;
        height: 34px;
        border: none;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.08);
        color: #111827;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
    }

    .payment-gateway-preview__content {
        padding: 24px;
    }

    .payment-gateway-preview__frame-wrap {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 14px;
        overflow: hidden;
        background: #fff;
    }

    .payment-gateway-preview__frame {
        width: 100%;
        height: min(76vh, 820px);
        border: 0;
        display: block;
    }

    /* Desktop checkout provider view is split in two columns.
       Keep only the right/white panel visible inside the popup. */
    @media (min-width: 992px) {
        .payment-gateway-preview__frame--white-only {
            width: 200%;
            max-width: none;
            transform: translateX(-50%);
            transform-origin: top left;
        }
    }

    /* Mobile provider view stacks the blue summary above the form.
       Crop the top part so only the white payment form is visible. */
    @media (max-width: 991px) {
        .payment-gateway-preview__frame-wrap {
            position: relative;
            height: 78vh;
        }

        .payment-gateway-preview__frame--white-only {
            height: calc(78vh + 230px);
            margin-top: -230px;
            transform: none;
        }
    }

    @media (max-width: 767px) {
        .payment-gateway-preview__content {
            padding: 12px;
        }

        .payment-gateway-preview__frame:not(.payment-gateway-preview__frame--white-only) {
            height: 78vh;
        }

        .payment-gateway-preview__frame--white-only {
            height: calc(78vh + 230px);
            margin-top: -230px;
        }
    }
</style>
@endpush

@push('script')
<script>
    (function () {
        const closeBtn = document.querySelector('.payment-modal__close');
        const backdrop = document.querySelector('.payment-modal__backdrop');
        const goBack = function () {
            window.history.back();
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', goBack);
        }
        if (backdrop) {
            backdrop.addEventListener('click', goBack);
        }
    })();
</script>
@endpush
