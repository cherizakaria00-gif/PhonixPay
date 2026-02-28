@extends('admin.layouts.app')

@section('panel')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __($pageTitle) }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ $plan->id ? route('admin.plans.update', $plan->id) : route('admin.plans.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">@lang('Slug')</label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $plan->slug) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('Name')</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('Price (cents/month)')</label>
                                <input type="number" min="0" name="price_monthly_cents" class="form-control" value="{{ old('price_monthly_cents', $plan->price_monthly_cents) }}" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">@lang('Currency')</label>
                                <input type="text" name="currency" class="form-control" value="{{ old('currency', $plan->currency ?? 'USD') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">@lang('Monthly Tx Limit')</label>
                                <input type="number" min="1" name="tx_limit_monthly" class="form-control" value="{{ old('tx_limit_monthly', $plan->tx_limit_monthly) }}" placeholder="@lang('Leave empty = unlimited')">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">@lang('Fee Percent')</label>
                                <input type="number" step="0.01" min="0" max="100" name="fee_percent" class="form-control" value="{{ old('fee_percent', $plan->fee_percent) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">@lang('Fixed Fee')</label>
                                <input type="number" step="0.01" min="0" name="fee_fixed" class="form-control" value="{{ old('fee_fixed', $plan->fee_fixed) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">@lang('Payout Frequency')</label>
                                <select name="payout_frequency" class="form-control" required>
                                    @php $freq = old('payout_frequency', $plan->payout_frequency); @endphp
                                    <option value="weekly_7d" {{ $freq == 'weekly_7d' ? 'selected' : '' }}>weekly_7d</option>
                                    <option value="twice_weekly" {{ $freq == 'twice_weekly' ? 'selected' : '' }}>twice_weekly</option>
                                    <option value="every_2_days" {{ $freq == 'every_2_days' ? 'selected' : '' }}>every_2_days</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('Payout Delay Days')</label>
                                <input type="number" min="0" name="payout_delay_days" class="form-control" value="{{ old('payout_delay_days', $plan->payout_delay_days) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('Sort Order')</label>
                                <input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $plan->sort_order ?? 0) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label d-block">@lang('Support Channels')</label>
                                @php $support = old('support_channels', $plan->support_channels ?? []); @endphp
                                <label class="me-3"><input type="checkbox" name="support_channels[]" value="email" {{ in_array('email', $support ?? []) ? 'checked' : '' }}> Email</label>
                                <label><input type="checkbox" name="support_channels[]" value="whatsapp" {{ in_array('whatsapp', $support ?? []) ? 'checked' : '' }}> WhatsApp</label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">@lang('Notification Channels')</label>
                                @php $notifyChannels = old('notification_channels', $plan->notification_channels ?? []); @endphp
                                <label class="me-3"><input type="checkbox" name="notification_channels[]" value="push" {{ in_array('push', $notifyChannels ?? []) ? 'checked' : '' }}> Push</label>
                                <label class="me-3"><input type="checkbox" name="notification_channels[]" value="sms" {{ in_array('sms', $notifyChannels ?? []) ? 'checked' : '' }}> SMS</label>
                                <label><input type="checkbox" name="notification_channels[]" value="email" {{ in_array('email', $notifyChannels ?? []) ? 'checked' : '' }}> Email</label>
                            </div>

                            @php $features = old('features', $plan->features ?? []); @endphp
                            <div class="col-md-6">
                                <label class="form-label d-block">@lang('Features')</label>
                                <label><input type="checkbox" name="payment_links" value="1" {{ ($features['payment_links'] ?? false) ? 'checked' : '' }}> @lang('Payment Links enabled')</label>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">@lang('Status')</label>
                                <label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}> @lang('Active')</label>
                            </div>
                            @if(!$plan->id)
                                <div class="col-md-3">
                                    <label class="form-label d-block">@lang('Default')</label>
                                    <label><input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}> @lang('Default plan')</label>
                                </div>
                            @endif

                            <div class="col-12">
                                <button type="submit" class="btn btn--primary w-100">@lang('Save Plan')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
