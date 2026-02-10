@php
    $banner = @getContent('banner.content', true)->data_values;
@endphp

<!--========================== Banner Section Start ==========================-->
<section class="banner">
    <div class="banner__shapes">
        <img src="{{ asset($activeTemplateTrue.'frontend/images/shapes/banner-shape1.png') }}" alt="" class="banner-shape one">
        <img src="{{ asset($activeTemplateTrue.'frontend/images/shapes/banner-shape2.png') }}" alt="" class="banner-shape two">
        <div class="banner-shape base"></div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="banner-content">
                    <span class="banner-content__subtitle">{{ __(@$banner->subheading) }}</span>
                    <h1 class="banner-content__title">{{ __(@$banner->heading) }}</h1>
                    <div class="banner-content__button">
                        <a href="{{ @$banner->button_url }}" class="btn btn--primary">{{ __(@$banner->button_text) }}</a>
                    </div>
                    <div class="banner-content__thumb">
                    <img src="{{ getImage('assets/images/frontend/banner/' .@$banner->image, '1080x635') }}" alt="@lang('banner')">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!--========================== Banner Section End ==========================-->