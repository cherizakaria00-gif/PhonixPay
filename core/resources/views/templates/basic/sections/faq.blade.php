@php
    $faq = @getContent('faq.content', true)->data_values;
    $faqs = @getContent('faq.element', orderById:true);
@endphp

<!-- ====================================== Faq Section Start ============================== -->
<section class="faq pt-120 pb-60">
    <div class="faq-shapes">
        <img src="{{ asset($activeTemplateTrue.'frontend/images/shapes/faq-shape1.png') }}" alt="" class="faq-shape one">
        <img src="{{ asset($activeTemplateTrue.'frontend/images/shapes/faq-shape2.png') }}" alt="" class="faq-shape two">
    </div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12"> 
                <div class="section-heading">
                    <span class="section-heading__subtitle">{{ __(@$faq->heading) }}</span>
                    <h2 class="section-heading__title">{{ __(@$faq->subheading) }}</h2>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="faq-content">
                    <div class="custom--accordion accordion" id="accordionExample">
                        @foreach($faqs as $index => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{ $index }}">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" 
                                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                                        {{ __(@$faq->data_values->question) }}
                                    </button>
                                </h2>
                                <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : null }}" aria-labelledby="heading{{ $index }}" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <p class="accordion-body__desc"> {{ __(@$faq->data_values->answer) }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div> 
</section>
<!-- ====================================== Faq Section End ============================== -->

@push('style')
    <style>
        .accordion-item:last-child {
            border-bottom: none;
        }
    </style>
@endpush