@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive table-responsive--sm">
                    <table class="table table--light style--two">
                        <thead>
                        <tr>
                            <th>@lang('Merchant')</th>
                            <th>@lang('Level')</th>
                            <th>@lang('Qualified Referrals')</th>
                            <th>@lang('Revenue Share')</th>
                            <th>@lang('Action')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($merchants as $merchant)
                            @php
                                $rewardStatus = $merchant->rewardStatus;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $merchant->fullname ?: $merchant->username }}</strong><br>
                                    <small>{{ $merchant->email }}</small>
                                </td>
                                <td>{{ (int) optional($rewardStatus)->current_level }}</td>
                                <td>{{ (int) optional($rewardStatus)->qualified_referrals_count }}</td>
                                <td>
                                    @if((int) optional($rewardStatus)->revenue_share_bps > 0)
                                        <span class="badge badge--success">{{ number_format(optional($rewardStatus)->revenue_share_bps / 100, 2) }}%</span>
                                    @else
                                        <span class="badge badge--warning">@lang('Inactive')</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.rewards.show', $merchant->id) }}" class="btn btn-sm btn-outline--primary">
                                        <i class="la la-desktop"></i> @lang('Manage')
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted" colspan="100%">{{ __($emptyMessage) }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($merchants->hasPages())
                <div class="card-footer py-4">
                    @php echo paginateLinks($merchants) @endphp
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder="Merchant email / username / ID" />
    <a href="{{ route('admin.rewards.levels') }}" class="btn btn-sm btn-outline--dark"><i class="las la-cog"></i> @lang('Reward Levels')</a>
@endpush
