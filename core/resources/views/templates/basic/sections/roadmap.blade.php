@php
    $roadmap = @getContent('roadmap.content', true)->data_values;
@endphp


<!-- ============================= Features Section Start Here ============================= -->
<div class="about py-120">
    <div class="container">
        <div class="row gy-5 flex-wrap-reverse">
            <div class="col-lg-6 col-md-6 pe-lg-5">
                <div class="about-thumb-three">
                    <img src="{{ getImage('assets/images/frontend/roadmap/' .@$roadmap->image, '620x645') }}" alt="@lang('Image')">
                </div>
            </div>
            <div class="col-lg-6 col-md-6">
                <div class="about-content">
                    <div class="section-heading style-two mb-0">
                        <h2 class="section-heading__title">{{ __(@$roadmap->heading) }}</h2>
                        <p class="section-heading__desc fs-18">{{ __(@$roadmap->description) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ============================= Features Section End Here ============================= -->