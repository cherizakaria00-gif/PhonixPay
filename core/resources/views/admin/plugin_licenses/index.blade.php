@extends('admin.layouts.app')
@section('panel')
<div class="row justify-content-center">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">@lang('Create License')</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.plugin.licenses.store') }}" method="post" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">@lang('Merchant (ID/username/email)')</label>
                        <input type="text" name="merchant" class="form-control" value="{{ old('merchant') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">@lang('Email')</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">@lang('Domain')</label>
                        <input type="text" name="domain" class="form-control" value="{{ old('domain') }}" placeholder="example.com" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">@lang('Plugin')</label>
                        <input type="text" name="plugin_name" class="form-control" value="{{ old('plugin_name', 'flujipay-woocommerce') }}">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn--primary w-100">@lang('Create')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                        <tr>
                            <th>@lang('License Key')</th>
                            <th>@lang('Merchant')</th>
                            <th>@lang('Email')</th>
                            <th>@lang('Domain')</th>
                            <th>@lang('Plugin')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Last Validation')</th>
                            <th>@lang('Action')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($licenses as $license)
                            <tr>
                                <td class="fw-bold">{{ $license->license_key }}</td>
                                <td>
                                    <a href="{{ route('admin.users.detail', $license->merchant_id) }}">
                                        {{ $license->merchant->fullname ?? 'N/A' }}
                                    </a>
                                    <br>
                                    <small class="text-muted">@{{ $license->merchant->username ?? 'N/A' }}</small>
                                </td>
                                <td>{{ $license->email }}</td>
                                <td>{{ $license->normalized_domain }}</td>
                                <td>{{ $license->plugin_name }}</td>
                                <td>@php echo $license->statusBadge; @endphp</td>
                                <td>
                                    @if($license->last_validated_at)
                                        {{ diffForHumans($license->last_validated_at) }}
                                    @else
                                        <span class="text-muted">@lang('Never')</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.plugin.licenses.show', $license->id) }}" class="btn btn-sm btn-outline--primary mb-1">
                                        <i class="la la-desktop"></i> @lang('Details')
                                    </a>
                                    @if($license->status !== \App\Models\PluginLicense::STATUS_REVOKED)
                                        <button class="btn btn-sm btn-outline--danger confirmationBtn"
                                                data-question="@lang('Revoke this plugin license?')"
                                                data-action="{{ route('admin.plugin.licenses.revoke', $license->id) }}">
                                            <i class="la la-ban"></i> @lang('Revoke')
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">{{ __('No plugin licenses found') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($licenses->hasPages())
                <div class="card-footer py-4">
                    @php echo paginateLinks($licenses) @endphp
                </div>
            @endif
        </div>
    </div>
</div>
<x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder="License key / merchant / email / domain" />
@endpush
