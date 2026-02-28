@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Current Plan Summary')</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>@lang('Merchant')</span><strong>{{ $merchant->username }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>@lang('Plan')</span><strong>{{ $merchant->plan->name ?? 'Starter' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>@lang('Status')</span><strong class="text-capitalize">{{ $merchant->plan_status ?? 'active' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>@lang('Used this month')</span><strong>{{ $usage['used'] }} / {{ $usage['unlimited'] ? __('Unlimited') : $usage['limit'] }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>@lang('Effective Fees')</span><strong>{{ number_format($effectivePlan['fee_percent'], 2) }}% + ${{ number_format($effectivePlan['fee_fixed'], 2) }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>@lang('Payout')</span><strong>{{ str_replace('_', ' ', $effectivePlan['payout_frequency']) }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>@lang('Last Payout')</span><strong>{{ $lastPayout ? showDateTime($lastPayout->scheduled_for) : '-' }}</strong></li>
                    </ul>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><h5 class="mb-0">@lang('Assign Plan')</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.plans.merchants.assign', $merchant->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label>@lang('Plan')</label>
                            <select class="form-control" name="plan_id" required>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ (int) $merchant->plan_id === (int) $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn--primary w-100">@lang('Assign Immediately')</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">@lang('Custom Overrides')</h5></div>
                <div class="card-body">
                    @php $overrides = $merchant->plan_custom_overrides ?? []; @endphp
                    <form action="{{ route('admin.plans.merchants.overrides', $merchant->id) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">@lang('Fee %')</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" name="fee_percent" value="{{ $overrides['fee_percent'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('Fixed Fee')</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="fee_fixed" value="{{ $overrides['fee_fixed'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('Tx Limit')</label>
                                @php $txLimitValue = ($overrides['tx_limit'] ?? '') === 'unlimited' ? 0 : ($overrides['tx_limit'] ?? ''); @endphp
                                <input type="number" min="0" class="form-control" name="tx_limit" value="{{ $txLimitValue }}" placeholder="@lang('0 = unlimited')">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">@lang('Payout Frequency')</label>
                                @php $freq = $overrides['payout_frequency'] ?? ''; @endphp
                                <select class="form-control" name="payout_frequency">
                                    <option value="">@lang('Use plan default')</option>
                                    <option value="weekly_7d" {{ $freq === 'weekly_7d' ? 'selected' : '' }}>weekly_7d</option>
                                    <option value="twice_weekly" {{ $freq === 'twice_weekly' ? 'selected' : '' }}>twice_weekly</option>
                                    <option value="every_2_days" {{ $freq === 'every_2_days' ? 'selected' : '' }}>every_2_days</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">@lang('Payment Links')</label>
                                @php $paymentLinksOverride = $overrides['features']['payment_links'] ?? null; @endphp
                                <select class="form-control" name="payment_links_override">
                                    <option value="default" {{ $paymentLinksOverride === null ? 'selected' : '' }}>@lang('Use plan default')</option>
                                    <option value="enabled" {{ $paymentLinksOverride === true ? 'selected' : '' }}>@lang('Force enabled')</option>
                                    <option value="disabled" {{ $paymentLinksOverride === false ? 'selected' : '' }}>@lang('Force disabled')</option>
                                </select>
                            </div>

                            @php
                                $overrideSupport = $overrides['support_channels'] ?? [];
                                $overrideNotify = $overrides['notification_channels'] ?? [];
                            @endphp

                            <div class="col-md-6">
                                <label class="form-label d-block">@lang('Support Channels')</label>
                                <label class="me-3"><input type="checkbox" name="support_channels[]" value="email" {{ in_array('email', $overrideSupport ?? []) ? 'checked' : '' }}> Email</label>
                                <label><input type="checkbox" name="support_channels[]" value="whatsapp" {{ in_array('whatsapp', $overrideSupport ?? []) ? 'checked' : '' }}> WhatsApp</label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">@lang('Notification Channels')</label>
                                <label class="me-3"><input type="checkbox" name="notification_channels[]" value="push" {{ in_array('push', $overrideNotify ?? []) ? 'checked' : '' }}> Push</label>
                                <label class="me-3"><input type="checkbox" name="notification_channels[]" value="sms" {{ in_array('sms', $overrideNotify ?? []) ? 'checked' : '' }}> SMS</label>
                                <label><input type="checkbox" name="notification_channels[]" value="email" {{ in_array('email', $overrideNotify ?? []) ? 'checked' : '' }}> Email</label>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn--primary w-100">@lang('Save Overrides')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
