@php
    $caseStudy = @getContent('case_study.content', true)->data_values;
@endphp

<!-- ========================= Case Study Section Start Here ========================== -->
<section class="case-study section-bg py-120">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="section-heading">
                    <span class="section-heading__subtitle">{{ __(@$caseStudy->heading) }}</span>
                    <h2 class="section-heading__title">{{ __(@$caseStudy->subheading) }}</h2>
                </div>
            </div>
        </div>
        <div class="row gy-4">
            <div class="col-sm-6">
                <div class="case-study-item">
                    <div class="case-study-item__thumb">
                        <img src="{{ getImage('assets/images/frontend/case_study/' .@$caseStudy->image, '570x645') }}" alt="@lang('Case Study')">
                        <div class="case-study-item__content">
                            <h3 class="case-study-item__title">
                                <a href="javascript:void(0)" class="default-cursor">{{ __(@$caseStudy->title) }}</a>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="case-study-item">
                    <div class="case-study-item__thumb">
                        <img src="{{ getImage('assets/images/frontend/case_study/' .@$caseStudy->second_image, '570x305') }}" alt="@lang('Case Study')">
                        <div class="case-study-item__content">
                            <h3 class="case-study-item__title">
                                <a href="javascript:void(0)" class="default-cursor">{{ __(@$caseStudy->second_title) }}</a>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="case-study-item">
                    <div class="case-study-item__thumb">
                        <img src="{{ getImage('assets/images/frontend/case_study/' .@$caseStudy->third_image, '570x305') }}" alt="@lang('Case Study')">
                        <div class="case-study-item__content">
                            <h3 class="case-study-item__title">
                                <a href="javascript:void(0)" class="default-cursor">{{ __(@$caseStudy->third_title) }}</a>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ========================= Case Study Section End Here ============================ -->