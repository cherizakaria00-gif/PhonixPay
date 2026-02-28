@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">@lang('Subscription Plans')</h5>
                    <a href="{{ route('admin.plans.create') }}" class="btn btn-sm btn-outline--primary">
                        <i class="las la-plus"></i> @lang('Create Plan')
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Plan')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Tx Limit')</th>
                                    <th>@lang('Fees')</th>
                                    <th>@lang('Payout')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr>
                                        <td>
                                            <strong>{{ $plan->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $plan->slug }}</small>
                                        </td>
                                        <td>${{ number_format($plan->price_monthly_cents / 100, 2) }}/@lang('month')</td>
                                        <td>{{ $plan->tx_limit_monthly ?? __('Unlimited') }}</td>
                                        <td>{{ number_format($plan->fee_percent, 2) }}% + ${{ number_format($plan->fee_fixed, 2) }}</td>
                                        <td>{{ str_replace('_', ' ', $plan->payout_frequency) }}</td>
                                        <td>
                                            @if($plan->is_active)
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Disabled')</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.plans.edit', $plan->id) }}" class="btn btn-sm btn-outline--primary">
                                                    <i class="las la-edit"></i> @lang('Edit')
                                                </a>
                                                <form action="{{ route('admin.plans.status', $plan->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline--warning">
                                                        <i class="las la-power-off"></i>
                                                        {{ $plan->is_active ? __('Disable') : __('Enable') }}
                                                    </button>
                                                </form>
                                                @if(!$plan->is_default)
                                                    <form action="{{ route('admin.plans.delete', $plan->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this plan?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline--danger">
                                                            <i class="las la-trash"></i> @lang('Delete')
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($plans->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($plans) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
