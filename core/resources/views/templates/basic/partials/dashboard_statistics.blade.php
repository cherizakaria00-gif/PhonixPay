<div class="col-12">
    <div class="widget-wrapper">
        <div class="row">
            <div class="col-xl-7 border--end position-relative">
                <div>
                    <div class="payment_canvas">
                        <canvas height="190" id="payment_chart" class="mt-4"></canvas>
                    </div>
                    <h5 class="payment-statistics">@lang('Payment Statistics')</h5>
                </div>
            </div>
            <div class="col-xl-5 ps-xl-0 pe-xl-0">
                <div class="reports">
                    <div class="widget-card-wrapper">
                        <div class="widget-card">
                            <h3 class="widget-card__number">{{ showAmount($payment['total']) }}</h3>
                            <p>@lang('Payment Requests')</p>
                        </div>
                        <div class="widget-card">
                            <h3 class="widget-card__number">{{ showAmount($payment['total_initiated']) }}</h3>
                            <p>@lang('Initiated Payment')</p>
                        </div>
                        <div class="widget-card">
                            <h3 class="widget-card__number">{{ showAmount($payment['total_succeed']) }}</h3>
                            <p>@lang('Succeed Payment')</p>
                        </div>
                        <div class="widget-card">
                            <h3 class="widget-card__number">{{ showAmount($payment['total_canceled']) }}</h3>
                            <p>@lang('Canceled Payment')</p>
                        </div>
                    </div>
                    <div class="widget-card-wrapper">
                        <div class="widget-card">
                            <h3 class="widget-card__number">{{ showAmount($withdraw['total']) }}</h3>
                            <p>@lang('Total Withdraw')</p>
                        </div>
                        <div class="widget-card">
                            <h3 class="widget-card__number">{{ showAmount($withdraw['total_pending']) }}</h3>
                            <p>@lang('Pending Withdraw')</p>
                        </div>
                        <div class="widget-card">
                            <h3 class="widget-card__number">{{ showAmount($withdraw['total_approved']) }}</h3>
                            <p>@lang('Approved Withdraw')</p>
                        </div>
                        <div class="widget-card">
                            <h3 class="widget-card__number">{{ showAmount($withdraw['total_rejected']) }}</h3>
                            <p>@lang('Rejected Withdraw')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



