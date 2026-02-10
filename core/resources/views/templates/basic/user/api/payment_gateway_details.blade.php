@extends($activeTemplate.'layouts.master')

@php
    $search = request()->search;
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="custom--card">
        <div class="card-body">
            <div class="row align-items-center mb-3">
                <div class="col-8">
                    <h6>{{__($pageTitle)}}</h6>
                </div> 
            </div>
            <div class="table-responsive--md">
                <table class="table custom--table">
                    <thead>
                        <tr>
                            <th>@lang('Name')</th>
                            <th>@lang('Currency')</th>
                            <th>@lang('Payment Limit')</th>
                            <th>@lang('Payment Charge')</th>
                        </tr>
                    </thead> 
                    <tbody>
                        @forelse($gateway->currencies as $currency)
                            <tr>
                                <td>
                                    <span class="fw-bold">{{ __(@$currency->name) }}</span>
                                </td>
                                <td>
                                    {{ @$currency->currency }}
                                </td>
                                <td>
                                   {{ $general->cur_sym }}{{ showAmount($currency->min_amount) }} - {{ $general->cur_sym }}{{ showAmount($currency->max_amount) }}
                                </td>
                                <td>
                                    {{ $general->cur_sym }}{{ showAmount($currency->fixed_charge) }} + {{ showAmount($currency->percent_charge) }}%
                                </td>
                            </tr>
                        @empty
                            <x-empty-message table="{{ true }}" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- custom--card end -->  
</div>
@endsection

