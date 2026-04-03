@extends($activeTemplate.'layouts.master')

@section('content')
@php
    $customer = $dispute->deposit->apiPayment->customer ?? null;
    $customerName = '';
    if ($customer) {
        $customerName = trim((string) ($customer->name ?? (($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))));
    }
    $customerEmail = $customer->email ?? $dispute->customer_email;
    $customerPhone = $customer->mobile
        ?? ($customer->phone
        ?? data_get($dispute->deposit->detail, 'customer.mobile')
        ?? data_get($dispute->deposit->detail, 'customer.phone'));
@endphp
<div class="row gy-3">
    <div class="col-lg-4">
        <div class="card custom--card border-0">
            <div class="card-body">
                <h5 class="mb-3">@lang('Dispute Summary')</h5>
                <p class="mb-1"><strong>@lang('Dispute ID'):</strong> {{ $dispute->dispute_id }}</p>
                <p class="mb-1"><strong>@lang('Transaction'):</strong> {{ $dispute->transaction_id }}</p>
                <p class="mb-1"><strong>@lang('Amount'):</strong> {{ showAmount((float) $dispute->amount) }} {{ $dispute->currency }}</p>
                <p class="mb-1"><strong>@lang('Status'):</strong> {{ ucfirst(str_replace('_', ' ', $dispute->status)) }}</p>
                <p class="mb-1"><strong>@lang('Opened'):</strong> {{ showDateTime($dispute->opened_at ?? $dispute->created_at) }}</p>
                <p class="mb-0"><strong>@lang('Resolution Deadline'):</strong> {{ $dispute->resolution_deadline ? showDateTime($dispute->resolution_deadline) : 'N/A' }}</p>
            </div>
        </div>

        @if(in_array($dispute->status, \App\Models\Dispute::ACTIVE_STATUSES, true))
            <div class="card custom--card border-0 mt-3">
                <div class="card-body">
                    <h6 class="mb-3">@lang('Request Resolution')</h6>
                    <form method="POST" action="{{ route('user.disputes.resolve', $dispute->id) }}">
                        @csrf
                        <div class="mb-2">
                            <textarea class="form-control" name="merchant_note" rows="3" placeholder="@lang('Optional note for admin')"></textarea>
                        </div>
                        <button class="btn btn--base w-100" type="submit">@lang('Resolve / Start 24h window')</button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="card custom--card border-0 mb-3">
            <div class="card-body">
                <h5 class="mb-3">@lang('Details')</h5>
                <p class="mb-2"><strong>@lang('Reason'):</strong> {{ $dispute->reason ?: 'N/A' }}</p>
                <p class="mb-2"><strong>@lang('Customer'):</strong> {{ $customerName ?: 'N/A' }}</p>
                <p class="mb-2"><strong>@lang('Customer Email'):</strong> {{ $customerEmail ?: 'N/A' }}</p>
                <p class="mb-2"><strong>@lang('Customer Phone'):</strong> {{ $customerPhone ?: 'N/A' }}</p>
                <p class="mb-2"><strong>@lang('Provider Email Notified At'):</strong> {{ $dispute->provider_email_sent_at ? showDateTime($dispute->provider_email_sent_at) : 'N/A' }}</p>
                <p class="mb-0"><strong>@lang('Merchant Notes'):</strong> {{ $dispute->merchant_notes ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="card custom--card border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>@lang('Date')</th>
                                <th>@lang('Action')</th>
                                <th>@lang('From')</th>
                                <th>@lang('To')</th>
                                <th>@lang('Actor')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dispute->logs as $log)
                                <tr>
                                    <td>{{ showDateTime($log->created_at) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                                    <td>{{ $log->old_status ? ucfirst(str_replace('_', ' ', $log->old_status)) : '-' }}</td>
                                    <td>{{ $log->new_status ? ucfirst(str_replace('_', ' ', $log->new_status)) : '-' }}</td>
                                    <td>{{ ucfirst($log->actor_type) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-muted" colspan="5">@lang('No log entries')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
