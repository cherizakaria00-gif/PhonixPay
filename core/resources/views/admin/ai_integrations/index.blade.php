@extends('admin.layouts.app')
@section('panel')
<div class="row mb-3">
    <div class="col-md-12">
        <form class="card" method="get">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">@lang('Search')</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="merchant/email/domain/license">
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('Option')</label>
                    <select name="option" class="form-control">
                        <option value="">@lang('All')</option>
                        <option value="api_keys" @selected($option === 'api_keys')>api_keys</option>
                        <option value="payment_link" @selected($option === 'payment_link')>payment_link</option>
                        <option value="plugin_sdk" @selected($option === 'plugin_sdk')>plugin_sdk</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('Status')</label>
                    <select name="status" class="form-control">
                        <option value="">@lang('All')</option>
                        <option value="not_configured" @selected($status === 'not_configured')>not_configured</option>
                        <option value="draft" @selected($status === 'draft')>draft</option>
                        <option value="connected" @selected($status === 'connected')>connected</option>
                        <option value="needs_attention" @selected($status === 'needs_attention')>needs_attention</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn--primary w-100" type="submit">@lang('Filter')</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive table-responsive--sm">
                    <table class="table table--light style--two">
                        <thead>
                        <tr>
                            <th>@lang('Merchant')</th>
                            <th>@lang('Selected Option')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Payment Link')</th>
                            <th>@lang('API Key Ref')</th>
                            <th>@lang('Plugin/SDK')</th>
                            <th>@lang('Updated')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($integrations as $integration)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.users.detail', $integration->merchant_id) }}">{{ $integration->merchant->fullname ?? 'N/A' }}</a>
                                    <br>
                                    <small class="text-muted">{{ $integration->merchant->email ?? 'N/A' }}</small>
                                </td>
                                <td><span class="fw-bold">{{ $integration->selected_option ?: 'N/A' }}</span></td>
                                <td>@php echo $integration->statusBadge; @endphp</td>
                                <td>
                                    @if($integration->payment_link_id)
                                        #{{ $integration->payment_link_id }}
                                        <br>
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($integration->payment_link_url, 35) }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($integration->public_key_reference)
                                        <small class="text-muted">PUB:</small> {{ \Illuminate\Support\Str::limit($integration->public_key_reference, 20) }}
                                        <br>
                                        <small class="text-muted">SEC:</small> {{ $integration->secret_key_reference ? '********' : 'N/A' }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $integration->merchant_email ?: 'N/A' }}</div>
                                    <small class="text-muted">{{ $integration->normalized_domain ?: 'N/A' }}</small>
                                    @if($integration->license_key)
                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($integration->license_key, 22) }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ diffForHumans($integration->updated_at) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="text-center text-muted">@lang('No AI integrations found')</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($integrations->hasPages())
                <div class="card-footer py-4">
                    @php echo paginateLinks($integrations) @endphp
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
