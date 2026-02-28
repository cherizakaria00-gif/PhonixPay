@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Plan Change Requests')</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Merchant')</th>
                                    <th>@lang('Current Plan')</th>
                                    <th>@lang('Requested Plan')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Requested At')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $requestItem)
                                    <tr>
                                        <td>
                                            <strong>{{ $requestItem->user->username ?? 'N/A' }}</strong>
                                            <br>
                                            <small>{{ $requestItem->user->email ?? '-' }}</small>
                                        </td>
                                        <td>{{ $requestItem->fromPlan->name ?? __('Starter') }}</td>
                                        <td>{{ $requestItem->toPlan->name ?? '-' }}</td>
                                        <td>
                                            @if($requestItem->status === 'pending')
                                                <span class="badge badge--warning">@lang('Pending')</span>
                                            @elseif($requestItem->status === 'approved')
                                                <span class="badge badge--success">@lang('Approved')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Rejected')</span>
                                            @endif
                                        </td>
                                        <td>{{ showDateTime($requestItem->created_at) }}</td>
                                        <td>
                                            @if($requestItem->status === 'pending')
                                                <div class="d-flex flex-wrap gap-2">
                                                    <form method="POST" action="{{ route('admin.plans.requests.approve', $requestItem->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline--success">
                                                            <i class="las la-check-circle"></i> @lang('Approve')
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('admin.plans.requests.reject', $requestItem->id) }}" class="d-flex gap-2 align-items-center">
                                                        @csrf
                                                        <input type="text" name="note" class="form-control form-control-sm" placeholder="@lang('Reason (optional)')">
                                                        <button type="submit" class="btn btn-sm btn-outline--danger">
                                                            <i class="las la-times-circle"></i> @lang('Reject')
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
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
                @if($requests->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($requests) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
