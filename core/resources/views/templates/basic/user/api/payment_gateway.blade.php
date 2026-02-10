@extends($activeTemplate.'layouts.master')

@php
    $search = request()->search;
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-md-12">
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
                                <th>@lang('Gateway')</th>
                                <th>@lang('Method')</th>
                                <th>@lang('Supported Currency')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gateways->sortBy('alias') as $gateway)
                                <tr>
                                    <td>
                                        <span class="fw-bold">{{__($gateway->name)}}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold">{{__($gateway->alias)}}</span>
                                    </td>
                                    <td>
                                        {{ $gateway->currencies->count() }}
                                    </td>
                                    <td>
                                        <a href="{{ route('user.api.payment.gateway.details', $gateway->id) }}" class="btn btn-sm btn--base">
                                            <i class="la la-desktop"></i>
                                        </a>
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
</div>
@endsection
