<div class="col-12">
    <div class="dashboard-stat-grid">
        <div class="stat-card stat-card--payment-succeed">
            <div class="stat-card__content">
                <p class="stat-card__label">@lang('Succeed Payment')</p>
                <h3 class="stat-card__value">{{ showAmount($payment['total_succeed'], currencyFormat:false) }}</h3>
                <span class="stat-card__delta stat-card__delta--positive">@lang('Updated this month')</span>
            </div>
            <div class="stat-card__icon">
                <i class="las la-check-circle"></i>
            </div>
        </div>
        <div class="stat-card stat-card--withdraw-total">
            <div class="stat-card__content">
                <p class="stat-card__label">@lang('Total Withdraw')</p>
                <h3 class="stat-card__value">{{ showAmount($withdraw['total'], currencyFormat:false) }}</h3>
                <span class="stat-card__delta stat-card__delta--positive">@lang('Updated this month')</span>
            </div>
            <div class="stat-card__icon">
                <i class="las la-wallet"></i>
            </div>
        </div>
        <div class="stat-card stat-card--withdraw-pending">
            <div class="stat-card__content">
                <p class="stat-card__label">@lang('Pending Withdraw')</p>
                <h3 class="stat-card__value">{{ showAmount($withdraw['total_pending'], currencyFormat:false) }}</h3>
                <span class="stat-card__delta stat-card__delta--neutral">@lang('No pending withdrawals')</span>
            </div>
            <div class="stat-card__icon">
                <i class="las la-hourglass-half"></i>
            </div>
        </div>
        <div class="stat-card stat-card--payment-chargeback">
            <div class="stat-card__content">
                <p class="stat-card__label">@lang('Payment Chargeback')</p>
                <h3 class="stat-card__value">{{ showAmount($payment['total_refunded'], currencyFormat:false) }}</h3>
                <span class="stat-card__delta stat-card__delta--negative">@lang('Chargebacks this month')</span>
            </div>
            <div class="stat-card__icon">
                <i class="las la-undo-alt"></i>
            </div>
        </div>
    </div>

    <div class="dashboard-chart-grid">
        <div class="dashboard-panel dashboard-panel--chart">
            <div class="dashboard-panel__header">
                <h6 class="payment-statistics payment-overview-title mb-0">@lang('Payment Overview')</h6>
            </div>
            <div class="payment-overview-canvas"></div>
            <div class="dashboard-chart__legend">
                <span><span class="legend-dot legend-dot--success"></span>@lang('Succeed Payment')</span>
                <span><span class="legend-dot legend-dot--danger"></span>@lang('Payment Chargeback')</span>
            </div>
        </div>
        <div class="dashboard-panel dashboard-panel--chart">
            <div class="dashboard-panel__header">
                <h6 class="mb-0">@lang('Weekly Withdrawals')</h6>
            </div>
            <div class="withdraw-weekly-canvas"></div>
        </div>
    </div>

</div>



