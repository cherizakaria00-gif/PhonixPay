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
                        <div class="widget-card stat-card stat-card--payment-chargeback">
                            <h3 class="widget-card__number">{{ showAmount($payment['total_refunded']) }}</h3>
                            <p>@lang('Payment Chargeback')</p>
                        </div>
                        <div class="widget-card stat-card stat-card--payment-succeed">
                            <h3 class="widget-card__number">{{ showAmount($payment['total_succeed']) }}</h3>
                            <p>@lang('Succeed Payment')</p>
                        </div>
                    </div>
                    <div class="widget-card-wrapper">
                        <div class="widget-card stat-card stat-card--withdraw-total">
                            <h3 class="widget-card__number">{{ showAmount($withdraw['total']) }}</h3>
                            <p>@lang('Total Withdraw')</p>
                        </div>
                        <div class="widget-card stat-card stat-card--withdraw-pending">
                            <h3 class="widget-card__number">{{ showAmount($withdraw['total_pending']) }}</h3>
                            <p>@lang('Pending Withdraw')</p>
                        </div>
                        <div class="widget-card stat-card stat-card--withdraw-approved">
                            <h3 class="widget-card__number">{{ showAmount($withdraw['total_approved']) }}</h3>
                            <p>@lang('Approved Withdraw')</p>
                        </div>
                        <div class="widget-card stat-card stat-card--withdraw-rejected">
                            <h3 class="widget-card__number">{{ showAmount($withdraw['total_rejected']) }}</h3>
                            <p>@lang('Rejected Withdraw')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="reporting-charts mt-3">
        <div class="row g-3">
            <div class="col-xl-6">
                <div class="card custom--card report-card h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">@lang('Payment Status')</h6>
                    </div>
                    <div class="card-body">
                        <div class="payment-status-canvas"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom--card report-card h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">@lang('Withdraw Status')</h6>
                    </div>
                    <div class="card-body">
                        <div class="withdraw-status-canvas"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



