@php
    $payout = @getContent('payout.content', true)->data_values;
    $payouts = @getContent('payout.element', orderById:true);
@endphp

<!-- =================================== Payout Section Start ====================================== -->
<div class="payout pt-60 pb-120">
    <div class="container">
        <div class="row gy-4 flex-wrap-reverse">
            <div class="col-md-6 d-md-block d-none">
                <div class="payout-thumb girl-img">
                    <img src="{{ getImage('assets/images/frontend/payout/' .@$payout->image, '565x775') }}" alt="@lang('Payout')">
                </div>
            </div>
            <div class="col-md-6">
                <div class="payout-content">
                    <div class="section-heading style-two">
                        <h2 class="section-heading__title">{{ __(@$payout->heading) }}</h2>
                        <p class="section-heading__desc">{{ __(@$payout->subheading) }}</p>
                    </div>
                    <div class="payout-item-wrapper">
                        @foreach($payouts as $payout)
                            <div class="payout-item">
                                <div class="payout-item__icon">
                                    <img src="{{ getImage('assets/images/frontend/payout/' .@$payout->data_values->image, '565x775') }}" alt="@lang('Payout')">
                                </div>
                                <div class="payout-item__content">
                                    <h5 class="payout-item__title">Advanced Cybersecurity</h5>
                                    <p class="payout-item__desc">Advanced technology cybersecurity tools include everything</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- =================================== Payout Section End ====================================== -->