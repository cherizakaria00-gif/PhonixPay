@php
    $feature = @getContent('feature.content', true)->data_values;
    $features = @getContent('feature.element', orderById:true);
@endphp

<!-- ============================= Features Section Start Here ============================= -->
<section class="features py-120 section-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="section-heading">
                    <span class="section-heading__subtitle">{{ __(@$feature->heading) }}</span>
                    <h2 class="section-heading__title">{{ __(@$feature->subheading) }}</h2>
                </div>
            </div>
        </div>
        <div class="row gy-4">
            @foreach($features as $feature)
                <div class="col-lg-4 col-sm-6 col-xsm-6">
                    <div class="feature-item">
                        <div class="feature-item__icon">
                            <img src="{{ getImage('assets/images/frontend/feature/' .@$feature->data_values->image, '39x39') }}" alt="@lang('Feature')">
                        </div>
                        <div class="feature-item__content">
                            <h5 class="feature-item__title">{{ __(@$feature->data_values->title) }}</h5>
                            <p class="feature-item__desc">{{ __(@$feature->data_values->description) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
<!-- ============================= Features Section End Here ============================= -->