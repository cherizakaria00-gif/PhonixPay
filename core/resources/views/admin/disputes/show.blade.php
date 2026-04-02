@extends('admin.layouts.app')

@section('panel')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">@lang('Dispute Summary')</h5></div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span>@lang('Dispute ID')</span><strong>{{ $dispute->dispute_id }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>@lang('Transaction')</span><strong>{{ $dispute->transaction_id }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>@lang('Status')</span><strong>{{ ucfirst(str_replace('_', ' ', $dispute->status)) }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>@lang('Amount')</span><strong>{{ showAmount((float) $dispute->amount) }} {{ $dispute->currency }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>@lang('Dispute Fee')</span><strong>{{ showAmount((float) $dispute->dispute_fee) }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>@lang('Fee Debited')</span><strong class="{{ $dispute->dispute_fee_charged_at ? 'text--success' : 'text--warning' }}">{{ $dispute->dispute_fee_charged_at ? 'Yes' : 'No' }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>@lang('Tx Amount Debited')</span><strong class="{{ $dispute->transaction_amount_debited_at ? 'text--danger' : 'text--warning' }}">{{ $dispute->transaction_amount_debited_at ? 'Yes' : 'No' }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>@lang('Opened At')</span><strong>{{ showDateTime($dispute->opened_at ?? $dispute->created_at) }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>@lang('Resolution Deadline')</span><strong>{{ $dispute->resolution_deadline ? showDateTime($dispute->resolution_deadline) : 'N/A' }}</strong></li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">@lang('Merchant Risk Snapshot')</h5></div>
            <div class="card-body">
                <p class="mb-1">@lang('Dispute Rate'): <strong>{{ number_format((float) $merchantMetrics['dispute_rate'], 2) }}%</strong></p>
                <p class="mb-1">@lang('Open Dispute Amount'): <strong>{{ showAmount((float) $merchantMetrics['open_dispute_amount']) }}</strong></p>
                <p class="mb-1">@lang('Total Dispute Fees Charged'): <strong>{{ showAmount((float) $merchantMetrics['total_dispute_fees']) }}</strong></p>
                <p class="mb-1">@lang('Refunded Amount'): <strong>{{ showAmount((float) $merchantMetrics['refunded_amount']) }}</strong></p>
                <p class="mb-0">@lang('Risk Level'): <span class="badge badge--{{ $merchantMetrics['merchant_risk_level'] === 'high' ? 'danger' : ($merchantMetrics['merchant_risk_level'] === 'medium' ? 'warning' : 'success') }}">{{ ucfirst($merchantMetrics['merchant_risk_level']) }}</span></p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('Admin Actions')</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.disputes.status', $dispute->id) }}" method="POST" class="row g-2 mb-3">
                    @csrf
                    <div class="col-md-4">
                        <select name="status" class="form-control" required>
                            @foreach(array_merge(\App\Models\Dispute::ACTIVE_STATUSES, \App\Models\Dispute::TERMINAL_STATUSES) as $status)
                                <option value="{{ $status }}" @selected($dispute->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="admin_notes" placeholder="@lang('Admin note for this action')">
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn--primary" type="submit">@lang('Update Status')</button>
                    </div>
                </form>

                <form action="{{ route('admin.disputes.provider.notify', $dispute->id) }}" method="POST" class="row g-2 mb-3">
                    @csrf
                    <div class="col-md-4">
                        <input type="email" class="form-control" name="provider_email" value="{{ $dispute->provider_email }}" placeholder="@lang('Provider email')" required>
                    </div>
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="subject" placeholder="@lang('Email subject')" value="Dispute {{ $dispute->dispute_id }} requires attention">
                    </div>
                    <div class="col-12">
                        <textarea class="form-control" name="message" rows="3" placeholder="@lang('Provider message')" required>Dispute {{ $dispute->dispute_id }} on transaction {{ $dispute->transaction_id }} requires provider review.</textarea>
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn--info" type="submit">@lang('Send Provider Email')</button>
                    </div>
                </form>

                <form action="{{ route('admin.disputes.notes', $dispute->id) }}" method="POST" class="row g-2">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">@lang('Admin Notes')</label>
                        <textarea class="form-control" name="admin_notes" rows="3">{{ $dispute->admin_notes }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">@lang('Merchant Notes')</label>
                        <textarea class="form-control" name="merchant_notes" rows="3">{{ $dispute->merchant_notes }}</textarea>
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn--dark" type="submit">@lang('Save Notes')</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('Merchant & Transaction')</h5></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6"><strong>@lang('Merchant'):</strong> {{ $dispute->merchant?->fullname }} ({{ '@' . ($dispute->merchant?->username ?? 'N/A') }})</div>
                    <div class="col-md-6"><strong>@lang('Customer Email'):</strong> {{ $dispute->customer_email ?: 'N/A' }}</div>
                    <div class="col-md-6"><strong>@lang('Provider'):</strong> {{ $dispute->provider ?: 'N/A' }}</div>
                    <div class="col-md-6"><strong>@lang('Provider Reference'):</strong> {{ $dispute->provider_reference ?: 'N/A' }}</div>
                    <div class="col-md-12"><strong>@lang('Reason'):</strong> {{ $dispute->reason ?: 'N/A' }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">@lang('Timeline / Audit Log')</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                            <tr>
                                <th>@lang('Date')</th>
                                <th>@lang('Action')</th>
                                <th>@lang('From')</th>
                                <th>@lang('To')</th>
                                <th>@lang('Actor')</th>
                                <th>@lang('Note')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dispute->logs as $log)
                                <tr>
                                    <td>{{ showDateTime($log->created_at) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                                    <td>{{ $log->old_status ? ucfirst(str_replace('_', ' ', $log->old_status)) : '-' }}</td>
                                    <td>{{ $log->new_status ? ucfirst(str_replace('_', ' ', $log->new_status)) : '-' }}</td>
                                    <td>{{ ucfirst($log->actor_type) }}{{ $log->actor_id ? ' #' . $log->actor_id : '' }}</td>
                                    <td>{{ $log->note ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="6">@lang('No logs found')</td>
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
