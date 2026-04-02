@extends('admin.layouts.app')

@section('panel')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="widget-two box--shadow2 b-radius--5 bg--primary">
            <div class="widget-two__content">
                <h6 class="text-white">@lang('Total Disputes')</h6>
                <h3 class="text-white">{{ (int) $summary['total_disputes'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-two box--shadow2 b-radius--5 bg--warning">
            <div class="widget-two__content">
                <h6 class="text-white">@lang('Open Disputes')</h6>
                <h3 class="text-white">{{ (int) $summary['open_disputes'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-two box--shadow2 b-radius--5 bg--success">
            <div class="widget-two__content">
                <h6 class="text-white">@lang('Dispute Fees Collected')</h6>
                <h3 class="text-white">{{ showAmount((float) $summary['fees_collected']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-two box--shadow2 b-radius--5 bg--danger">
            <div class="widget-two__content">
                <h6 class="text-white">@lang('Refunded Volume')</h6>
                <h3 class="text-white">{{ showAmount((float) $summary['refunded_volume']) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-lg-2">
                <input type="text" name="search" class="form-control" placeholder="@lang('Dispute ID / TX / Email')" value="{{ request('search') }}">
            </div>
            <div class="col-lg-2">
                <select name="merchant_id" class="form-control">
                    <option value="">@lang('All Merchants')</option>
                    @foreach($merchants as $merchant)
                        <option value="{{ $merchant->id }}" @selected((int) request('merchant_id') === (int) $merchant->id)>
                            {{ $merchant->username }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="status" class="form-control">
                    <option value="">@lang('All Status')</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <input type="text" name="provider" class="form-control" placeholder="@lang('Provider')" value="{{ request('provider') }}">
            </div>
            <div class="col-lg-2">
                <input type="text" name="date" class="form-control datepicker-here date-range" autocomplete="off" placeholder="@lang('Start - End Date')" value="{{ request('date') }}">
            </div>
            <div class="col-lg-2 d-grid">
                <button class="btn btn--primary" type="submit">@lang('Filter')</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">@lang('Open Dispute Manually')</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.disputes.open') }}" class="row g-2">
            @csrf
            <div class="col-lg-2">
                <input type="number" min="1" name="deposit_id" class="form-control" placeholder="@lang('Deposit ID')" required>
            </div>
            <div class="col-lg-3">
                <input type="email" name="provider_email" class="form-control" placeholder="@lang('Provider Email (optional)')">
            </div>
            <div class="col-lg-4">
                <input type="text" name="reason" class="form-control" placeholder="@lang('Reason')">
            </div>
            <div class="col-lg-3 d-grid">
                <button class="btn btn--success" type="submit">@lang('Open Dispute')</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table--light style--two mb-0">
                <thead>
                    <tr>
                        <th>@lang('Dispute')</th>
                        <th>@lang('Merchant')</th>
                        <th>@lang('Transaction')</th>
                        <th>@lang('Amount')</th>
                        <th>@lang('Fee')</th>
                        <th>@lang('Status')</th>
                        <th>@lang('Opened')</th>
                        <th>@lang('Action')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disputes as $dispute)
                        <tr>
                            <td>
                                <strong>{{ $dispute->dispute_id }}</strong>
                                <br>
                                <small>{{ $dispute->provider ?: 'N/A' }}</small>
                            </td>
                            <td>
                                {{ $dispute->merchant?->fullname ?? 'N/A' }}
                                <br>
                                <small>{{ '@' . ($dispute->merchant?->username ?? 'N/A') }}</small>
                            </td>
                            <td>{{ $dispute->transaction_id }}</td>
                            <td>{{ showAmount((float) $dispute->amount) }} {{ $dispute->currency }}</td>
                            <td>
                                {{ showAmount((float) $dispute->dispute_fee) }}
                                <br>
                                @if($dispute->dispute_fee_charged_at)
                                    <small class="text--success">@lang('Charged')</small>
                                @else
                                    <small class="text--warning">@lang('Pending')</small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $isActive = in_array($dispute->status, \App\Models\Dispute::ACTIVE_STATUSES, true);
                                @endphp
                                <span class="badge {{ $isActive ? 'badge--warning' : 'badge--success' }}">{{ ucfirst(str_replace('_', ' ', $dispute->status)) }}</span>
                            </td>
                            <td>{{ showDateTime($dispute->opened_at ?? $dispute->created_at) }}</td>
                            <td>
                                <a href="{{ route('admin.disputes.show', $dispute->id) }}" class="btn btn-sm btn-outline--primary">@lang('Manage')</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-muted" colspan="8">@lang('No disputes found')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($disputes->hasPages())
        <div class="card-footer py-3">
            {{ paginateLinks($disputes) }}
        </div>
    @endif
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">@lang('Merchant Risk Monitor')</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table--light style--two mb-0">
                <thead>
                    <tr>
                        <th>@lang('Merchant')</th>
                        <th>@lang('Transactions')</th>
                        <th>@lang('Total Volume')</th>
                        <th>@lang('Total Disputes')</th>
                        <th>@lang('Open Disputes')</th>
                        <th>@lang('Dispute Rate')</th>
                        <th>@lang('Disputed Volume')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($merchantRiskRows as $row)
                        @php
                            $totalTx = (int) ($row->total_transactions ?? 0);
                            $totalDisputes = (int) ($row->total_disputes ?? 0);
                            $rate = $totalTx > 0 ? ($totalDisputes / $totalTx) * 100 : 0;
                        @endphp
                        <tr>
                            <td>{{ $row->fullname }}<br><small>{{ '@' . $row->username }}</small></td>
                            <td>{{ $totalTx }}</td>
                            <td>{{ showAmount((float) ($row->total_volume ?? 0)) }}</td>
                            <td>{{ $totalDisputes }}</td>
                            <td>{{ (int) ($row->open_disputes ?? 0) }}</td>
                            <td>{{ number_format($rate, 2) }}%</td>
                            <td>{{ showAmount((float) ($row->disputed_volume ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-muted" colspan="7">@lang('No merchant risk data yet')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
@endpush
