@extends($activeTemplate.'layouts.master')

@section('content')
<div class="custom--card">
    <div class="card-body">
        <div class="row align-items-center mb-3">
            <div class="col-6">
                <h6 class="fs-sm-14">@lang($pageTitle)</h6>
            </div>
            <div class="col-6 text-end">
                <a href="{{ route('ticket.open') }}" class="btn btn--base btn-sm">
                    <i class="las la-plus"></i>
                    @lang('Add New')
                </a>
            </div>
        </div>
        <div class="table-responsive--sm">
            <table class="table custom--table">
                <thead>
                    <tr>
                        <th>@lang('Subject')</th>
                        <th>@lang('Status')</th>
                        <th>@lang('Priority')</th>
                        <th>@lang('Last Reply')</th>
                        <th>@lang('Action')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supports as $key => $support)
                        <tr>
                            <td> <a href="{{ route('ticket.view', $support->ticket) }}" class="fw-bold">
                                    [@lang('Ticket')#{{ $support->ticket }}] {{ __($support->subject) }} </a></td>
                            <td>
                                @php echo $support->statusBadge; @endphp
                            </td>
                            <td>
                                @if ($support->priority == 1)
                                    <span class="badge badge--dark">@lang('Low')</span>
                                @elseif($support->priority == 2)
                                    <span class="badge badge--success">@lang('Medium')</span>
                                @elseif($support->priority == 3)
                                    <span class="badge badge--primary">@lang('High')</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($support->last_reply)->diffForHumans() }} </td>
                            <td>
                                <a href="{{ route('ticket.view', $support->ticket) }}" class="btn btn-sm btn--base">
                                    <i class="la la-desktop"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100%" class="text-center not-found">{{ __($emptyMessage) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table> 
        </div>
        @if($supports->hasPages())
            <div class="pt-4 pb-2">
                {{ paginatelinks($supports) }}
            </div>
        @endif
    </div>
</div>
@endsection
