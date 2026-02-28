@extends($activeTemplate.'layouts.master')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Current Plan')</h5>
                </div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-lg-8">
                            <h4 class="mb-1">{{ $currentPlan['name'] }}</h4>
                            <p class="text-muted mb-3">
                                ${{ number_format($currentPlan['price_monthly_cents'] / 100, 2) }}/@lang('month') ·
                                <span class="text-capitalize">{{ $user->plan_status ?? 'active' }}</span>
                                @if($user->plan_renews_at)
                                    · @lang('Renews') {{ showDateTime($user->plan_renews_at, 'M d, Y') }}
                                @endif
                            </p>

                            <div class="mb-2 d-flex justify-content-between">
                                <span>@lang('Transactions used this month')</span>
                                <strong>
                                    {{ $usage['used'] }} /
                                    {{ $usage['unlimited'] ? __('Unlimited') : $usage['limit'] }}
                                </strong>
                            </div>
                            <div class="progress mb-3" style="height:10px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $usage['unlimited'] ? 0 : $usage['percent'] }}%"></div>
                            </div>

                            <p class="mb-1"><strong>@lang('Fees'):</strong> {{ number_format($currentPlan['fee_percent'], 2) }}% + ${{ number_format($currentPlan['fee_fixed'], 2) }}</p>
                            <p class="mb-1"><strong>@lang('Payout schedule'):</strong> {{ $usage['payout_frequency_label'] }}</p>
                            <p class="mb-1"><strong>@lang('Support'):</strong> {{ implode(', ', $currentPlan['support_channels'] ?? ['email']) }}</p>
                            <p class="mb-0"><strong>@lang('Notifications'):</strong> {{ implode(', ', $currentPlan['notification_channels'] ?? ['push']) }}</p>
                        </div>
                        <div class="col-lg-4">
                            @if($pendingRequest)
                                <div class="alert alert-warning mb-0">
                                    <strong>@lang('Pending Request')</strong><br>
                                    @lang('Your request to switch to') <strong>{{ $pendingRequest->toPlan->name ?? '' }}</strong> @lang('is awaiting admin approval.')
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    @lang('Choose a plan below. Paid plans are activated immediately after successful payment.')
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Available Plans')</h5>
                </div>
                <div class="card-body">
                    <div class="row gy-4">
                        @foreach($plans as $plan)
                            @php
                                $isCurrent = (int) ($currentPlanId ?? 0) === (int) $plan->id;
                                $isStarter = $plan->slug === 'starter';
                                $features = $plan->features ?? [];
                                $payoutLabel = match($plan->payout_frequency) {
                                    'twice_weekly' => '2x per week (Tue/Fri)',
                                    'every_2_days' => 'Every 2 days',
                                    default => 'Every 7 days',
                                };
                            @endphp
                            <div class="col-lg-3 col-md-6">
                                <div class="card h-100 border {{ $isCurrent ? 'border-primary' : '' }}">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">{{ $plan->name }}</h6>
                                            @if($isCurrent)
                                                <span class="badge badge--success">@lang('Current')</span>
                                            @endif
                                        </div>

                                        <h4 class="mb-3">${{ number_format($plan->price_monthly_cents / 100, 2) }}<small class="text-muted">/@lang('mo')</small></h4>

                                        <ul class="list-group list-group-flush mb-3">
                                            <li class="list-group-item px-0">@lang('Limit'): {{ $plan->tx_limit_monthly ?? __('Unlimited') }}</li>
                                            <li class="list-group-item px-0">@lang('Fees'): {{ number_format($plan->fee_percent, 2) }}% + ${{ number_format($plan->fee_fixed, 2) }}</li>
                                            <li class="list-group-item px-0">@lang('Payout'): {{ $payoutLabel }}</li>
                                            <li class="list-group-item px-0">@lang('Support'): {{ implode(', ', $plan->support_channels ?? ['email']) }}</li>
                                            <li class="list-group-item px-0">@lang('Notify'): {{ implode(', ', $plan->notification_channels ?? ['push']) }}</li>
                                            <li class="list-group-item px-0">@lang('Payment Links'): {{ ($features['payment_links'] ?? false) ? __('Enabled') : __('Not included') }}</li>
                                        </ul>

                                        @if(!$isCurrent)
                                            <form action="{{ route('user.plan.change') }}" method="POST" class="mt-auto">
                                                @csrf
                                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                                <button type="submit" class="btn btn--base w-100" {{ $pendingRequest ? 'disabled' : '' }}>
                                                    {{ $isStarter ? __('Switch to Free') : __('Pay & Upgrade to ') . $plan->name }}
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn--dark w-100 mt-auto" disabled>@lang('Active Plan')</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
