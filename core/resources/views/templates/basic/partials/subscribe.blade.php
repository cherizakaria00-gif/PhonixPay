@php
    $subscribe = @getContent('subscribe.content', true)->data_values;
@endphp

<div class="subscribe">
    <button type="submit" class="subscribe-btn btn btn--primary"> 
        <span class="text">@lang('Subscribe')</span> <span class="icon"><i class="far fa-bell"></i></span>
    </button>
    <div class="subscribe-box">
        <button class="subscribe__close"><i class="las la-times"></i></button>
        <h5 class="subscribe-box__title mb-2">{{ __(@$subscribe->heading) }}</h5>
        <p class="subscribe-box__desc mb-2">{{ __(@$subscribe->subheading) }}</p>
        <form action="#" class="subscription-form form">
            @csrf
            <div class="input-group">
                <input type="email" name="email" class="form-control form--control" required="">
                <button type="submit" class="input-group-text btn btn--base btn--sm exclude">@lang('Submit')</button>
            </div>
        </form>
    </div>
</div>

@push('style')
<style>
    .subscribe {
        position: fixed;
        bottom: 50px;
        left: 50px;
        z-index: 9;
    }
    @media (max-width: 1630px) {
        .subscribe {
            bottom: 30px;
        }
    }
    @media (max-width: 500px) {
        .subscribe {
            left: 15px;
        }
    }
    @media (max-width: 1630px) {
        .subscribe-btn {
            padding: 0;
            width: 40px;
            height: 40px;
            text-align: center;
            line-height: 40px;
            border-radius: 3px; 
        }
        .subscribe-btn .text {
            display: none;
        }
        .subscribe-btn .icon {
            font-size: 15px;
            animation: ring 2s infinite ease;
        }
    }
    @media screen and (max-width: 767px) {
        .subscribe-btn {
            width: 35px;
            height: 35px;
            line-height: 35px;
        }
    }
    @keyframes ring {
        0% {
            transform: rotate(35deg);
        }
        12.5% {
            transform: rotate(-30deg);
        }
        25% {
            transform: rotate(25deg);
        }
        37.5% {
            transform: rotate(-20deg);
        }
        50% {
            transform: rotate(15deg);
        }
        62.5% {
            transform: rotate(-10deg);
        }
        75% {
            transform: rotate(5deg);
        }
        100% {
            transform: rotate(0deg);
        }
    }
    .subscribe-box {
        background-color: #fff;
        padding: 40px;
        box-shadow: 0 0 15px 3px #24232312;
        border-radius: 10px;
        width: 450px;
        position: absolute;
        bottom: 100%;
        left: 0;
        margin-bottom: 25px;
        z-index: 99;
        border: 1px solid #0000001a;
        transform: scale(.95);
        visibility: hidden;
        opacity: 0;
        transition: .2s linear;
    }
    @media (max-width: 500px) {
        .subscribe-box {
            width: 300px;
            padding: 30px 15px;
        }
    }
    .subscribe-box::before {
        position: absolute;
        content: "";
        left: 95px;
        bottom: -10px;
        width: 20px;
        height: 20px;
        background-color: #fff;
        transform: rotate(45deg);
        z-index: -1;
        border-style: solid;
        border-width: 1px;
        border-color: #0000 #0000001a #0000001a #00800000;
    }
    @media (max-width: 1630px) {
        .subscribe-box::before {
            left: 15px;
        }
    }
    .subscribe-box.show {
        transform: scale(1);
        visibility: visible;
        opacity: 1;
    }
    .subscribe__close {
        border: 0;
        outline: 0;
        background: transparent;
        position: absolute;
        right: 15px;
        top: 15px;
        z-index: 9;
        font-size: 15px;
        color: hsl(var(--body-color)); 
    }
    @media (max-width: 500px) {
        .subscribe__close {
            right: 10px;
            top: 10px;
        }
    }
</style> 
@endpush

@push('script')
<script>
    (function ($) {
        "use strict";
        $('.subscribe-btn').on('click', function () {
            $('.subscribe-box').toggleClass('show')
        });

        $('.subscribe__close').on('click', function () {
            $('.subscribe-box').removeClass('show')
        });

        var formEl = $(".subscription-form");
            formEl.on('submit', function(e) {
                e.preventDefault();
                var data = formEl.serialize();

                if (!formEl.find('input[name=email]').val()) {
                    return notify('error', 'Email field is required');
                }

                $.ajax({
                    url: "{{ route('subscribe') }}",
                    method: 'post',
                    data: data,

                    success: function(response) {
                        if (response.success) {
                            formEl.find('input[name=email]').val('')
                            notify('success', response.message);
                        } else {
                            $.each(response.error, function(key, value) {
                                formEl.find('input[name=email]').val('')
                                notify('error', value);
                            });
                        }
                    }
                });
            });
    })(jQuery);
</script>
@endpush