@extends('admin.layouts.app')

@section('panel')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">@lang('Card')</h5></div>
            <div class="card-body">
                <p class="mb-1"><strong>@lang('Merchant'):</strong> {{ $card->user->fullname }} ({{ '@' . $card->user->username }})</p>
                <p class="mb-1"><strong>@lang('Provider Card ID'):</strong> {{ $card->provider_card_id }}</p>
                <p class="mb-1"><strong>@lang('PAN'):</strong> {{ $card->masked_pan ?: ('**** ' . $card->last4) }}</p>
                <p class="mb-1"><strong>@lang('Balance'):</strong> {{ showAmount($card->balance) }} {{ $card->currency }}</p>
                <p class="mb-0"><strong>@lang('Status'):</strong> {{ ucfirst($card->status) }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('Fund Card')</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.virtual.cards.fund', $card->id) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label">@lang('Amount (deducted from merchant general balance)')</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="submit" class="btn btn--primary">@lang('Fund')</button>
                    </div>
                </form>
                <form action="{{ route('admin.virtual.cards.sync', $card->id) }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn--dark btn-sm">@lang('Sync Transactions')</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('Card Controls')</h5></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <form action="{{ route('admin.virtual.cards.freeze', $card->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="freeze" value="{{ strtolower($card->status) === 'frozen' ? 0 : 1 }}">
                            <button type="submit" class="btn btn-outline--warning w-100">
                                {{ strtolower($card->status) === 'frozen' ? __('Unfreeze Card') : __('Freeze Card') }}
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form action="{{ route('admin.virtual.cards.mastercard.details', $card->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline--info w-100">@lang('Fetch Mastercard Details')</button>
                        </form>
                    </div>
                </div>

                <form action="{{ route('admin.virtual.cards.withdraw', $card->id) }}" method="POST" class="row g-2 align-items-end mt-2">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label">@lang('Withdraw Amount to Merchant Balance')</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="submit" class="btn btn-outline--success">@lang('Withdraw')</button>
                    </div>
                </form>

                <form action="{{ route('admin.virtual.cards.upgrade.limit', $card->id) }}" method="POST" class="row g-2 align-items-end mt-2">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label">@lang('Upgrade Card Limit')</label>
                        <input type="number" step="0.01" min="0.01" name="new_limit" class="form-control" required>
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="submit" class="btn btn-outline--primary">@lang('Upgrade')</button>
                    </div>
                </form>
            </div>
        </div>

        @if($mastercardData)
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">@lang('Mastercard Details')</h5></div>
                <div class="card-body">
                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 12px;">{{ json_encode($mastercardData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h5 class="mb-0">@lang('Transactions')</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('Type')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Description')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($card->transactions as $txn)
                            <tr>
                                <td>{{ showDateTime($txn->transacted_at ?: $txn->created_at) }}</td>
                                <td>{{ strtoupper($txn->type) }}</td>
                                <td>{{ showAmount($txn->amount) }} {{ $txn->currency }}</td>
                                <td>{{ $txn->description ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">@lang('No transactions found')</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
