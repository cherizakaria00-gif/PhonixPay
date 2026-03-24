@extends('admin.layouts.app')

@section('panel')
<form method="POST" action="{{ route('admin.virtual.cards.settings.update') }}">
    @csrf
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('Virtual Card Api')</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">@lang('Name*')</label>
                    <input type="text" name="provider" class="form-control" value="{{ $settings->provider ?? 'Strowallet Api' }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('Public Key*')</label>
                    <input type="text" name="public_key" class="form-control" placeholder="@lang('Keep empty to keep existing key')">
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('Secret Key*')</label>
                    <input type="text" name="secret_key" class="form-control" placeholder="@lang('Keep empty to keep existing key')">
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('Base URL*')</label>
                    <input type="text" name="base_url" class="form-control" value="{{ $settings->base_url ?? '' }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">@lang('Mode')</label>
                    <div class="vc-mode-wrap">
                        <label class="vc-mode {{ ($settings->mode ?? 'sandbox') === 'live' ? 'is-active' : '' }}">
                            <input type="radio" name="mode" value="live" @checked(($settings->mode ?? 'sandbox') === 'live')>
                            <span>@lang('Live')</span>
                        </label>
                        <label class="vc-mode {{ ($settings->mode ?? 'sandbox') === 'sandbox' ? 'is-active' : '' }}">
                            <input type="radio" name="mode" value="sandbox" @checked(($settings->mode ?? 'sandbox') === 'sandbox')>
                            <span>@lang('Sandbox')</span>
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">@lang('Webhook URL')</label>
                    <input type="text" name="webhook_url" class="form-control" value="{{ $settings->webhook_url ?? route('webhooks.strowallet.card') }}">
                    <small class="text-muted">@lang('Default webhook'): {{ route('webhooks.strowallet.card') }}</small>
                </div>

                <div class="col-12">
                    <label class="form-label">@lang('Card limit admin')</label>
                    <input type="number" name="max_cards_per_merchant" class="form-control" min="1" value="{{ $settings->max_cards_per_merchant ?? 3 }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">@lang('Card Details*')</label>
                    <textarea name="card_details_text" rows="4" class="form-control">{{ $settings->card_details_text ?? '' }}</textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('Enabled')</label>
                    <select name="enabled" class="form-control">
                        <option value="1" @selected((bool)($settings->enabled ?? false))>@lang('Yes')</option>
                        <option value="0" @selected(!(bool)($settings->enabled ?? false))>@lang('No')</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('Default Currency')</label>
                    <input type="text" name="default_currency" class="form-control" value="{{ $settings->default_currency ?? 'USD' }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">@lang('Webhook Secret')</label>
                    <input type="text" name="webhook_secret" class="form-control" placeholder="@lang('Keep empty to keep existing key')">
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn--primary w-100" type="submit">@lang('Update')</button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('Virtual Card Charges')</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card border">
                        <div class="card-header"><h6 class="mb-0">@lang('Charges')</h6></div>
                        <div class="card-body row g-2">
                            <div class="col-12">
                                <label class="form-label">@lang('Fixed Charge*')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="fixed_charge" class="form-control" value="{{ (float)($settings->fixed_charge ?? 0) }}">
                                    <span class="input-group-text">{{ $settings->default_currency ?? 'USD' }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">@lang('Percent Charge*')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="percent_charge" class="form-control" value="{{ (float)($settings->percent_charge ?? 0) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border">
                        <div class="card-header"><h6 class="mb-0">@lang('Range')</h6></div>
                        <div class="card-body row g-2">
                            <div class="col-12">
                                <label class="form-label">@lang('Minimum Amount')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="min_amount" class="form-control" value="{{ (float)($settings->min_amount ?? 0) }}">
                                    <span class="input-group-text">{{ $settings->default_currency ?? 'USD' }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">@lang('Maximum Amount')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="max_amount" class="form-control" value="{{ (float)($settings->max_amount ?? 0) }}">
                                    <span class="input-group-text">{{ $settings->default_currency ?? 'USD' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border">
                        <div class="card-header"><h6 class="mb-0">@lang('Limit')</h6></div>
                        <div class="card-body row g-2">
                            <div class="col-12">
                                <label class="form-label">@lang('Daily Limit*')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="daily_limit" class="form-control" value="{{ (float)($settings->daily_limit ?? 0) }}">
                                    <span class="input-group-text">{{ $settings->default_currency ?? 'USD' }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">@lang('Monthly Limit*')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="monthly_limit" class="form-control" value="{{ (float)($settings->monthly_limit ?? 0) }}">
                                    <span class="input-group-text">{{ $settings->default_currency ?? 'USD' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn--primary w-100" type="submit">@lang('Update')</button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('Card Reload Charges')</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card border">
                        <div class="card-header"><h6 class="mb-0">@lang('Charges')</h6></div>
                        <div class="card-body row g-2">
                            <div class="col-12">
                                <label class="form-label">@lang('Fixed Charge*')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="reload_fixed_charge" class="form-control" value="{{ (float)($settings->reload_fixed_charge ?? 0) }}">
                                    <span class="input-group-text">{{ $settings->default_currency ?? 'USD' }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">@lang('Percent Charge*')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="reload_percent_charge" class="form-control" value="{{ (float)($settings->reload_percent_charge ?? 0) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border">
                        <div class="card-header"><h6 class="mb-0">@lang('Range')</h6></div>
                        <div class="card-body row g-2">
                            <div class="col-12">
                                <label class="form-label">@lang('Minimum Amount')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="reload_min_amount" class="form-control" value="{{ (float)($settings->reload_min_amount ?? 0) }}">
                                    <span class="input-group-text">{{ $settings->default_currency ?? 'USD' }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">@lang('Maximum Amount')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="reload_max_amount" class="form-control" value="{{ (float)($settings->reload_max_amount ?? 0) }}">
                                    <span class="input-group-text">{{ $settings->default_currency ?? 'USD' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border">
                        <div class="card-header"><h6 class="mb-0">@lang('Limit')</h6></div>
                        <div class="card-body row g-2">
                            <div class="col-12">
                                <label class="form-label">@lang('Daily Limit*')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="reload_daily_limit" class="form-control" value="{{ (float)($settings->reload_daily_limit ?? 0) }}">
                                    <span class="input-group-text">{{ $settings->default_currency ?? 'USD' }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">@lang('Monthly Limit*')</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="reload_monthly_limit" class="form-control" value="{{ (float)($settings->reload_monthly_limit ?? 0) }}">
                                    <span class="input-group-text">{{ $settings->default_currency ?? 'USD' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn--primary w-100" type="submit">@lang('Update')</button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">@lang('Advanced Endpoints')</h5></div>
        <div class="card-body row g-2">
            <div class="col-md-6"><input type="text" name="endpoint_create_customer" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'create_customer', '') }}" placeholder="create_customer"></div>
            <div class="col-md-6"><input type="text" name="endpoint_get_customer" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'get_customer', '') }}" placeholder="get_customer"></div>
            <div class="col-md-6"><input type="text" name="endpoint_update_customer" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'update_customer', '') }}" placeholder="update_customer"></div>
            <div class="col-md-6"><input type="text" name="endpoint_create_card" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'create_card', '') }}" placeholder="create_card"></div>
            <div class="col-md-6"><input type="text" name="endpoint_fund_card" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'fund_card', '') }}" placeholder="fund_card"></div>
            <div class="col-md-6"><input type="text" name="endpoint_card_details" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'card_details', '') }}" placeholder="card_details"></div>
            <div class="col-md-6"><input type="text" name="endpoint_card_transactions" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'card_transactions', '') }}" placeholder="card_transactions"></div>
            <div class="col-md-6"><input type="text" name="endpoint_full_card_history" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'full_card_history', '') }}" placeholder="full_card_history"></div>
            <div class="col-md-6"><input type="text" name="endpoint_freeze_unfreeze" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'freeze_unfreeze', '') }}" placeholder="freeze_unfreeze"></div>
            <div class="col-md-6"><input type="text" name="endpoint_withdraw_from_card" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'withdraw_from_card', '') }}" placeholder="withdraw_from_card"></div>
            <div class="col-md-6"><input type="text" name="endpoint_card_withdraw_status" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'card_withdraw_status', '') }}" placeholder="card_withdraw_status"></div>
            <div class="col-md-6"><input type="text" name="endpoint_upgrade_card_limit" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'upgrade_card_limit', '') }}" placeholder="upgrade_card_limit"></div>
            <div class="col-md-6"><input type="text" name="endpoint_mastercard_details" class="form-control" value="{{ data_get($resolvedEndpoints ?? [], 'mastercard_details', '') }}" placeholder="mastercard_details"></div>
        </div>
    </div>
