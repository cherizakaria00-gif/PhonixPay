@extends('admin.layouts.app')

@section('panel')
@php
    $status = $summary['status'];
@endphp
<div class="row gy-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">{{ $merchant->fullname ?: $merchant->username }} · @lang('Rewards Profile')</h5>
                <a href="{{ route('admin.users.detail', $merchant->id) }}" class="btn btn-sm btn-outline--dark">@lang('Merchant Profile')</a>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <small class="text-muted">@lang('Current Level')</small>
                            <h4 class="mb-0">{{ $summary['current_level'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <small class="text-muted">@lang('Qualified Referrals')</small>
                            <h4 class="mb-0">{{ $summary['qualified_referrals_count'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <small class="text-muted">@lang('Total Earned')</small>
                            <h4 class="mb-0">${{ number_format($summary['total_earned_cents'] / 100, 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <small class="text-muted">@lang('Revenue Share')</small>
                            <h4 class="mb-0">{{ number_format($status->revenue_share_bps / 100, 2) }}%</h4>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row gy-3">
                    <div class="col-lg-6">
                        <div class="alert alert-info mb-0">
                            <p class="mb-1"><strong>@lang('Referral Code'):</strong> {{ $summary['referral_code']->code }}</p>
                            <p class="mb-0"><strong>@lang('Referral Link'):</strong> <a href="{{ $summary['referral_link'] }}" target="_blank">{{ $summary['referral_link'] }}</a></p>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <form method="POST" action="{{ route('admin.rewards.adjust', $merchant->id) }}" class="row g-2">
                            @csrf
                            <div class="col-sm-4">
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount (USD)" required>
                            </div>
                            <div class="col-sm-6">
                                <input type="text" name="description" class="form-control" placeholder="Reason" required>
                            </div>
                            <div class="col-sm-2 d-grid">
                                <button type="submit" class="btn btn--base btn-sm">@lang('Adjust')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">@lang('Referrals')</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                        <tr>
                            <th>@lang('Referred Merchant')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Registered')</th>
                            <th>@lang('Qualified')</th>
                            <th>@lang('Action')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($referrals as $referral)
                            <tr>
                                <td>
                                    <strong>{{ optional($referral->referred)->fullname ?: optional($referral->referred)->username }}</strong><br>
                                    <small>{{ optional($referral->referred)->email }}</small>
                                </td>
                                <td>
                                    <span class="badge badge--{{ $referral->status === 'qualified' ? 'success' : ($referral->status === 'revoked' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($referral->status) }}
                                    </span>
                                </td>
                                <td>{{ $referral->registered_at ? showDateTime($referral->registered_at) : '-' }}</td>
                                <td>{{ $referral->qualified_at ? showDateTime($referral->qualified_at) : '-' }}</td>
                                <td>
                                    @if($referral->status !== 'revoked')
                                        <form method="POST" action="{{ route('admin.rewards.referral.revoke', $referral->id) }}" class="d-flex gap-2">
                                            @csrf
                                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (optional)">
                                            <button type="submit" class="btn btn-sm btn-outline--danger">@lang('Revoke')</button>
                                        </form>
                                    @else
                                        <span class="text-muted">@lang('Revoked')</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">@lang('No referrals found')</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($referrals->hasPages())
                <div class="card-footer">@php echo paginateLinks($referrals) @endphp</div>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">@lang('Rewards Ledger')</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('Type')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Source')</th>
                            <th>@lang('Description')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($ledger as $entry)
                            <tr>
                                <td>{{ showDateTime($entry->created_at) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $entry->type)) }}</td>
                                <td class="{{ $entry->amount_cents >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $entry->amount_cents >= 0 ? '+' : '-' }}${{ number_format(abs($entry->amount_cents) / 100, 2) }}
                                </td>
                                <td>{{ ucfirst($entry->source_type) }}{{ $entry->source_id ? ' #' . $entry->source_id : '' }}</td>
                                <td>{{ $entry->description ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">@lang('No ledger entries found')</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($ledger->hasPages())
                <div class="card-footer">@php echo paginateLinks($ledger) @endphp</div>
            @endif
        </div>
    </div>
</div>
@endsection
