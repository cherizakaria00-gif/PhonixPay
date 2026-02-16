@extends('admin.layouts.app')

@section('panel')
<div class="row justify-content-center">
    @if(request()->routeIs('admin.withdraw.data.all') || request()->routeIs('admin.withdraw.method'))
    <div class="col-12">
        @include('admin.withdraw.widget')
    </div>
    @endif
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-0">

                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th>@lang('Gateway | Transaction')</th>
                                <th>@lang('Payout Date')</th>
                                <th>@lang('Initiated')</th>
                                <th>@lang('Merchant')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Conversion')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Action')</th>

                            </tr>
                        </thead>
                        <tbody>
                            @forelse($withdrawals as $withdraw)
                            @php
                            $details = ($withdraw->withdraw_information != null) ? json_encode($withdraw->withdraw_information) : null;
                            @endphp
                            <tr>
                                <td>
                                    <span class="fw-bold"><a href="{{ appendQuery('method',@$withdraw->method->id) }}"> {{ __(@$withdraw->method->name) }}</a></span>
                                    <br>
                                    <small>{{ $withdraw->trx }}</small>
                                </td>
                                <td>
                                    {{ showDateTime($withdraw->payout_date ?? $withdraw->created_at, 'd M Y') }}
                                </td>
                                <td>
                                    {{ showDateTime($withdraw->created_at) }} <br>  {{ diffForHumans($withdraw->created_at) }}
                                </td>

                                <td>
                                    <span class="fw-bold">{{ $withdraw->user->fullname }}</span>
                                    <br>
                                    <span class="small"> <a href="{{ appendQuery('search',@$withdraw->user->username) }}"><span>@</span>{{ $withdraw->user->username }}</a> </span>
                                </td>


                                <td>
                                   {{ showAmount($withdraw->amount ) }} - <span class="text-danger" title="@lang('charge')">{{ showAmount($withdraw->charge)}} </span>
                                    <br>
                                    <strong title="@lang('Amount after charge')">
                                    {{ showAmount($withdraw->amount-$withdraw->charge) }}
                                    </strong>

                                </td>

                                <td>
                                   1 {{ __(gs('cur_text')) }} =  {{ showAmount($withdraw->rate, currencyFormat:false) }} {{ __($withdraw->currency) }}
                                    <br>
                                    <strong>{{ showAmount($withdraw->final_amount, currencyFormat:false) }} {{ __($withdraw->currency) }}</strong>
                                </td>

                                <td>
                                    @php echo $withdraw->statusBadge @endphp
                                </td>
                                <td>
                                    <a href="{{ route('admin.withdraw.data.details', $withdraw->id) }}" class="btn btn-sm btn-outline--primary ms-1">
                                        <i class="la la-desktop"></i> @lang('Details')
                                    </a>
                                    @if($withdraw->status == Status::PAYMENT_PENDING)
                                        <button type="button" class="btn btn-sm btn-outline--success ms-1 withdraw-approve-btn"
                                                data-id="{{ $withdraw->id }}"
                                                data-amount="{{ showAmount($withdraw->final_amount, currencyFormat:false) }}"
                                                data-currency="{{ $withdraw->currency }}">
                                            <i class="la la-check"></i> @lang('Approve')
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline--danger ms-1 withdraw-reject-btn"
                                                data-id="{{ $withdraw->id }}">
                                            <i class="la la-ban"></i> @lang('Reject')
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                            </tr>
                            @endforelse

                        </tbody>
                    </table><!-- table end -->
                </div>
            </div>
            @if ($withdrawals->hasPages())
            <div class="card-footer py-4">
                {{ paginateLinks($withdrawals) }}
            </div>
            @endif
        </div><!-- card end -->
    </div>
</div>

{{-- APPROVE MODAL --}}
<div id="approveModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Approve Withdrawal Confirmation')</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form action="{{ route('admin.withdraw.data.approve') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="approve_withdraw_id">
                <div class="modal-body">
                    <p>@lang('Have you sent') <span class="fw-bold text--success" id="approve_amount_label">--</span>?</p>
                    <textarea name="details" class="form-control" rows="3" placeholder="@lang('Provide the details. eg: transaction number')"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- REJECT MODAL --}}
<div id="rejectModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Reject Withdrawal Confirmation')</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form action="{{route('admin.withdraw.data.reject')}}" method="POST">
                @csrf
                <input type="hidden" name="id" id="reject_withdraw_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>@lang('Reason of Rejection')</label>
                        <textarea name="details" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                </div>
            </form>
        </div>
    </div>
</div>



@endsection

@push('breadcrumb-plugins')
<x-search-form dateSearch='yes' placeholder='Username / TRX' />
<x-export />
@endpush

@push('script')
<script>
    (function ($) {
        "use strict";

        $('.withdraw-approve-btn').on('click', function () {
            const id = $(this).data('id');
            const amount = $(this).data('amount');
            const currency = $(this).data('currency');
            $('#approve_withdraw_id').val(id);
            $('#approve_amount_label').text(amount + ' ' + currency);
            $('#approveModal').modal('show');
        });

        $('.withdraw-reject-btn').on('click', function () {
            const id = $(this).data('id');
            $('#reject_withdraw_id').val(id);
            $('#rejectModal').modal('show');
        });
    })(jQuery);
</script>
@endpush
