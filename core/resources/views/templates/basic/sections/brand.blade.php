@php
    $brand = @getContent('brand.content', true)->data_values;
    $brands = @getContent('brand.element');
@endphp

<!--========================== Brand Section Start ==========================-->
<div class="brands py-60 ">
    <div class="container">
        <span class="brand-title">{{ __(@$brand->heading) }}</span>
        <div class="brand-logos brand-slider">
            @foreach($brands as $brand)
                <img src="{{ getImage('assets/images/frontend/brand/' .@$brand->data_values->image, '128x32') }}" alt="@lang('Brand')">
            @endforeach
        </div>
    </div>
</div>
<!--========================== Brand Section End ==========================-->

@push('style-lib')
<!-- Slick -->
<link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/slick.css') }}">
@endpush

@push('script-lib')
<!-- Slick js -->
<script src="{{ asset($activeTemplateTrue.'js/slick.min.js') }}"></script>
@endpush

@push('script')
    <script>
        (function ($) {
        "use strict";
            // ========================= Client Slider Js Start ===============
                $('.brand-slider').slick({
                slidesToShow: 5,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 1000,
                pauseOnHover: true,
                speed: 2000 ,
                dots: false,
                arrows: false,
                prevArrow: '<button type="button" class="slick-prev"><i class="fas fa-long-arrow-alt-left"></i></button>',
                nextArrow: '<button type="button" class="slick-next"><i class="fas fa-long-arrow-alt-right"></i></button>',
                responsive: [
                    {
                    breakpoint: 1199,
                    settings: {
                        slidesToShow:5,
                    }
                    },
                    {
                    breakpoint: 991,
                    settings: {
                        slidesToShow: 4
                    }
                    },
                    {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: 4
                    }
                    },
                    {
                    breakpoint: 400,
                    settings: {
                        slidesToShow: 3
                    }
                    }
                ]
                });
            // ========================= Client Slider Js End ===================
        })(jQuery);
    </script>
@endpush