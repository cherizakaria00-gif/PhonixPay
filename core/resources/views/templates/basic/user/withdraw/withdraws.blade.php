@extends($activeTemplate . 'layouts.master')

@php
    $request = request();
    $payoutAvailable = $user->balance * 0.7;
    $payoutAvailable = $payoutAvailable < 0 ? 0 : $payoutAvailable;
@endphp 

@section('content')
<div class="row justify-content-center gy-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="withdraw-stat">
                            <div class="withdraw-stat__label">@lang('Your current balance')</div>
                            <div class="withdraw-stat__value text--success">{{ showAmount($user->balance) }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="withdraw-stat">
                            <div class="withdraw-stat__label">@lang('Available for payout')</div>
                            <div class="withdraw-stat__value text--success">{{ showAmount($payoutAvailable) }}</div>
                        </div>
                    </div>
                </div>

                @if(@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)
                    <div class="withdraw-actions">
                        <div class="withdraw-actions__meta">
                            @if($hasPendingWithdraw)
                                <div class="text-muted">@lang('Next payout') :
                                    <span class="text--primary">@lang('Pending approval')</span>
                                </div>
                            @else
                                <div class="text-muted">@lang('Next payout date') :
                                    <span class="text--primary">{{ showDateTime($nextPayoutDate, 'd M') }}</span>
                                </div>
                            @endif
                            <small class="text-muted d-block">
                                @lang('Min') {{ showAmount(@$user->withdrawSetting->withdrawMethod->min_limit) }} /
                                @lang('Max') {{ showAmount(@$user->withdrawSetting->withdrawMethod->max_limit) }}
                            </small>
                        </div>
                        <button
                            type="button"
                            class="btn btn--primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#withdrawRequestModal"
                            @disabled($hasPendingWithdraw || !$canRequestPayout)
                        >
                            {{ $hasPendingWithdraw ? __('Payout Pending') : __('Withdraw') }}
                        </button>
                    </div>
                @else
                    <p class="text-muted mt-3 mb-0">
                        @lang('Please, setup the payout method for withdrawals.')
                    </p>
                @endif
            </div>
        </div>
        </div>

        <div class="col-12 mt-5">
            <div class="card custom--card border-0">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">@lang('Payout History')</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($withdraws as $withdraw)
                                    <tr>
                                        <td>{{ showDateTime(@$withdraw->created_at, 'd M Y') }}</td>
                                        <td><strong>{{ showAmount(@$withdraw->amount) }}</strong></td>
                                        <td>@php echo $withdraw->statusBadge @endphp</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __('Data not found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div><!-- custom--card end -->
        </div>

        <div class="col-12">
            <div class="mt-3">
                @if ($withdraws->hasPages())
                    {{ paginatelinks($withdraws) }}
                @endif
            </div>
        </div>
    </div>
</div>

@php
    $activeMethod = @$user->withdrawSetting->withdrawMethod;
    $savedUserData = [];
    foreach (@$user->withdrawSetting->user_data ?? [] as $data) {
        if (isset($data->type) && $data->type === 'file') {
            continue;
        }
        if (!isset($data->name)) {
            continue;
        }
        if (isset($data->value) && $data->value !== '' && $data->value !== null) {
            $savedUserData[] = $data;
        }
    }
    $withdrawMeta = [
        'name' => $activeMethod?->name,
        'currency' => $activeMethod?->currency,
        'min_limit' => $activeMethod?->min_limit ? (float) $activeMethod->min_limit : null,
        'max_limit' => $activeMethod?->max_limit ? (float) $activeMethod->max_limit : null,
        'available_balance' => $payoutAvailable ? (float) $payoutAvailable : 0,
        'fixed_charge' => $activeMethod?->fixed_charge ? (float) $activeMethod->fixed_charge : 0,
        'percent_charge' => $activeMethod?->percent_charge ? (float) $activeMethod->percent_charge : 0,
        'rate' => $activeMethod?->rate ? (float) $activeMethod->rate : 1,
        'next_date' => $nextPayoutDate ? showDateTime($nextPayoutDate, 'd M Y') : null,
    ];
@endphp

<div class="modal fade" id="withdrawRequestModal" tabindex="-1" aria-labelledby="withdrawRequestLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content withdraw-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="withdrawRequestLabel">@lang('Withdraw')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('Close')"></button>
            </div>
            <form action="{{ route('user.withdraw.request') }}" method="post" class="withdraw-request-form">
                @csrf
                <div class="modal-body">
                    <div class="withdraw-modal__group">
                        <label class="form-label">@lang('Amount to payout')</label>
                        @php
                            $methodMax = @$user->withdrawSetting->withdrawMethod->max_limit;
                            $inputMax = $methodMax ? min($methodMax, $payoutAvailable) : $payoutAvailable;
                        @endphp
                        <div class="withdraw-input">
                            <span class="withdraw-input__prefix">{{ gs('cur_sym') }}</span>
                            <input
                                type="number"
                                step="any"
                                name="amount"
                                class="form-control withdraw-input__field"
                                placeholder="@lang('Enter amount')"
                                value="{{ old('amount', getAmount(@$user->withdrawSetting->amount)) }}"
                                min="{{ getAmount(@$user->withdrawSetting->withdrawMethod->min_limit) }}"
                                max="{{ getAmount($inputMax) }}"
                                @disabled($hasPendingWithdraw || !$canRequestPayout)
                            >
                            <button type="button" class="btn btn-link withdraw-input__max">@lang('Max')</button>
                        </div>
                        <small class="text-danger withdraw-min-note">
                            @lang('Minimum payout amount is') {{ showAmount(@$user->withdrawSetting->withdrawMethod->min_limit) }}
                        </small>
                    </div>

                    <div class="withdraw-modal__group">
                        <label class="form-label">@lang('Payout method')</label>
                        <div class="withdraw-method-line">
                            <div class="withdraw-method-line__name">
                                <span class="method-icon"><i class="las la-university"></i></span>
                                <span class="method-text">{{ __(@$activeMethod->name ?? __('Not set')) }}</span>
                            </div>
                            <div class="withdraw-method-line__currency">
                                {{ __(@$activeMethod->currency ?? '') }}
                            </div>
                        </div>
                    </div>

                    <div class="withdraw-modal__group">
                        <label class="form-label">@lang('Send payout to')</label>
                        @if(count($savedUserData))
                            <div class="withdraw-saved-account">
                                @foreach($savedUserData as $item)
                                    @php
                                        $displayValue = $item->value;
                                        if (is_array($displayValue)) {
                                            $displayValue = implode(', ', $displayValue);
                                        }
                                    @endphp
                                    <div class="withdraw-saved-account__row">
                                        <span class="withdraw-saved-account__label">{{ __($item->name) }}</span>
                                        <span class="withdraw-saved-account__value">{{ $displayValue }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted small mb-2">@lang('No payout account saved yet.')</div>
                        @endif
                        <button
                            type="button"
                            class="btn btn--light w-100 withdraw-add-method"
                            data-bs-toggle="modal"
                            data-bs-target="#withdrawMethodModal"
                            data-bs-dismiss="modal"
                        >
                            {{ count($savedUserData) ? __('Change account') : __('Add new account') }}
                        </button>
                    </div>

                    <div class="withdraw-modal__summary">
                        <div>
                            <span>@lang('Fees')</span>
                            <strong class="withdraw-fee">-</strong>
                        </div>
                        <div>
                            <span>@lang('Exchange rate')</span>
                            <strong class="withdraw-rate">-</strong>
                        </div>
                        <div>
                            <span>@lang('Estimated delivery')</span>
                            <strong class="withdraw-delivery">{{ $nextPayoutDate ? showDateTime($nextPayoutDate, 'd M Y') : __('Pending approval') }}</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn--primary w-100" @disabled($hasPendingWithdraw || !$canRequestPayout)>
                        {{ $hasPendingWithdraw ? __('Payout Pending') : __('Request Payout') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="withdrawMethodModal" tabindex="-1" aria-labelledby="withdrawMethodLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content withdraw-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="withdrawMethodLabel">@lang('Add Withdraw Method')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('Close')"></button>
            </div>
            <form action="{{ route('user.withdraw.method.submit') }}" method="post" enctype="multipart/form-data" class="withdraw-method-form">
                @csrf
                <div class="modal-body">
                    <div class="withdraw-modal__group">
                        <label class="form-label">@lang('Payout method')</label>
                        <select class="form-select form--control withdraw-method-select" name="method_code" required>
                            <option value="">@lang('Select One')</option>
                            @foreach($withdrawMethod as $data)
                                <option
                                    value="{{ $data->id }}"
                                    data-resource="{{$data}}"
                                    data-form='<x-withdraw-form identifier="id" identifierValue="{{ $data->form_id }}"/>'
                                    {{ $data->id == old('method_code') ? 'selected' : null }}
                                >
                                    {{__($data->name)}}
                                    ({{ showAmount($data->min_limit, currencyFormat:false) }} - {{ showAmount($data->max_limit, currencyFormat:false) }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-2">
                            <i class="las la-info-circle"></i>
                            @lang('Withdraw Time'):
                            <span class="schedule_type capitalize"></span>
                            <span class="schedule"></span>
                        </small>
                        <small class="text-muted rate-element d-none"></small>
                    </div>

                    <div class="withdraw-modal__group">
                        <div class="withdraw_form"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn--primary w-100">@lang('Save Method')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('style')
<style>
    .border-line-area.style-two{
        text-align: center;
        position: relative;
        z-index: 1;
    }
    .border-line-area.style-two .border-line-title {
        display: inline-block;
        margin-bottom: 0 !important;
        background: #fff;
        padding: 10px;
        padding-bottom: 5px;
    }
    .border-line-title-wrapper {
        position: relative;
    }
    .border-line-title-wrapper::before {
        position: absolute;
        content: "";
        width: 100%;
        height: 0.1px;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        background-color: #e5e5e5;
        z-index: -1;
    }

    .withdraw-detail__balance {
        font-size: 36px; 
    }
    .withdraw-detail-border {
        border-right: 1px solid #dee2e6; 
    }
    @media (max-width: 1399px) {
        .withdraw-detail__desc {
            font-weight: 500;
            font-size: 17px
        }
        .text-muted.title {
            font-size: 20px;
        }
        .withdraw-detail__balance {
            font-size: 32px; 
        }
    }
    @media (max-width: 767px) {
        .withdraw-detail-border {
            border-right: 0; 
            border-bottom: 1px solid #dee2e6!important; 
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .withdraw-detail__desc {
            font-size: 16px
        }
    }
    .withdraw-method-image img{
        max-width: 80px;
        max-height: 80px;
    }
    .withdraw-stat {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 18px 20px;
        height: 100%;
    }
    .withdraw-stat__label {
        color: #6b7280;
        font-weight: 600;
        margin-bottom: 6px;
    }
    .withdraw-stat__value {
        font-size: 30px;
        font-weight: 700;
    }
    .withdraw-actions {
        margin-top: 16px;
        padding-top: 12px;
        border-top: 1px solid #eef2f7;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .withdraw-actions__meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .payout-request__group {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
    }
    .payout-request__amount {
        flex: 1 1 220px;
    }
    .payout-request__amount .form--control {
        height: 44px;
    }
    .payout-request__action .btn {
        height: 44px;
        padding-inline: 20px;
    }
    .payout-request__hint {
        display: inline-block;
        margin-top: 8px;
    }
    .manage-payouts .dropdown-menu {
        min-width: 210px;
    }
    .withdraw-modal {
        border-radius: 18px;
        border: 0;
        box-shadow: 0 30px 70px rgba(0, 0, 0, 0.2);
    }
    .withdraw-modal .modal-header {
        border-bottom: 1px solid #e9ecef;
        padding: 18px 22px;
    }
    .withdraw-modal .modal-body {
        padding: 20px 22px 10px;
    }
    .withdraw-modal .modal-footer {
        border-top: 1px solid #e9ecef;
        padding: 16px 22px 20px;
    }
    .withdraw-modal__group {
        margin-bottom: 16px;
    }
    .withdraw-input {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 8px 12px;
        background: #f8fafc;
    }
    .withdraw-input__prefix {
        font-weight: 600;
        color: #6b7280;
        min-width: 18px;
    }
    .withdraw-input__field {
        border: 0;
        background: transparent;
        box-shadow: none !important;
        padding: 0;
        height: 36px;
    }
    .withdraw-input__max {
        font-weight: 600;
        color: #3b82f6;
        text-decoration: none;
        padding: 0 6px;
    }
    .withdraw-min-note {
        display: block;
        margin-top: 6px;
        font-size: 12px;
    }
    .withdraw-method-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 10px 14px;
        background: #fff;
    }
    .withdraw-method-line__name {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: #111827;
    }
    .withdraw-method-line__currency {
        font-weight: 600;
        color: #6b7280;
    }
    .method-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: #eef2ff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #4f46e5;
        font-size: 16px;
    }
    .withdraw-add-method {
        background: #eef2ff;
        color: #1d4ed8;
        font-weight: 600;
        border-radius: 12px;
        border: 0;
    }
    .withdraw-modal__summary {
        background: #f8fafc;
        border-radius: 12px;
        padding: 12px 14px;
        display: grid;
        gap: 8px;
        font-size: 14px;
        color: #6b7280;
    }
    .withdraw-modal__summary strong {
        color: #111827;
        font-weight: 600;
    }
    .withdraw-saved-account {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 10px 14px;
        background: #f8fafc;
        display: grid;
        gap: 6px;
        margin-bottom: 12px;
        font-size: 14px;
    }
    .withdraw-saved-account__row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
    }
    .withdraw-saved-account__label {
        color: #6b7280;
        font-weight: 500;
    }
    .withdraw-saved-account__value {
        color: #111827;
        font-weight: 600;
        text-align: right;
        word-break: break-word;
    }


</style>
@endpush

@push('script')
<script>
    (function () {
        const meta = @json($withdrawMeta);
        const amountInput = document.querySelector('#withdrawRequestModal input[name="amount"]');
        const maxBtn = document.querySelector('.withdraw-input__max');
        const feeEl = document.querySelector('.withdraw-fee');
        const rateEl = document.querySelector('.withdraw-rate');
        const deliveryEl = document.querySelector('.withdraw-delivery');
        const minNote = document.querySelector('.withdraw-min-note');

        if (!amountInput || !meta) return;

        const updatePreview = () => {
            const amount = parseFloat(amountInput.value || 0);
            const fixedCharge = parseFloat(meta.fixed_charge || 0);
            const percentCharge = parseFloat(meta.percent_charge || 0);
            const rate = parseFloat(meta.rate || 1);

            const charge = amount > 0 ? (fixedCharge + (amount * percentCharge / 100)) : 0;
            feeEl.textContent = charge ? `~ ${charge.toFixed(2)} {{ gs('cur_text') }}` : '-';

            if (meta.currency && meta.currency !== '{{ gs('cur_text') }}') {
                rateEl.textContent = `1 {{ gs('cur_text') }} = ${rate} ${meta.currency}`;
            } else {
                rateEl.textContent = `1 {{ gs('cur_text') }} = 1 {{ gs('cur_text') }}`;
            }

            if (meta.next_date) {
                deliveryEl.textContent = meta.next_date;
            }

            if (meta.min_limit) {
                minNote.textContent = `{{ __('Minimum payout amount is') }} ${parseFloat(meta.min_limit).toFixed(2)} {{ gs('cur_text') }}`;
            }
        };

        amountInput.addEventListener('input', updatePreview);
        if (maxBtn) {
            maxBtn.addEventListener('click', () => {
                const available = parseFloat(meta.available_balance || 0);
                const max = meta.max_limit ? parseFloat(meta.max_limit) : available;
                const allowed = available > 0 ? Math.min(available, max) : 0;
                amountInput.value = allowed > 0 ? allowed.toFixed(2) : '';
                updatePreview();
            });
        }

        updatePreview();
    })();

    (function ($) {
        const methodModal = $('#withdrawMethodModal');
        if (!methodModal.length) return;

        const select = methodModal.find('.withdraw-method-select');
        const amountInput = methodModal.find('input[name="amount"]');
        const rateElement = methodModal.find('.rate-element');
        const scheduleType = methodModal.find('.schedule_type');
        const schedule = methodModal.find('.schedule');
        const formContainer = methodModal.find('.withdraw_form');

        const resetFields = () => {
            rateElement.addClass('d-none').text('');
            scheduleType.text('');
            schedule.text('');
            formContainer.empty();
        };

        const updateMethod = () => {
            const selected = select.find('option:selected');
            const resource = selected.data('resource');
            const form = selected.data('form');

            if (!resource) {
                resetFields();
                return;
            }

            const rate = parseFloat(resource.rate || 1);

            if (resource.currency && resource.currency !== '{{ gs('cur_text') }}') {
                rateElement
                    .removeClass('d-none')
                    .html(`<small class="fst-italic"><i class="las la-info-circle"></i> <span>@lang('Conversion Rate'):</span> 1 {{ gs('cur_text') }} = <span class="rate">${rate}</span> <span class="base-currency">${resource.currency}</span></small>`);
            } else {
                rateElement.addClass('d-none').text('');
            }

            scheduleType.text(resource.schedule_type || '');
            if (resource.schedule_type === 'daily') {
                schedule.text('');
            } else {
                schedule.text(resource.showSchedule ? ` - ${resource.showSchedule}` : '');
            }

            formContainer.html(form || '');
            if (typeof defaultBehavior === 'function') {
                defaultBehavior();
            }
        };

        select.on('change', updateMethod);
        if (amountInput.length) {
            amountInput.on('input', updateMethod);
        }
        updateMethod();

        if (@json((bool) old('method_code'))) {
            methodModal.modal('show');
        }
    })(jQuery);
</script>
@endpush
