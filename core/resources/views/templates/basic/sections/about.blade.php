@php
    $about = @getContent('about.content', true)->data_values;
    $abouts = @getContent('about.element', orderById:true);
@endphp

<!--========================== About Section Start ==========================-->
<div class="about pt-60 pb-120">
    <div class="container">
        <div class="row gy-5 flex-wrap-reverse">
            <div class="col-lg-6 col-md-6 pe-lg-5">
                <div class="about-thumb-wrapper">
                    <div class="about-thumb">
                        <img src="{{ getImage('assets/images/frontend/about/' .@$about->image, '330x375') }}" alt="@lang('About')">
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6">
                <div class="about-content">
                    <div class="section-heading style-two">
                        <span class="section-heading__subtitle">{{ __(@$about->heading) }}</span>
                        <h2 class="section-heading__title">{{ __(@$about->subheading) }}</h2>
                        <p class="section-heading__desc">{{ __(@$about->description) }}</p>
                    </div>
                    <div class="about-counter-wrapper">
                        @foreach($abouts as $about)
                            <div class="about-counter">
                                <h2 class="about-counter__number">{{ __(@$about->data_values->title) }}</h2>
                                <span class="about-counter__text">{{ __(@$about->data_values->description) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--========================== About Section End ==========================-->