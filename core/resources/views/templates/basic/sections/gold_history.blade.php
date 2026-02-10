@php
    $history = @getContent('gold_history.content', true)->data_values;
    $allHistory = @getContent('gold_history.element', orderById:true);
@endphp

<!--========================== Our Mission Section End ==========================-->
<section class="gold-history pt-60 pb-60">
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-6">
                <div class="payout-content">
                    <div class="section-heading style-two">
                        <h2 class="section-heading__title">{{ __(@$history->heading) }}</h2>
                        <p class="section-heading__desc">{{ __(@$history->subheading) }}</p>
                    </div>
                    <div class="payout-item-wrapper">
                        @foreach($allHistory as $index => $data)
                            <div class="payout-item">
                                <h3 class="payout-item__icon">{{ ++$index }}</h3>
                                <div class="payout-item__content">
                                    <h5 class="payout-item__title">{{ @$data->data_values->date }}</h5>
                                    <p class="payout-item__desc">{{ @$data->data_values->description }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="gold-history-thumb">
                    <img src="{{ getImage('assets/images/frontend/gold_history/' .@$history->image, '500x560') }}" alt="@lang('Image')">
                </div>
            </div>
        </div>
    </div>
</section>
<!--========================== Our Mission Section End ==========================-->