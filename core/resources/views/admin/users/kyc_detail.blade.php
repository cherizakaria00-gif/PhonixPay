@extends('admin.layouts.app')
@section('panel')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    @if($user->kyc_data)
                        <ul class="list-group">
                          @foreach($user->kyc_data as $val)
                          @continue(!$val->value)
                          <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{__($val->name)}}
                            <span>
                                @if($val->type == 'checkbox')
                                    {{ implode(',',$val->value) }}
                                @elseif($val->type == 'file')
                                    @if($val->value)
                                        <a href="{{ route('admin.download.attachment',encrypt(getFilePath('verify').'/'.$val->value)) }}"><i class="fa-regular fa-file"></i>  @lang('Attachment') </a>
                                    @else
                                        @lang('No File')
                                    @endif
                                @else
                                <p>{{__($val->value)}}</p>
                                @endif
                            </span>
                          </li>
                          @endforeach
                        </ul>
                        @else
                        <h5 class="text-center">@lang('KYC data not found')</h5>
                    @endif

                    @if($user->kv == Status::KYC_UNVERIFIED)
                    <div class="my-3">
                        <h6>@lang('Rejection Reason')</h6>
                        <p>{{ $user->kyc_rejection_reason }}</p>
                    </div>
                    @endif

                    @if($user->kv == Status::KYC_PENDING)
                    <div class="d-flex flex-wrap justify-content-end mt-3">
                        <button class="btn btn-outline--danger me-3" data-bs-toggle="modal" data-bs-target="#kycRejectionModal"><i class="las la-ban"></i>@lang('Reject')</button>
                        <button class="btn btn-outline--success approveBtn"><i class="las la-check"></i>@lang('Approve')</button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>


    <div id="kycRejectionModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Reject KYC Documents')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.users.kyc.reject', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-primary p-3">
                            @lang('If you reject these documents, the user will be able to re-submit new documents and these documents will be replaced by new documents.')
                        </div>

                        <div class="form-group">
                            <label>@lang('Rejection Reason')</label>
                            <textarea class="form-control" name="reason" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- APPROVE MODAL --}}
    <div id="approveModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Confirmation Alert!')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.users.kyc.approve', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="question">@lang('Can you confirm that you want to authorize these documents? If you do, please set up the payment fee for this merchant.')</p>
                        <hr>
                        <div class="form-group mt-3">
                            <label for="payment_fixed_charge">@lang('Payment Fixed Charge')</label>
                            <div class="input-group">
                                <input type="number" step="any" name="payment_fixed_charge" value="{{ getAmount(gs('payment_fixed_charge')) }}" class="form-control">
                                <div class="input-group-text">{{ __(gs('cur_text')) }}</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_percent_charge">@lang('Payment Percent Charge')</label>
                            <div class="input-group">
                                <input type="number" step="any" name="payment_percent_charge" value="{{ getAmount(gs('payment_percent_charge')) }}" class="form-control">
                                <div class="input-group-text">%</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('No')</button>
                        <button type="submit" class="btn btn--primary">@lang('Yes')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('script')
    <script>
        (function ($) {
            "use strict";
            $('.approveBtn').on('click', function () {
                var modal = $('#approveModal');
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush