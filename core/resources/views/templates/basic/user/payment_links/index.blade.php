@extends($activeTemplate.'layouts.master')

@php
    $showHeaderBalance = true;
@endphp

@section('content')
    <div class="row">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center mb-3 pf-links-header">
            <div>
                <h4 class="mb-1">@lang('Payment Links')</h4>
                <p class="mb-0 text-muted">@lang('Create and manage shareable links for your customers.')</p>
            </div>
            <a href="{{ route('user.payment.links.create') }}" class="btn btn--base btn-sm">
                <i class="las la-plus"></i> @lang('Create Payment Link')
            </a>
        </div>

        <div class="col-12">
            <div class="pf-links-grid">
                @forelse($paymentLinks as $link)
                    @php
                        $linkUrl = route('payment.link.show', $link->code);
                        $salesCount = (int) ($link->deposits_count ?? 0);
                    @endphp
                    <div class="pf-link-card">
                        <div class="pf-link-card__top">
                            <div>
                                <h5 class="pf-link-title mb-1">{{ $link->description ?: __('Payment Link') }}</h5>
                                <div class="pf-link-amount">
                                    {{ showAmount($link->amount, currencyFormat:false) }}
                                    <span>{{ __($link->currency) }}</span>
                                </div>
                            </div>
                            <div class="pf-link-status">
                                @php echo $link->statusBadge; @endphp
                            </div>
                        </div>

                        <div class="pf-link-meta">
                            <div class="pf-link-meta__item">
                                <span class="pf-link-meta__label">@lang('Views')</span>
                                <span class="pf-link-meta__value">0</span>
                            </div>
                            <div class="pf-link-meta__item">
                                <span class="pf-link-meta__label">@lang('Sales')</span>
                                <span class="pf-link-meta__value">{{ $salesCount }}</span>
                            </div>
                            <div class="pf-link-meta__item">
                                <span class="pf-link-meta__label">@lang('Expires')</span>
                                <span class="pf-link-meta__value">
                                    @if($link->expires_at)
                                        {{ showDateTime($link->expires_at) }}
                                    @else
                                        @lang('Never')
                                    @endif
                                </span>
                            </div>
                            <div class="pf-link-meta__item">
                                <span class="pf-link-meta__label">@lang('Mode')</span>
                                <span class="pf-link-meta__value">
                                    {{ $link->allowsMultiplePayments() ? __('Multi-use') : __('One-time') }}
                                </span>
                            </div>
                        </div>

                        <div class="pf-link-url">
                            <input type="text" value="{{ $linkUrl }}" readonly>
                            <button type="button" class="pf-link-copy copy-link-btn" data-link="{{ $linkUrl }}">
                                <i class="las la-copy"></i>
                            </button>
                        </div>

                        <div class="pf-link-actions">
                            @if($link->status == \App\Models\PaymentLink::STATUS_ACTIVE)
                                <a href="{{ route('user.payment.links.edit', $link->id) }}" class="btn btn--light btn-sm">
                                    <i class="las la-edit"></i> @lang('Edit')
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                            <a href="{{ $linkUrl }}" class="btn btn--light btn-sm" target="_blank" rel="noopener">
                                <i class="las la-external-link-alt"></i> @lang('Open')
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="pf-link-empty">
                        <p class="text-muted mb-0">@lang('No payment links found')</p>
                    </div>
                @endforelse
            </div>

            @if($paymentLinks->hasPages())
                <div class="mt-4">
                    @php echo paginateLinks($paymentLinks) @endphp
                </div>
            @endif

            <div class="pf-link-history mt-4">
                <div class="pf-link-history__header">
                    <h5 class="mb-0">@lang('Payment Link History')</h5>
                    <p class="text-muted mb-0">@lang('Only payments made through your payment links are shown here.')</p>
                </div>

                <div class="table-responsive">
                    <table class="table pf-link-history__table">
                        <thead>
                            <tr>
                                <th>@lang('Customer')</th>
                                <th>@lang('Link')</th>
                                <th class="text-end">@lang('Amount')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Reference')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paymentLinkPayments as $deposit)
                                @php
                                    $statusLabel = 'Initiated';
                                    $statusClass = 'status-badge--warning';
                                    if ($deposit->status == \App\Constants\Status::PAYMENT_SUCCESS) {
                                        $statusLabel = 'Succeeded';
                                        $statusClass = 'status-badge--success';
                                    } elseif ($deposit->status == \App\Constants\Status::PAYMENT_REFUNDED) {
                                        $statusLabel = 'Refunded';
                                        $statusClass = 'status-badge--warning';
                                    } elseif ($deposit->status == \App\Constants\Status::PAYMENT_REJECT) {
                                        $statusLabel = 'Canceled';
                                        $statusClass = 'status-badge--danger';
                                    }

                                    $customer = $deposit->apiPayment->customer ?? null;
                                    $customerName = '';
                                    if ($customer) {
                                        $customerName = trim($customer->name ?? (($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')));
                                    }

                                    $paidCurrency = strtoupper((string) ($deposit->method_currency ?? ''));
                                    $baseCurrency = strtoupper((string) gs('cur_text'));
                                    $showPaidAmount = $paidCurrency !== '' && $paidCurrency !== $baseCurrency;
                                @endphp
                                <tr>
                                    <td>{{ $customerName ?: __('N/A') }}</td>
                                    <td>{{ $deposit->paymentLink->description ?? $deposit->paymentLink->code ?? __('N/A') }}</td>
                                    <td class="text-end">
                                        <strong>{{ showAmount($deposit->amount) }}</strong>
                                        @if($showPaidAmount)
                                            <small class="d-block text-muted">
                                                @lang('Paid'): {{ showAmount((float) $deposit->gateway_amount, currencyFormat: false) }} {{ $paidCurrency }}
                                            </small>
                                        @endif
                                    </td>
                                    <td><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td>{{ $deposit->trx ?: __('N/A') }}</td>
                                    <td>{{ showDateTime($deposit->created_at, 'M d, Y h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">@lang('No payment link payments found')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($paymentLinkPayments->hasPages())
                    <div class="mt-3">
                        @php echo paginateLinks($paymentLinkPayments) @endphp
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('style')
<style>
    .pf-links-header h4 {
        font-weight: 600;
        color: #0f172a;
    }

    .pf-links-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 18px;
    }

    .pf-link-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .pf-link-card__top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }

    .pf-link-title {
        font-size: 16px;
        font-weight: 600;
        color: #0f172a;
    }

    .pf-link-amount {
        font-size: 20px;
        font-weight: 600;
        color: #4f46e5;
    }

    .pf-link-amount span {
        font-size: 12px;
        color: #6b7280;
        font-weight: 600;
        margin-left: 4px;
    }

    .pf-link-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border-top: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
        padding: 12px 0;
    }

    .pf-link-meta__item {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 12px;
    }

    .pf-link-meta__label {
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .pf-link-meta__value {
        color: #0f172a;
        font-weight: 600;
        font-size: 13px;
    }

    .pf-link-url {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 8px 10px;
    }

    .pf-link-url input {
        border: none;
        background: transparent;
        font-size: 12px;
        color: #475569;
        width: 100%;
        outline: none;
    }

    .pf-link-copy {
        border: 0;
        background: #ffffff;
        border-radius: 8px;
        padding: 6px 8px;
        color: #4f46e5;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
    }

    .pf-link-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .pf-link-empty {
        background: #ffffff;
        border: 1px dashed #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
    }

    .pf-link-history {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
    }

    .pf-link-history__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }

    .pf-link-history__table thead th {
        font-size: 12px;
        color: #64748b;
        font-weight: 700;
        border-bottom-color: #e5e7eb;
    }

    .pf-link-history__table tbody td {
        vertical-align: middle;
        border-bottom-color: #eef2f7;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 118px;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 14px;
        line-height: 1;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-badge--warning {
        background: #f3eab3;
        color: #ac6c00;
    }

    .status-badge--success {
        background: #bfeccf;
        color: #0f9f4a;
    }

    .status-badge--danger {
        background: #f7d5d9;
        color: #ea4040;
    }

    @media (max-width: 575px) {
        .pf-link-meta {
            flex-direction: column;
            align-items: flex-start;
        }

        .pf-link-actions {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endpush

@push('script')
<script>
    (function() {
        document.querySelectorAll('.copy-link-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const link = this.getAttribute('data-link');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(link);
                } else {
                    const temp = document.createElement('input');
                    temp.value = link;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                }
            });
        });
    })();
</script>
@endpush
