@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ route('admin.stripe.accounts.update', $account->id) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Name')</label>
                                    <input type="text" class="form-control" name="name" value="{{ old('name', $account->name) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Publishable Key')</label>
                                    <input type="text" class="form-control" name="publishable_key" value="{{ old('publishable_key', $account->publishable_key) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Secret Key')</label>
                                    <input type="text" class="form-control" name="secret_key" value="{{ old('secret_key', $account->secret_key) }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('Range From')</label>
                                    <input type="number" step="any" class="form-control" name="min_amount" value="{{ old('min_amount', getAmount($account->min_amount)) }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('Range To')</label>
                                    <input type="number" step="any" class="form-control" name="max_amount" value="{{ old('max_amount', getAmount($account->max_amount)) }}">
                                    <small class="text--small">@lang('Use 0 for no limit. Leave both 0 to use round-robin')</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('Status')</label>
                                    <select name="is_active" class="form-control" required>
                                        <option value="1" @selected(old('is_active', $account->is_active) == 1)>@lang('Enabled')</option>
                                        <option value="0" @selected(old('is_active', $account->is_active) == 0)>@lang('Disabled')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Update')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-back route="{{ route('admin.stripe.accounts.index') }}" />
@endpush
