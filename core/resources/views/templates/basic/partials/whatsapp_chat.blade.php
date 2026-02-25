@php
    $whatsappNumber = preg_replace('/\D+/', '', '+19707807495');
    $whatsappText = rawurlencode('Hello Flujipay team, I need help with my account.');
    $whatsappUrl = "https://wa.me/{$whatsappNumber}?text={$whatsappText}";
@endphp

<div class="position-fixed" style="right: 18px; bottom: 18px; z-index: 1050;">
    <a href="{{ $whatsappUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 text-white fw-semibold"
       style="background:#87c5a6; box-shadow:0 10px 24px rgba(135,197,166,.35); text-decoration:none; min-width: 52px; justify-content: center;">
        <i class="lab la-whatsapp" style="font-size: 24px; line-height:1;"></i>
        <span class="d-none d-sm-inline">@lang('WhatsApp Chat')</span>
    </a>
</div>
