@extends('admin.layouts.app')
@section('panel')
<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                        <tr>
                            <th>@lang('Link')</th>
                            <th>@lang('Merchant')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Description')</th>
                            <th>@lang('Expires')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Action')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($paymentLinks as $link)
                            @php
                                $linkUrl = route('payment.link.show', $link->code);
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ $linkUrl }}" target="_blank">{{ strLimit($link->code, 18) }}</a>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $link->user->fullname }}</span>
                                    <br>
                                    <span class="small">
                                        <a href="{{ appendQuery('search', $link->user->username) }}"><span>@</span>{{ $link->user->username }}</a>
                                    </span>
                                </td>
                                <td>{{ showAmount($link->amount, currencyFormat:false) }} {{ __($link->currency) }}</td>
                                <td>{{ $link->description ?: __('N/A') }}</td>
                                <td>
                                    @if($link->expires_at)
                                        {{ showDateTime($link->expires_at) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>@php echo $link->statusBadge; @endphp</td>
                                <td>
                                    @if($link->deposit_id)
                                        <a href="{{ route('admin.deposit.details', $link->deposit_id) }}" class="btn btn-sm btn-outline--primary">
                                            <i class="la la-desktop"></i> @lang('Details')
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage ?? 'No payment links found') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table><!-- table end -->
                </div>
            </div>
            @if($paymentLinks->hasPages())
            <div class="card-footer py-4">
                @php echo paginateLinks($paymentLinks) @endphp
            </div>
            @endif
        </div><!-- card end -->
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder='Code / Merchant' />
@endpush
