@php
    $whatsappNumber = preg_replace('/\D+/', '', '+19707807495');
    $whatsappText = rawurlencode('Hello Flujipay team, I need help with my account.');
    $whatsappUrl = "https://wa.me/{$whatsappNumber}?text={$whatsappText}";
@endphp

<style>
    .whatsapp-fab {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 1050;
    }

    .whatsapp-fab__btn {
        width: 58px;
        height: 58px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #25D366;
        color: #fff;
        text-decoration: none;
        position: relative;
        box-shadow: 0 12px 28px rgba(37, 211, 102, 0.45);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .whatsapp-fab__btn::before,
    .whatsapp-fab__btn::after {
        content: "";
        position: absolute;
        border-radius: 999px;
        inset: -8px;
        background: rgba(37, 211, 102, 0.25);
        animation: whatsapp-pulse 2.2s infinite;
        z-index: -1;
    }

    .whatsapp-fab__btn::after {
        inset: -18px;
        background: rgba(37, 211, 102, 0.15);
        animation-delay: 0.6s;
    }

    .whatsapp-fab__btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(37, 211, 102, 0.55);
    }

    @keyframes whatsapp-pulse {
        0% {
            transform: scale(0.85);
            opacity: 0.9;
        }
        70% {
            transform: scale(1.2);
            opacity: 0;
        }
        100% {
            transform: scale(1.2);
            opacity: 0;
        }
    }
</style>

<div class="whatsapp-fab">
    <a href="{{ $whatsappUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="whatsapp-fab__btn"
       aria-label="@lang('WhatsApp Chat')">
        <i class="lab la-whatsapp" style="font-size: 26px; line-height:1;"></i>
    </a>
</div>
