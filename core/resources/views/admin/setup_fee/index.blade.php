@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th>@lang('Merchant')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Submitted')</th>
                                <th>@lang('Reviewed')</th>
                                <th>@lang('Reason')</th>
                                <th>@lang('Action')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>
                                        <span class="fw-bold">{{ $user->fullname }}</span><br>
                                        <a href="{{ route('admin.users.detail', $user->id) }}">@{{ $user->username }}</a><br>
                                        <small>{{ $user->email }}</small>
                                    </td>
                                    <td>
                                        @if($user->setup_fee_status === 'pending_review')
                                            <span class="badge badge--warning">@lang('Pending Review')</span>
                                        @elseif($user->setup_fee_status === 'approved')
                                            <span class="badge badge--success">@lang('Approved')</span>
                                        @elseif($user->setup_fee_status === 'rejected')
                                            <span class="badge badge--danger">@lang('Rejected')</span>
                                        @else
                                            <span class="badge badge--dark">{{ $user->setup_fee_status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $user->setup_fee_submitted_at ? showDateTime($user->setup_fee_submitted_at) : '--' }}
                                    </td>
                                    <td>
                                        {{ $user->setup_fee_reviewed_at ? showDateTime($user->setup_fee_reviewed_at) : '--' }}
                                    </td>
                                    <td>
                                        <span class="small text-wrap d-inline-block" style="max-width: 260px;">
                                            {{ $user->setup_fee_rejection_reason ?: '--' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="button--group">
                                            <form action="{{ route('admin.setup.fees.approve', $user->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--success" @disabled($user->setup_fee_status === 'approved')>
                                                    <i class="las la-check"></i> @lang('Approve')
                                                </button>
                                            </form>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline--danger rejectionBtn"
                                                data-action="{{ route('admin.setup.fees.reject', $user->id) }}"
                                                data-user="@{{ $user->username }}"
                                            >
                                                <i class="las la-times"></i> @lang('Reject')
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-muted" colspan="100%">@lang('No setup fee requests found')</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($users->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($users) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectSetupFeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" class="modal-content" id="rejectSetupFeeForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Reject Setup Fee')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">@lang('Reason for rejection')</p>
                    <textarea name="reason" class="form-control" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark btn-sm" data-bs-dismiss="modal">@lang('Close')</button>
                    <button type="submit" class="btn btn--danger btn-sm">@lang('Reject')</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder="Username / Email" />
@endpush

@push('script')
    <script>
        (function ($) {
            'use strict';

            $('.rejectionBtn').on('click', function () {
                $('#rejectSetupFeeForm').attr('action', $(this).data('action'));
                $('#rejectSetupFeeModal').modal('show');
            });
        })(jQuery);
    </script>
@endpush
