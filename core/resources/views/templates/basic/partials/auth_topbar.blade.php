@php
    $user = auth()->user();
    $showHeaderBalance = $showHeaderBalance ?? true;

    $headerNotifications = collect();
    $headerUnreadNotificationCount = 0;

    if ($showHeaderBalance) {
        $headerBalance = $user?->balance ?? 0;
        $headerPayoutAvailable = $headerBalance * 0.7;
        $headerPayoutAvailable = $headerPayoutAvailable < 0 ? 0 : $headerPayoutAvailable;
    }

    if ($user) {
        $headerNotifications = \App\Models\NotificationLog::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(8)
            ->get()
            ->map(function ($notification) {
                $message = preg_replace('/<(style|script)\b[^>]*>.*?<\/\1>/is', ' ', $notification->message ?? '');
                $message = strip_tags((string) $message);
                $message = preg_replace('/\s+/u', ' ', html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $notification->preview_message = \Illuminate\Support\Str::limit(trim((string) $message), 65);
                return $notification;
            });

        $headerUnreadNotificationCount = \App\Models\NotificationLog::where('user_id', $user->id)
            ->where('user_read', 0)
            ->count();
    }
@endphp

<div class="dashboard-top-nav topbar">
    <div class="topbar-left"></div>

    <div class="topbar-right">
        @if($showHeaderBalance)
            <div class="bal-chip">
                <i class="las la-wallet"></i>
                <span class="lbl">@lang('Balance')</span>
                <span class="val">{{ showAmount($headerBalance) }}</span>
            </div>

            <div class="bal-chip">
                <i class="las la-chart-line"></i>
                <span class="lbl">@lang('Payout Available')</span>
                <span class="val">{{ showAmount($headerPayoutAvailable) }}</span>
            </div>
        @endif

        <div class="dropdown header-notification-dropdown" data-poll-url="{{ route('user.notifications.poll') }}">
            <button
                class="icon-btn"
                type="button"
                data-bs-toggle="dropdown"
                data-bs-display="static"
                aria-expanded="false"
                aria-label="@lang('Notifications')">
                <i class="las la-bell"></i>
                <span id="merchantNotificationCount" class="notif-dot {{ $headerUnreadNotificationCount > 0 ? '' : 'd-none' }}">
                    {{ $headerUnreadNotificationCount > 9 ? '9+' : $headerUnreadNotificationCount }}
                </span>
            </button>
            <div class="dropdown-menu dropdown-menu-end header-notification-menu">
                <div class="header-notification-menu__head d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">@lang('Notifications')</h6>
                    <form id="merchantNotificationMarkAllForm" action="{{ route('user.notifications.read.all') }}" method="POST" class="{{ $headerUnreadNotificationCount > 0 ? '' : 'd-none' }}">
                        @csrf
                        <button type="submit" class="header-notification-mark-all">@lang('Mark all')</button>
                    </form>
                </div>

                <div id="merchantNotificationList" class="header-notification-menu__body">
                    @forelse($headerNotifications as $headerNotification)
                        <a href="{{ route('user.notification.read', $headerNotification->id) }}"
                           class="header-notification-item {{ (int)$headerNotification->user_read === 0 ? 'is-unread' : '' }}">
                            <div class="header-notification-item__subject">{{ __($headerNotification->subject ?: 'Notification') }}</div>
                            <div class="header-notification-item__message">{{ $headerNotification->preview_message }}</div>
                        </a>
                    @empty
                        <div class="header-notification-empty">@lang('No notifications yet')</div>
                    @endforelse
                </div>

                <div class="header-notification-menu__footer">
                    <a href="{{ route('user.notifications') }}">@lang('View all notifications')</a>
                </div>
            </div>
        </div>

        <a href="{{ route('user.profile.setting') }}" class="profile-chip">
            <i class="las la-user-friends"></i>
            <span>@lang('Profile')</span>
        </a>
    </div>
</div>

@push('script')
<script>
    (function ($) {
        "use strict";

        const $dropdown = $('.header-notification-dropdown');
        if (!$dropdown.length) return;

        const pollUrl = $dropdown.data('poll-url');
        const $countBadge = $('#merchantNotificationCount');
        const $list = $('#merchantNotificationList');
        const $markAllForm = $('#merchantNotificationMarkAllForm');
        const emptyHtml = '<div class="header-notification-empty">' + @json(__('No notifications yet')) + '</div>';

        function escapeHtml(value) {
            return $('<div/>').text(value || '').html();
        }

        function renderNotifications(data) {
            const unreadCount = Number(data.unread_count || 0);
            if (unreadCount > 0) {
                $countBadge.text(unreadCount > 9 ? '9+' : unreadCount).removeClass('d-none');
                $markAllForm.removeClass('d-none');
            } else {
                $countBadge.addClass('d-none');
                $markAllForm.addClass('d-none');
            }

            if (!Array.isArray(data.notifications) || !data.notifications.length) {
                $list.html(emptyHtml);
                return;
            }

            let html = '';
            data.notifications.forEach(function (notification) {
                html += '<a href="' + escapeHtml(notification.url) + '" class="header-notification-item ' + (notification.unread ? 'is-unread' : '') + '">';
                html += '<div class="header-notification-item__subject">' + escapeHtml(notification.subject) + '</div>';
                html += '<div class="header-notification-item__message">' + escapeHtml(notification.message) + '</div>';
                html += '</a>';
            });

            $list.html(html);
        }

        function loadNotifications() {
            $.get(pollUrl, function (response) {
                if (response && response.status === 'success') {
                    renderNotifications(response);
                }
            });
        }

        setInterval(loadNotifications, 30000);
        loadNotifications();
    })(jQuery);
</script>
@endpush
