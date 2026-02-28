@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">@lang('Merchant Plans')</h5>
                    <x-search-form placeholder="Username / Email" />
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Merchant')</th>
                                    <th>@lang('Current Plan')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Usage This Month')</th>
                                    <th>@lang('Last Payout')</th>
                                    <th>@lang('Assign Plan')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($merchants as $merchant)
                                    @php
                                        $merchantUsage = $usage[$merchant->id] ?? ['used' => 0, 'limit' => null, 'unlimited' => true];
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $merchant->fullname ?: $merchant->username }}</strong>
                                            <br>
                                            <small>{{ $merchant->email }}</small>
                                        </td>
                                        <td>{{ $merchant->plan->name ?? 'Starter' }}</td>
                                        <td><span class="badge badge--info text-capitalize">{{ $merchant->plan_status ?? 'active' }}</span></td>
                                        <td>
                                            {{ $merchantUsage['used'] }} /
                                            {{ $merchantUsage['unlimited'] ? __('Unlimited') : $merchantUsage['limit'] }}
                                        </td>
                                        <td>
                                            {{ $merchant->last_payout_at ? showDateTime($merchant->last_payout_at) : '-' }}
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.plans.merchants.assign', $merchant->id) }}" method="POST" class="d-flex gap-2">
                                                @csrf
                                                <select name="plan_id" class="form-control form-control-sm" required>
                                                    @foreach($plans as $plan)
                                                        <option value="{{ $plan->id }}" {{ (int) $merchant->plan_id === (int) $plan->id ? 'selected' : '' }}>
                                                            {{ $plan->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline--primary">@lang('Apply')</button>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.plans.merchants.detail', $merchant->id) }}" class="btn btn-sm btn-outline--dark">
                                                <i class="las la-eye"></i> @lang('Details')
                                            </a>
                                            <a href="{{ route('admin.users.detail', $merchant->id) }}" class="btn btn-sm btn-outline--primary">
                                                <i class="las la-user"></i> @lang('Merchant')
                                            </a>
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
                @if($merchants->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($merchants) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
