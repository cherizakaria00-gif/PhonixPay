@php
    $paymentSolution = @getContent('payment_solution.content', true)->data_values;
    $paymentSolutions = @getContent('payment_solution.element', orderById:true);
@endphp

<!-- =============================== Payment Soluction Section Start ================= -->
<section class="payment-solution py-120 section-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="section-heading">
                    <span class="section-heading__subtitle">{{ __(@$paymentSolution->heading) }}</span>
                    <h2 class="section-heading__title">{{ __(@$paymentSolution->subheading) }}</h2>
                </div>
            </div>
        </div>
        <div class="row gy-4 justify-content-center">
            @foreach($paymentSolutions as $paymentSolution)
                <div class="col-md-4 col-sm-6 col-xsm-6">
                    <div class="payment-solution-item">
                        <h5 class="payment-solution-item__title">{{ __(@$paymentSolution->data_values->title) }}</h5>
                        <p class="payment-solution-item__desc">{{ __(@$paymentSolution->data_values->description) }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
<!-- =============================== Payment Soluction Section End ================= -->