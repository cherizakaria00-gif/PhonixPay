@extends('admin.layouts.app')
@section('panel')
<div class="row mb-none-30">
    <div class="col-xl-4 col-md-5 mb-30">
        <div class="card b-radius--10 h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">@lang('License Details')</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>@lang('License Key')</span>
                        <span class="fw-bold text-break">{{ $license->license_key }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>@lang('Merchant')</span>
                        <a href="{{ route('admin.users.detail', $license->merchant_id) }}">
                            {{ $license->merchant->username ?? 'N/A' }}
                        </a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>@lang('Email')</span>
                        <span class="fw-bold">{{ $license->email }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>@lang('Domain')</span>
                        <span class="fw-bold">{{ $license->normalized_domain }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>@lang('Plugin')</span>
                        <span class="fw-bold">{{ $license->plugin_name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>@lang('Status')</span>
                        <span>@php echo $license->statusBadge; @endphp</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>@lang('Activations')</span>
                        <span class="fw-bold">{{ (int) $license->activations_count }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>@lang('Last Validated')</span>
                        <span class="fw-bold">
                            {{ $license->last_validated_at ? showDateTime($license->last_validated_at) : __('Never') }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>@lang('Last IP')</span>
                        <span class="fw-bold">{{ $license->last_used_ip ?? __('N/A') }}</span>
                    </li>
                </ul>

                @if($license->status !== \App\Models\PluginLicense::STATUS_REVOKED)
                    <button class="btn btn-outline--danger btn-sm w-100 mt-3 confirmationBtn"
                            data-question="@lang('Revoke this plugin license?')"
                            data-action="{{ route('admin.plugin.licenses.revoke', $license->id) }}">
                        <i class="la la-ban"></i> @lang('Revoke License')
                    </button>
                @endif

                <button class="btn btn-outline--danger btn-sm w-100 mt-2 confirmationBtn"
                        data-question="@lang('Delete this license permanently?')"
                        data-action="{{ route('admin.plugin.licenses.delete', $license->id) }}">
                    <i class="la la-trash"></i> @lang('Delete License')
                </button>
            </div>
        </div>
    </div>

    <div class="col-xl-8 col-md-7 mb-30">
        <div class="card b-radius--10 mb-30">
            <div class="card-header">
                <h5 class="card-title mb-0">@lang('Update Domain')</h5>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('admin.plugin.licenses.update.domain', $license->id) }}" class="row g-2">
                    @csrf
                    <div class="col-md-9">
                        <label class="form-label">@lang('Allowed Domain')</label>
                        <input type="text" name="domain" class="form-control" value="{{ $license->domain }}" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn--primary w-100">@lang('Update URL')</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card b-radius--10">
            <div class="card-header">
                <h5 class="card-title mb-0">@lang('Validation History')</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('Action')</th>
                            <th>@lang('Result')</th>
                            <th>@lang('Message')</th>
                            <th>@lang('Domain')</th>
                            <th>@lang('IP')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($history as $row)
                            <tr>
                                <td>{{ showDateTime($row->created_at) }}</td>
                                <td>{{ ucfirst($row->action) }}</td>
                                <td>
                                    @if($row->result === 'success')
                                        <span class="badge badge--success">@lang('Success')</span>
                                    @else
                                        <span class="badge badge--danger">@lang('Failed')</span>
                                    @endif
                                </td>
                                <td>{{ $row->message ?: __('N/A') }}</td>
                                <td>{{ $row->normalized_domain ?: __('N/A') }}</td>
                                <td>{{ $row->ip ?: __('N/A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">@lang('No validation history found')</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($history->hasPages())
                <div class="card-footer py-4">
                    @php echo paginateLinks($history) @endphp
                </div>
            @endif
        </div>
    </div>
</div>
<x-confirmation-modal />
@endsection
