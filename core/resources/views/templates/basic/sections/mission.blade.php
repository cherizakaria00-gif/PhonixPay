@php
    $mission = @getContent('mission.content', true)->data_values;
    $missions = @getContent('mission.element', orderById:true);
@endphp

<!--========================== Our Mission Section Start ==========================-->
<div class="our-mission pt-120 pb-60">
    <div class="container">
        <div class="row gy-5 flex-wrap-reverse">
            <div class="col-lg-6 col-md-6 pe-lg-5">
                <div class="about-thumb-two">
                    <img src="{{ getImage('assets/images/frontend/mission/' .@$mission->image, '500x560') }}" alt="@lang('mission')">
                </div>
            </div>
            <div class="col-lg-6 col-md-6">
                <div class="our-mission-content">
                    <div class="section-heading style-two">
                        <h2 class="section-heading__title">{{ __(@$mission->heading) }}</h2>
                        <p class="section-heading__desc">{{ __(@$mission->description) }}</p>
                    </div>
                    <ul class="text-list">
                        @foreach($missions as $data)
                            <li class="text-list__item">{{ __(@$data->data_values->list_text) }}</li>
                        @endforeach
                    </ul>
                    <div class="contact-us mt-lg-5 mt-4">
                        <a href="{{ @$mission->button_url }}" class="btn btn--base">{{ __(@$mission->button_text) }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--========================== Our Mission Section End ==========================-->