</form>

<div class="card mb-3">
    <div class="card-header"><h5 class="mb-0">@lang('Admin Control')</h5></div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4">
                <form action="{{ route('admin.virtual.cards.sync.all') }}" method="POST">@csrf<button class="btn btn-outline--warning w-100">@lang('Sync All Cards')</button></form>
            </div>
            <div class="col-md-8">
                <form action="{{ route('admin.virtual.cards.merchant.create', 0) }}" method="POST" id="createMerchantCardForm" class="row g-2">
                    @csrf
                    <div class="col-md-3">
                        <select class="form-control" id="merchantIdSelect" required>
                            <option value="">@lang('Select merchant')</option>
                            @foreach($merchants as $merchant)
                                <option value="{{ $merchant->id }}">{{ $merchant->firstname }} {{ $merchant->lastname }} ({{ '@' . $merchant->username }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><input type="text" name="currency" class="form-control" value="{{ data_get($settings, 'default_currency', 'USD') }}"></div>
                    <div class="col-md-2"><input type="text" name="name_on_card" class="form-control" placeholder="Name On Card"></div>
                    <div class="col-md-2"><input type="text" name="card_label" class="form-control" placeholder="Card Name"></div>
                    <div class="col-md-1"><input type="color" name="card_color" class="form-control form-control-color p-1" value="#4f46e5"></div>
                    <div class="col-md-2"><button class="btn btn--success w-100" type="submit">@lang('Create')</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table--light style--two">
                <thead>
                <tr>
                    <th>@lang('Card')</th>
                    <th>@lang('Merchant')</th>
                    <th>@lang('Balance')</th>
                    <th>@lang('Status')</th>
                    <th>@lang('Actions')</th>
                </tr>
                </thead>
                <tbody>
                @forelse($cards as $card)
                    <tr>
                        @php
                            $cardLabel = data_get($card->meta, 'label') ?: ('Card ' . ($card->last4 ?: '0000'));
                            $cardColor = data_get($card->meta, 'color') ?: '#4f46e5';
                        @endphp
                        <td>
                            <strong>{{ $cardLabel }}</strong>
                            <span class="vc-color-dot ms-1" style="background: {{ $cardColor }};"></span>
                            <br>
                            <small class="text-muted">{{ $card->masked_pan ?: ('**** ' . ($card->last4 ?: '')) }}</small><br>
                            <small class="text-muted">{{ $card->provider_card_id }}</small>
                        </td>
                        <td><strong>{{ $card->user->fullname ?? 'N/A' }}</strong><br><small class="text-muted">{{ '@' . ($card->user->username ?? 'n/a') }}</small></td>
                        <td>{{ showAmount($card->balance) }} {{ $card->currency }}</td>
                        <td><span class="badge badge--info">{{ ucfirst($card->status) }}</span></td>
                        <td><div class="button--group"><a href="{{ route('admin.virtual.cards.show', $card->id) }}" class="btn btn-sm btn-outline--primary">@lang('Details')</a><form action="{{ route('admin.virtual.cards.sync', $card->id) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline--warning">@lang('Sync')</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="100%" class="text-center text-muted">{{ __($emptyMessage) }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($cards->hasPages())<div class="card-footer py-4">{{ paginateLinks($cards) }}</div>@endif
</div>
@endsection

@push('style')
<style>
.vc-mode-wrap{display:flex;border-radius:8px;overflow:hidden;border:1px solid #ececf3}
.vc-mode{flex:1;margin:0;cursor:pointer}
.vc-mode input{display:none}
.vc-mode span{display:block;padding:10px 12px;text-align:center;background:#f5f6fb}
.vc-mode.is-active span{background:#ea5151;color:#fff;font-weight:600}
.vc-color-dot{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:middle;border:1px solid rgba(0,0,0,.08)}
</style>
@endpush

@push('script')
<script>
(function () {
    const form = document.getElementById('createMerchantCardForm');
    const select = document.getElementById('merchantIdSelect');
    if (!form || !select) return;

    form.addEventListener('submit', function (event) {
        const merchantId = select.value;
        if (!merchantId) {
            event.preventDefault();
            return;
        }
        form.action = '{{ url('admin/virtual-cards/merchant') }}/' + merchantId + '/create';
    });

    const modes = Array.from(document.querySelectorAll('.vc-mode'));
    modes.forEach(function(item){
        item.addEventListener('click', function(){
            modes.forEach(function(x){x.classList.remove('is-active');});
            item.classList.add('is-active');
        });
    });
})();
</script>
@endpush
