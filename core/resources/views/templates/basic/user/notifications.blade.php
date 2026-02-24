@extends($activeTemplate . 'layouts.master')

@php
    $showHeaderBalance = true;
@endphp

@section('content')
    <div class="row">
        <div class="col-12 mb-3 d-flex justify-content-end">
            <form action="{{ route('user.notifications.read.all') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline--base btn-sm">@lang('Mark all as read')</button>
            </form>
        </div>
        <div class="col-12">
            <div class="card custom--card border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Subject')</th>
                                    <th>@lang('Message')</th>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notifications as $notification)
                                    @php
                                        $rowMessage = preg_replace('/<(style|script)\b[^>]*>.*?<\/\1>/is', ' ', $notification->message ?? '');
                                        $rowMessage = strip_tags((string) $rowMessage);
                                        $rowMessage = preg_replace('/\s+/u', ' ', html_entity_decode($rowMessage, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                                    @endphp
                                    <tr>
                                        <td>{{ __($notification->subject ?: 'Notification') }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit(trim((string) $rowMessage), 90) }}</td>
                                        <td>{{ diffForHumans($notification->created_at) }}</td>
                                        <td>
                                            @if((int)$notification->user_read === 0)
                                                <span class="badge badge--warning">@lang('Unread')</span>
                                            @else
                                                <span class="badge badge--success">@lang('Read')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if((int)$notification->user_read === 0)
                                                <a href="{{ route('user.notification.read', $notification->id) }}" class="btn btn-sm btn-outline--base">
                                                    @lang('Mark as read')
                                                </a>
                                            @else
                                                <span class="text-muted">@lang('Done')</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted">{{ __('No notification found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @if($notifications->hasPages())
            <div class="col-12 mt-3">
                {{ paginatelinks($notifications) }}
            </div>
        @endif
    </div>
@endsection
