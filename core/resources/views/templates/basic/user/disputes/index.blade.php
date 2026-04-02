@extends($activeTemplate.'layouts.master')

@section('content')
<div class="row gy-3">
    <div class="col-md-3">
        <div class="card custom--card border-0 h-100">
            <div class="card-body">
                <div class="text-muted">@lang('Dispute Rate')</div>
                <h4 class="mb-0">{{ number_format((float) $metrics['dispute_rate'], 2) }}%</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card custom--card border-0 h-100">
            <div class="card-body">
                <div class="text-muted">@lang('Open Dispute Amount')</div>
                <h4 class="mb-0">{{ showAmount((float) $metrics['open_dispute_amount']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card custom--card border-0 h-100">
            <div class="card-body">
                <div class="text-muted">@lang('Dispute Fees Charged')</div>
                <h4 class="mb-0">{{ showAmount((float) $metrics['total_dispute_fees']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card custom--card border-0 h-100">
            <div class="card-body">
                <div class="text-muted">@lang('Refunded Amount')</div>
                <h4 class="mb-0">{{ showAmount((float) $metrics['refunded_amount']) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card custom--card border-0">
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="@lang('Dispute ID / Transaction / Email')">
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-control">
                            <option value="">@lang('All Status')</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn--base">@lang('Filter')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card custom--card border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>@lang('Dispute')</th>
                                <th>@lang('Transaction')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Deadline')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($disputes as $dispute)
                                <tr>
                                    <td>{{ $dispute->dispute_id }}</td>
                                    <td>{{ $dispute->transaction_id }}</td>
                                    <td>{{ showAmount((float) $dispute->amount) }} {{ $dispute->currency }}</td>
                                    <td>
                                        @php $active = in_array($dispute->status, \App\Models\Dispute::ACTIVE_STATUSES, true); @endphp
                                        <span class="badge {{ $active ? 'bg-warning' : 'bg-success' }}">{{ ucfirst(str_replace('_', ' ', $dispute->status)) }}</span>
                                    </td>
                                    <td>{{ $dispute->resolution_deadline ? showDateTime($dispute->resolution_deadline) : 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('user.disputes.show', $dispute->id) }}" class="btn btn-sm btn-outline--primary">@lang('Details')</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-muted" colspan="6">@lang('No disputes found')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        @if($disputes->hasPages())
            {{ paginatelinks($disputes) }}
        @endif
    </div>
</div>
@endsection
