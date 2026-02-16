@extends($activeTemplate . 'layouts.master')

@php
    $request = request();
@endphp 

@section('content')
<div class="row justify-content-center gy-4">
    <div class="col-12">
        <div class="page-heading mb-4">
            <h3 class="mb-2">{{ __($pageTitle) }}</h3>
            <p>
                @lang('Take control of your earnings with our user-friendly withdraw page, featuring up-to-date information on your balance, next payout date, and withdrawal history. Stay informed and on top of your finances with ease')
            </p>
        </div>
        <hr>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 withdraw-detail-border">
                        <div class="withdraw-detail">
                            <h3 class="text-muted title">@lang('Your current balance is') 
                                <span class="text--success withdraw-detail__balance">
                                    {{ showAmount($user->balance) }}
                                </span>
                                <br>
                                @if(@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)
                                @lang('Available for payout') <span class="text--success withdraw-detail__balance">{{ showAmount($user->balance) }}</span>
                                @else
                                <p class="mt-2 withdraw-detail__desc">
                                    @lang('Please, setup the payout method for withdrawals.')
                                </p>
                                @endif
                            </h3>
                            @if(@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)
                                @if($hasPendingWithdraw)
                                    <h4 class="text-muted mt-3 withdraw-detail__desc">@lang('Next payout') :
                                        <span class="text--primary">@lang('Pending approval')</span>
                                    </h4>
                                @else
                                    <h4 class="text-muted mt-3 withdraw-detail__desc">@lang('Next payout date') :
                                        <span class="text--primary">{{ showDateTime($nextPayoutDate, 'd M') }}</span>
                                    </h4>
                                @endif
                                <div class="mt-3 payout-request">
                                    <form action="{{ route('user.withdraw.request') }}" method="post">
                                        @csrf
                                        <div class="payout-request__group">
                                            <div class="payout-request__amount">
                                                <label class="form-label mb-1">@lang('Payout Amount')</label>
                                                <input
                                                    type="number"
                                                    step="any"
                                                    name="amount"
                                                    class="form-control form--control"
                                                    placeholder="@lang('Enter amount')"
                                                    value="{{ old('amount', getAmount(@$user->withdrawSetting->amount)) }}"
                                                    min="{{ getAmount(@$user->withdrawSetting->withdrawMethod->min_limit) }}"
                                                    max="{{ getAmount(@$user->withdrawSetting->withdrawMethod->max_limit) }}"
                                                    @disabled($hasPendingWithdraw || !$canRequestPayout)
                                                >
                                            </div>
                                            <div class="payout-request__action">
                                                <button class="btn btn--primary btn-sm" @disabled($hasPendingWithdraw || !$canRequestPayout)>
                                                    {{ $hasPendingWithdraw ? __('Payout Pending') : __('Request Payout') }}
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-muted payout-request__hint">
                                            @lang('Min') {{ showAmount(@$user->withdrawSetting->withdrawMethod->min_limit) }} /
                                            @lang('Max') {{ showAmount(@$user->withdrawSetting->withdrawMethod->max_limit) }}
                                        </small>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-end mb-3">
                            <div class="dropdown manage-payouts">
                                <button class="btn btn--primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @lang('Manage payouts')
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('user.withdraw.method') }}">
                                            @lang('Add Withdraw Method')
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="withdraw-method">
                            <h5 class="title mb-2">@lang('Payout Method')</h5>
                            @if(@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)
                                @if(@$user->withdrawSetting->withdrawMethod->image)
                                   <div class="withdraw-method-image">
                                        <img 
                                        src="{{ getImage(getFilePath('withdrawMethod').'/'. @$user->withdrawSetting->withdrawMethod->image,getFileSize('withdrawMethod'))}}" 
                                        alt="@lang('Image')" 
                                        class="w-25"
                                    >
                                   </div>
                                @endif
                            @else 
                                <h6 class="mt-2 text-muted withdraw-detail__desc">@lang('You\'ve no payout method')</h6>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mt-5">
            <div class="card custom--card border-0">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">@lang('Payout History')</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($withdraws as $withdraw)
                                    <tr>
                                        <td>{{ showDateTime(@$withdraw->created_at, 'd M Y') }}</td>
                                        <td><strong>{{ showAmount(@$withdraw->amount) }}</strong></td>
                                        <td>@php echo $withdraw->statusBadge @endphp</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __('Data not found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div><!-- custom--card end -->
        </div>

        <div class="col-12">
            <div class="mt-3">
                @if ($withdraws->hasPages())
                    {{ paginatelinks($withdraws) }}
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('style')
<style>
    .border-line-area.style-two{
        text-align: center;
        position: relative;
        z-index: 1;
    }
    .border-line-area.style-two .border-line-title {
        display: inline-block;
        margin-bottom: 0 !important;
        background: #fff;
        padding: 10px;
        padding-bottom: 5px;
    }
    .border-line-title-wrapper {
        position: relative;
    }
    .border-line-title-wrapper::before {
        position: absolute;
        content: "";
        width: 100%;
        height: 0.1px;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        background-color: #e5e5e5;
        z-index: -1;
    }

    .withdraw-detail__balance {
        font-size: 36px; 
    }
    .withdraw-detail-border {
        border-right: 1px solid #dee2e6; 
    }
    @media (max-width: 1399px) {
        .withdraw-detail__desc {
            font-weight: 500;
            font-size: 17px
        }
        .text-muted.title {
            font-size: 20px;
        }
        .withdraw-detail__balance {
            font-size: 32px; 
        }
    }
    @media (max-width: 767px) {
        .withdraw-detail-border {
            border-right: 0; 
            border-bottom: 1px solid #dee2e6!important; 
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .withdraw-detail__desc {
            font-size: 16px
        }
    }
    .withdraw-method-image img{
        max-width: 80px;
        max-height: 80px;
    }
    .payout-request__group {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
    }
    .payout-request__amount {
        flex: 1 1 220px;
    }
    .payout-request__amount .form--control {
        height: 44px;
    }
    .payout-request__action .btn {
        height: 44px;
        padding-inline: 20px;
    }
    .payout-request__hint {
        display: inline-block;
        margin-top: 8px;
    }
    .manage-payouts .dropdown-menu {
        min-width: 210px;
    }


</style>
@endpush
