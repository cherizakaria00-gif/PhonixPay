@extends('admin.layouts.app')
@section('panel')


<div class="row justify-content-center">
    @if(request()->routeIs('admin.deposit.list') || request()->routeIs('admin.deposit.method'))
        <div class="col-12">
            @include('admin.deposit.widget')
        </div>
    @endif

    <div class="col-md-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                        <tr>
                            <th>@lang('Gateway | Transaction')</th>
                            <th>@lang('Initiated')</th>
                            <th>@lang('Merchant')</th>
                            <th>@lang('Customer')</th>
                            <th>@lang('Email')</th>
                            <th>@lang('Phone')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Conversion')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Action')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($deposits as $deposit)
                            @php
                                $details = $deposit->detail ? json_encode($deposit->detail) : null;
                            @endphp
                            <tr>
                                <td>
                                    @php
                                        $isStripeGateway = stripos($deposit->gateway->alias, 'stripe') !== false || stripos($deposit->gateway->name, 'stripe') !== false;
                                    @endphp
                                    @if($isStripeGateway)
                                        <span class="fw-bold">
                                            <a href="{{ appendQuery('method',@$deposit->gateway->alias) }}">{{ __($deposit->stripeAccount->name ?? 'Stripe') }}</a>
                                        </span>
                                        <br>
                                    @else
                                        <span class="fw-bold"> <a href="{{ appendQuery('method',@$deposit->gateway->alias) }}">{{ __(@$deposit->gateway->name) }}</a> </span>
                                        <br>
                                    @endif
                                     <small> {{ $deposit->trx }} </small>
                                </td>

                                <td>
                                    {{ showDateTime($deposit->created_at) }}<br>{{ diffForHumans($deposit->created_at) }}
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $deposit->user->fullname }}</span>
                                    <br>
                                    <span class="small">
                                    <a href="{{ appendQuery('search',@$deposit->user->username) }}"><span>@</span>{{ $deposit->user->username }}</a>
                                    </span>
                                </td>
                                @php
                                    $customer = $deposit->apiPayment->customer ?? null;
                                    $customerName = '';
                                    if ($customer) {
                                        $customerName = trim($customer->name ?? (($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')));
                                    }
                                    $customerEmail = $customer->email ?? null;
                                    $customerPhone = $customer->mobile ?? ($customer->phone ?? null);
                                @endphp
                                <td>{{ $customerName ?: __('N/A') }}</td>
                                <td>{{ $customerEmail ?: __('N/A') }}</td>
                                <td>{{ $customerPhone ?: __('N/A') }}</td>
                                <td>
                                   {{ showAmount($deposit->amount) }} - <span class="text-danger" title="@lang('Total charge')">{{ showAmount($deposit->totalCharge)}} </span>
                                    <br>
                                    <strong title="@lang('Amount after total charge')">
                                    {{ showAmount($deposit->amount-$deposit->totalCharge) }}
                                    </strong> 
                                </td>
                                <td>
                                   1 {{ __(gs('cur_text')) }} =  {{ showAmount($deposit->rate, currencyFormat:false) }} {{__($deposit->method_currency)}}
                                    <br>
                                    <strong>{{ showAmount($deposit->final_amount, currencyFormat:false) }} {{__($deposit->method_currency)}}</strong>
                                </td>
                                <td>
                                    @php echo $deposit->statusBadge @endphp
                                </td>
                                <td>
                                    <a href="{{ route('admin.deposit.details', $deposit->id) }}"
                                       class="btn btn-sm btn-outline--primary ms-1">
                                        <i class="la la-desktop"></i> @lang('Details')
                                    </a>
                                    @if($isStripeGateway && $deposit->status == \App\Constants\Status::PAYMENT_SUCCESS)
                                        <button class="btn btn-sm btn-outline--danger ms-1 confirmationBtn"
                                                data-question="@lang('Are you sure to refund this payment?')"
                                                data-action="{{ route('admin.deposit.refund', $deposit->id) }}">
                                            <i class="la la-undo"></i> @lang('Refund')
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
            @if($deposits->hasPages())
            <div class="card-footer py-4">
                @php echo paginateLinks($deposits) @endphp
            </div>
            @endif
        </div><!-- card end -->
    </div>
</div>
<x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form dateSearch='yes' placeholder='Username / TRX' />
    <x-export />
@endpush
