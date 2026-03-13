@extends($activeTemplate.'layouts.master')

@php
    $showHeaderBalance = true;
@endphp

@section('content')
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card custom--card h-100">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Plugin License')</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>@lang('Email')</label>
                        <input type="email" class="form--control" value="{{ auth()->user()->email }}" readonly>
                    </div>
                    <div class="form-group">
                        <label>@lang('Website URL / Domain')</label>
                        <input type="text" class="form--control" value="{{ auth()->user()->website_domain ?: auth()->user()->website_url }}" readonly>
                        <small class="text-muted d-block mt-1">
                            @lang('This domain is locked from profile setup. Contact admin if you need to change it.')
                        </small>
                    </div>
                    <div class="form-group mb-0">
                        <label>@lang('Current License Key')</label>
                        <input type="text" class="form--control" value="{{ $currentLicense?->license_key ?: __('Not generated yet') }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card custom--card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">@lang('License Keys')</h5>
                    <span class="text-muted">@lang('Total'): {{ $licenses->total() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>@lang('Key')</th>
                                <th>@lang('Domain')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Last Validation')</th>
                                <th>@lang('Action')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($licenses as $license)
                                <tr>
                                    <td>
                                        <div class="small fw-bold">{{ $license->license_key }}</div>
                                        <button type="button" class="btn btn-sm btn-outline--primary mt-1 copy-license-btn"
                                                data-license="{{ $license->license_key }}">
                                            @lang('Copy')
                                        </button>
                                    </td>
                                    <td>
                                        <div>{{ $license->normalized_domain }}</div>
                                        <small class="text-muted">{{ $license->email }}</small>
                                    </td>
                                    <td>@php echo $license->statusBadge; @endphp</td>
                                    <td>
                                        @if($license->last_validated_at)
                                            {{ diffForHumans($license->last_validated_at) }}
                                        @else
                                            <span class="text-muted">@lang('Never')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($license->status !== \App\Models\PluginLicense::STATUS_REVOKED)
                                            <form method="post" action="{{ route('user.plugin.licenses.revoke', $license->id) }}" class="mb-1">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--danger w-100"
                                                        onclick="return confirm('@lang('Are you sure you want to revoke this license?')')">
                                                    @lang('Revoke')
                                                </button>
                                            </form>
                                        @endif

                                        @if($license->status !== \App\Models\PluginLicense::STATUS_REVOKED)
                                            <form method="post" action="{{ route('user.plugin.licenses.regenerate', $license->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--warning w-100"
                                                        onclick="return confirm('@lang('Regenerate this license key? The current key will be revoked.')')">
                                                    @lang('Regenerate')
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%" class="text-center text-muted py-4">@lang('No plugin licenses found')</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($licenses->hasPages())
                    <div class="card-footer">
                        @php echo paginateLinks($licenses) @endphp
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            document.querySelectorAll('.copy-license-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const value = this.getAttribute('data-license') || '';
                    if (!value) {
                        return;
                    }

                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(value);
                    } else {
                        const temp = document.createElement('input');
                        temp.value = value;
                        document.body.appendChild(temp);
                        temp.select();
                        document.execCommand('copy');
                        temp.remove();
                    }
                });
            });
        })();
    </script>
@endpush
