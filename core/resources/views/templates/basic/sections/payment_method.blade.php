@php
    $paymentMethod = @getContent('payment_method.content', true)->data_values;
@endphp

<!--========================== Payment Section Start ==========================-->
<div class="payment-method py-60">
    <div class="container">
        <div class="payment-method__inner">
            <div class="payment-method__thumb">
                <img src="{{ getImage('assets/images/frontend/payment_method/' .@$paymentMethod->image, '1216x116') }}" alt="@lang('Payment Method')">
            </div>
        </div>
    </div>
</div>
<!--========================== Payment Section End ==========================-->