@php
    $testimonial = @getContent('testimonial.content', true)->data_values;
    $testimonials = @getContent('testimonial.element');
@endphp

<!--========================== Testimonials Section Start ==========================-->
<section class="testimonials pt-60 pb-120">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="section-heading-two d-flex flex-wrap justify-content-between">
                    <div class="section-heading style-two mb-0">
                        <div class="section-heading__inner">
                            <span class="section-heading__subtitle">{{ __(@$testimonial->heading) }}</span>
                            <h2 class="section-heading__title">{{ __(@$testimonial->subheading) }}</h2>
                        </div>
                    </div>
                    <div class="section-heading__right">
                        <a href="{{ @$testimonial->btn_url }}" class="btn btn--base btn--md outline">{{ __(@$testimonial->btn_text) }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="testimonial-slider">
            @foreach($testimonials as $testimonial)
                <div class="testimonails-card">
                    <div class="testimonial-item">
                        <div class="testimonial-item__content">
                            <div class="testimonial-item__logo">
                                <img src="{{ getImage('assets/images/frontend/testimonial/' .@$testimonial->data_values->company, '170x45') }}" alt="@lang('Company')">
                            </div>
                            <p class="testimonial-item__desc">{{ __(@$testimonial->data_values->remark) }}</p>
                            <div class="testimonial-item__info">
                                <div class="testimonial-item__thumb">
                                    <img src="{{ getImage('assets/images/frontend/testimonial/' .@$testimonial->data_values->image, '59x59') }}" alt="@lang('Image')">
                                </div>
                                <div class="testimonial-item__details">
                                    <h4 class="testimonial-item__name">{{ __(@$testimonial->data_values->name) }}</h4>
                                    <span class="testimonial-item__designation">{{ __(@$testimonial->data_values->designation) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
<!--========================== Testimonials Section End ==========================-->

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
            // ========================= Slick Slider Js Start ==============
            $('.testimonial-slider').slick({
            slidesToShow: 2,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 2000,
            speed: 1500,
            dots: true,
            pauseOnHover: true,
            arrows: false,
            prevArrow: '<button type="button" class="slick-prev"><i class="fas fa-long-arrow-alt-left"></i></button>',
            nextArrow: '<button type="button" class="slick-next"><i class="fas fa-long-arrow-alt-right"></i></button>',
            responsive: [
                {
                    breakpoint: 1199,
                    settings: {
                    arrows: false,
                    slidesToShow: 2,
                    dots: true,
                    }
                },
                {
                    breakpoint: 991,
                    settings: {
                    arrows: false,
                    slidesToShow: 2
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                    arrows: false,
                    slidesToShow: 1
                    }
                }
                ]
            });
            // ========================= Slick Slider Js End ===================
        })(jQuery);
    </script>
@endpush