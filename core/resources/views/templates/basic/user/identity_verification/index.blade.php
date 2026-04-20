@extends($activeTemplate.'layouts.master')

@section('content')
<div class="row justify-content-center gy-4">
    <div class="col-12">
        <div class="card custom--card h-auto">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h5 class="mb-1">@lang('Identity Verification')</h5>
                        <p class="text-muted mb-0">@lang('Complete your identity check securely using our verification partner.')</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn--base" id="diditStartBtn" @disabled(!$isConfigured)>
                            <i class="las la-shield-alt"></i> @lang('Start Verification')
                        </button>
                        <a href="#diditVerificationFrame" class="btn btn-outline--base d-none" id="diditOpenInlineBtn">
                            <i class="las la-window-maximize"></i> @lang('Open Embedded View')
                        </a>
                    </div>
                </div>

                @if(!$isConfigured)
                    <div class="alert alert-warning mt-3 mb-0">
                        @lang('Identity verification is not configured yet. Please contact admin.')
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card custom--card h-auto">
            <div class="card-body">
                <h6 class="mb-3">@lang('Current Status')</h6>
                <div class="didit-status-chip mb-3">
                    @php
                        $status = strtolower((string) (auth()->user()->identity_verification_status ?? 'not_started'));
                    @endphp
                    <span class="badge badge--{{ $status === 'approved' ? 'success' : ($status === 'declined' ? 'danger' : 'warning') }}">
                        {{ ucfirst(str_replace('_', ' ', $status ?: 'not_started')) }}
                    </span>
                </div>

                <ul class="didit-meta-list list-unstyled mb-0">
                    <li>
                        <span>@lang('Last Session')</span>
                        <strong>{{ $latestSession?->session_id ?? 'N/A' }}</strong>
                    </li>
                    <li>
                        <span>@lang('Last Updated')</span>
                        <strong>{{ $latestSession?->updated_at ? showDateTime($latestSession->updated_at, 'Y-m-d h:i A') : 'N/A' }}</strong>
                    </li>
                    <li>
                        <span>@lang('Verified At')</span>
                        <strong>{{ auth()->user()->didit_verified_at ? showDateTime(auth()->user()->didit_verified_at, 'Y-m-d h:i A') : 'N/A' }}</strong>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card custom--card h-auto mt-4">
            <div class="card-body">
                <h6 class="mb-3">@lang('Recent Sessions')</h6>
                @if($history->count())
                    <div class="table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('Session')</th>
                                    <th>@lang('Status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $row)
                                    <tr>
                                        <td class="text-truncate" style="max-width:160px;">{{ $row->session_id }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">@lang('No verification session found yet.')</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-8 col-lg-7">
        <div class="card custom--card h-auto" id="diditVerificationFrame">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h6 class="mb-0">@lang('Embedded Verification')</h6>
                <a href="javascript:void(0)" class="btn btn-outline--base btn-sm d-none" id="diditOpenNewTabBtn" target="_blank" rel="noopener">
                    <i class="las la-external-link-alt"></i> @lang('Open in new tab')
                </a>
            </div>
            <div class="card-body p-0">
                <div class="didit-iframe-placeholder" id="diditPlaceholder">
                    <i class="las la-id-card didit-placeholder-icon"></i>
                    <p class="mb-0">@lang('Start a verification session to load the secure Didit flow here.')</p>
                </div>
                <iframe id="diditIframe" class="didit-iframe d-none" allow="camera; microphone; fullscreen; geolocation" referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .didit-meta-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid #edf2f7;
        font-size: 14px;
    }

    .didit-meta-list li:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .didit-meta-list li span {
        color: #6b7280;
    }

    .didit-meta-list li strong {
        color: #111827;
        text-align: right;
    }

    .didit-iframe-placeholder {
        min-height: 640px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        background: #f9fafb;
        padding: 24px;
        text-align: center;
    }

    .didit-placeholder-icon {
        font-size: 42px;
        margin-bottom: 12px;
        color: #2d5bff;
    }

    .didit-iframe {
        width: 100%;
        min-height: 760px;
        border: 0;
        background: #fff;
    }

    @media (max-width: 991px) {
        .didit-iframe,
        .didit-iframe-placeholder {
            min-height: 620px;
        }
    }
</style>
@endpush

@push('script')
<script>
    (function ($) {
        'use strict';

        const startBtn = $('#diditStartBtn');
        const openInlineBtn = $('#diditOpenInlineBtn');
        const openNewTabBtn = $('#diditOpenNewTabBtn');
        const iframe = $('#diditIframe');
        const placeholder = $('#diditPlaceholder');

        startBtn.on('click', function () {
            if (startBtn.prop('disabled')) {
                return;
            }

            startBtn.prop('disabled', true);
            const originalHtml = startBtn.html();
            startBtn.html('<i class="las la-spinner la-spin"></i> {{ __('Creating session...') }}');

            $.ajax({
                url: "{{ route('user.identity.session') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (res) {
                    if (!res || !res.success || !res.data || !res.data.verification_url) {
                        throw new Error((res && res.message) ? res.message : 'Unable to start verification session.');
                    }

                    const verificationUrl = res.data.verification_url;
                    iframe.attr('src', verificationUrl).removeClass('d-none');
                    placeholder.addClass('d-none');
                    openInlineBtn.removeClass('d-none');
                    openNewTabBtn.attr('href', verificationUrl).removeClass('d-none');

                    if (typeof notify !== 'undefined' && notify) {
                        notify('success', res.message || 'Verification session created.');
                    }
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Unable to create verification session.';
                    if (typeof notify !== 'undefined' && notify) {
                        notify('error', message);
                    } else {
                        alert(message);
                    }
                },
                complete: function () {
                    startBtn.prop('disabled', false);
                    startBtn.html(originalHtml);
                }
            });
        });
    })(jQuery);
</script>
@endpush
