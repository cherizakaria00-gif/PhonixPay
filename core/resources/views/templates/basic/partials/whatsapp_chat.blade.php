@php
    $whatsappNumber = preg_replace('/\D+/', '', '+19707807495');
    $whatsappText = rawurlencode('Hello Flujipay team, I need help with my account.');
    $whatsappUrl = "https://wa.me/{$whatsappNumber}?text={$whatsappText}";
@endphp

<div class="position-fixed" style="right: 18px; bottom: 18px; z-index: 1050;">
    <a href="{{ $whatsappUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill text-white fw-semibold"
       style="background:#25D366; box-shadow:0 10px 24px rgba(37,211,102,.35); text-decoration:none;">
        <i class="lab la-whatsapp" style="font-size: 24px; line-height:1;"></i>
        <span class="d-none d-sm-inline">@lang('WhatsApp Chat')</span>
    </a>
</div>
