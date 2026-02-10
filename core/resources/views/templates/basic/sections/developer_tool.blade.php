@php
    $developer = @getContent('developer_tool.content', true)->data_values;
    $developers = @getContent('developer_tool.element', orderById:true);
@endphp

<!-- =================================== Developer Tools Section Start Here ================================= -->
<section class="developer-tools py-120 bg-img" style="background-image: url({{ asset($activeTemplateTrue.'frontend/images/thumbs/developer-tools-bg.png') }});">
    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="developer-tools-thumb">
                    <img src="{{ getImage('assets/images/frontend/developer_tool/' .@$developer->image, '630x865') }}" alt="@lang('Image')">
                </div>
            </div>
            <div class="col-xxl-8 col-xl-7">
                <div class="developer-tools-content">
                    <div class="section-heading style-two">
                        <span class="section-heading__subtitle">{{ __(@$developer->heading) }}</span>
                        <h2 class="section-heading__title">{{ __(@$developer->subheading) }}</h2>
                    </div>
                    <div class="developer-tools-wrapper">
                        @foreach($developers as $developer)
                            <div class="developer-tools-item">
                                <div class="developer-tools-item__content">
                                    <div class="developer-tools-item__icon">
                                        <img src="{{ getImage('assets/images/frontend/developer_tool/' .@$developer->data_values->image, '630x865') }}" alt="@lang('Image')">
                                    </div>
                                    <div class="developer-tools-item__info">
                                        <h4 class="developer-tools-item__title">{{ __(@$developer->data_values->title) }}</h4>
                                        <p class="developer-tools-item__desc">{{ __(@$developer->data_values->description) }}</p>
                                    </div>
                                </div>
                                <div class="developer-tools-item__details">
                                    <span class="btn btn--light btn--icon"><i class="fas fa-check"></i></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- =================================== Developer Tools Section End Here ================================= -->