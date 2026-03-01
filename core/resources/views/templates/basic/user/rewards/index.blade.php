@extends($activeTemplate.'layouts.master')

@section('content')
@php
    $status = $summary['status'];
    $nextTarget = $summary['next_level_target'];
    $totalEarned = number_format($summary['total_earned_cents'] / 100, 2);
    $withdrawable = number_format($summary['withdrawable_balance_cents'] / 100, 2);
@endphp
<div class="row gy-4">
    @if(!empty($schemaError))
        <div class="col-12">
            <div class="alert alert-warning mb-0">
                {{ __($schemaError) }}
            </div>
        </div>
    @endif

    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">@lang('FlujiPay Rewards')</h4>
                    <p class="mb-0 text-muted">
                        @lang('Current level'): <strong>{{ $summary['current_level'] }}</strong>
                        · @lang('Qualified referrals'): <strong>{{ $summary['qualified_referrals_count'] }}</strong>
                        @if($nextTarget)
                            · @lang('Next target'): <strong>{{ $nextTarget }}</strong>
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('user.rewards.index', array_merge(request()->except('page', 'referrals_page'), ['tab' => 'overview'])) }}" class="btn btn-sm {{ $tab === 'overview' ? 'btn--base' : 'btn--dark' }}">@lang('Overview')</a>
                    <a href="{{ route('user.rewards.index', array_merge(request()->except('page', 'referrals_page'), ['tab' => 'refer'])) }}" class="btn btn-sm {{ $tab === 'refer' ? 'btn--base' : 'btn--dark' }}">@lang('Refer & Earn')</a>
                    <a href="{{ route('user.rewards.index', array_merge(request()->except('page', 'referrals_page'), ['tab' => 'history'])) }}" class="btn btn-sm {{ $tab === 'history' ? 'btn--base' : 'btn--dark' }}">@lang('Earnings History')</a>
                </div>
            </div>
        </div>
    </div>

    @if($tab === 'overview')
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Progress')</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>@lang('Level') {{ $summary['current_level'] }}</span>
                        <span>{{ $summary['qualified_referrals_count'] }}{{ $nextTarget ? '/' . $nextTarget : '' }}</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $summary['progress_percent'] }}%"></div>
                    </div>

                    <hr>

                    <div class="row gy-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block mb-1">@lang('Discount benefit')</small>
                                @if($summary['discount_active'])
                                    <strong class="text-success">
                                        {{ $status->discount_active_until ? showDateTime($status->discount_active_until, 'M d, Y H:i') : __('Active') }}
                                    </strong>
                                @else
                                    <strong class="text-muted">@lang('Inactive')</strong>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block mb-1">@lang('Revenue share')</small>
                                @if($summary['revenue_share_active'])
                                    <strong class="text-success">{{ number_format($status->revenue_share_bps / 100, 2) }}%</strong>
                                @else
                                    <strong class="text-muted">@lang('Locked until Level 3')</strong>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0">@lang('Total Earned')</h6></div>
                <div class="card-body">
                    <h3 class="mb-1">${{ $totalEarned }}</h3>
                    <small class="text-muted">@lang('Ledger net total')</small>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h6 class="mb-0">@lang('Withdrawable Balance')</h6></div>
                <div class="card-body">
                    <h3 class="mb-1">${{ $withdrawable }}</h3>
                    <small class="text-muted">@lang('Available as rewards credit')</small>
                </div>
            </div>
        </div>
    @endif

    @if($tab === 'refer')
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">@lang('Your Referral Link')</h5>
                    <form method="POST" action="{{ route('user.rewards.code.regenerate') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline--dark">@lang('Regenerate')</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input id="referralLinkInput" type="text" class="form-control" value="{{ $summary['referral_link'] }}" readonly>
                        <button type="button" class="btn btn--base" id="copyReferralLinkBtn">@lang('Copy')</button>
                    </div>
                    <p class="mb-2"><strong>@lang('Code'):</strong> {{ $summary['referral_code']->code ?: 'N/A' }}</p>

                    <div class="alert alert-info mb-0">
                        <ul class="mb-0 ps-3">
                            <li>@lang('Earn $5 when each referred merchant completes their first successful sale.')</li>
                            <li>@lang('Unlock levels at 10 / 20 / 50 qualified merchants.')</li>
                            <li>@lang('Level 3 unlocks 0.5% revenue share on direct referrals transactions.')</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">@lang('Referral QR')</h5></div>
                <div class="card-body text-center">
                    <img src="{{ cryptoQR(urlencode($summary['referral_link'])) }}" alt="@lang('Referral QR')" class="img-fluid" style="max-width: 220px;">
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">@lang('Direct Referrals')</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead>
                            <tr>
                                <th>@lang('Merchant')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Registered')</th>
                                <th>@lang('Qualified')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($referrals as $referral)
                                <tr>
                                    <td>
                                        <strong>{{ optional($referral->referred)->fullname ?: optional($referral->referred)->username ?: 'N/A' }}</strong><br>
                                        <small>{{ optional($referral->referred)->email }}</small>
                                    </td>
                                    <td><span class="badge badge--{{ $referral->status === 'qualified' ? 'success' : ($referral->status === 'revoked' ? 'danger' : 'warning') }}">{{ ucfirst($referral->status) }}</span></td>
                                    <td>{{ $referral->registered_at ? showDateTime($referral->registered_at) : '-' }}</td>
                                    <td>{{ $referral->qualified_at ? showDateTime($referral->qualified_at) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">@lang('No referrals yet')</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($referrals->hasPages())
                    <div class="card-footer">{!! paginateLinks($referrals) !!}</div>
                @endif
            </div>
        </div>
    @endif

    @if($tab === 'history')
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <h5 class="mb-0">@lang('Ledger')</h5>
                    <form method="GET" class="d-flex gap-2">
                        <input type="hidden" name="tab" value="history">
                        <select name="type" class="form-control">
                            <option value="">@lang('All types')</option>
                            @foreach(['referral_bonus' => 'Bonus', 'revenue_share' => 'Revenue Share', 'reversal' => 'Reversal', 'adjustment' => 'Adjustment'] as $value => $label)
                                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn--base btn-sm" type="submit">@lang('Filter')</button>
                    </form>
                </div>
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
                                    <td colspan="5" class="text-center text-muted">@lang('No ledger entries yet')</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($ledger->hasPages())
                    <div class="card-footer">{!! paginateLinks($ledger) !!}</div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('script')
<script>
    (function () {
        const copyBtn = document.getElementById('copyReferralLinkBtn');
        const input = document.getElementById('referralLinkInput');

        if (!copyBtn || !input) {
            return;
        }

        copyBtn.addEventListener('click', async function () {
            const value = input.value || '';
            if (!value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(value);
            } catch (error) {
                input.select();
                document.execCommand('copy');
            }
        });
    })();
</script>
@endpush
