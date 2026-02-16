@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two custom-data-table">
                            <thead>
                            <tr>
                                <th>@lang('Name')</th>
                                <th>@lang('Publishable Key')</th>
                                <th>@lang('Secret Key')</th>
                                <th>@lang('Min Amount')</th>
                                <th>@lang('Max Amount')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Action')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($accounts as $account)
                                <tr>
                                    <td>{{ __($account->name) }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($account->publishable_key, 18) }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($account->secret_key, 18) }}</td>
                                    <td>{{ getAmount($account->min_amount) }}</td>
                                    <td>{{ $account->max_amount > 0 ? getAmount($account->max_amount) : __('No limit') }}</td>
                                    <td>@php echo $account->statusBadge; @endphp</td>
                                    <td>
                                        <div class="button--group">
                                            <a href="{{ route('admin.stripe.accounts.edit', $account->id) }}" class="btn btn-sm btn-outline--primary">
                                                <i class="la la-pencil"></i>@lang('Edit')
                                            </a>
                                            @if($account->is_active)
                                                <button class="btn btn-sm btn-outline--danger ms-1 confirmationBtn"
                                                        data-question="@lang('Are you sure to disable this account?')"
                                                        data-action="{{ route('admin.stripe.accounts.status', $account->id) }}">
                                                    <i class="la la-eye-slash"></i>@lang('Disable')
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline--success ms-1 confirmationBtn"
                                                        data-question="@lang('Are you sure to enable this account?')"
                                                        data-action="{{ route('admin.stripe.accounts.status', $account->id) }}">
                                                    <i class="la la-eye"></i>@lang('Enable')
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.stripe.accounts.create') }}" class="btn btn--primary">
        <i class="las la-plus"></i>@lang('Add New')
    </a>
@endpush
