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

    $profileSource = trim($user?->fullname ?? $user?->username ?? '');
    $profileInitials = 'U';
    if ($profileSource !== '') {
        $parts = preg_split('/\s+/', $profileSource);
        $profileInitials = strtoupper(substr($parts[0], 0, 1));
        if (isset($parts[1])) {
            $profileInitials .= strtoupper(substr($parts[1], 0, 1));
        } else {
            $profileInitials .= strtoupper(substr($parts[0], 1, 1) ?: '');
        }
        $profileInitials = substr($profileInitials, 0, 2);
    }
@endphp

<div class="dashboard-top-nav">
    <div class="row align-items-center">
        <div class="col-3 d-lg-block d-none">
            <h5 class="page-title">{{ __($pageTitle) }}</h5>
        </div>
        <div class="col-3 d-lg-none d-block">
            <button class="sidebar-open-btn"><i class="las la-bars"></i></button>
        </div>
        <div class="col-9">
            <div class="d-flex flex-wrap justify-content-end align-items-center header-toolbar">
                @if($showHeaderBalance)
                    <div class="header-balance header-balance--cards">
                        <div class="header-balance__card">
                            <span class="header-balance__icon">
                                <i class="las la-wallet"></i>
                            </span>
                            <div class="header-balance__content">
                                <span class="header-balance__label">@lang('Balance')</span>
                                <span class="header-balance__value">{{ showAmount($headerBalance) }}</span>
                            </div>
                        </div>
                        <div class="header-balance__card">
                            <span class="header-balance__icon">
                                <i class="las la-chart-line"></i>
                            </span>
                            <div class="header-balance__content">
                                <span class="header-balance__label">@lang('Payout Available')</span>
                                <span class="header-balance__value">{{ showAmount($headerPayoutAvailable) }}</span>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="header-actions">
                    <div class="dropdown header-notification-dropdown" data-poll-url="{{ route('user.notifications.poll') }}">
                        <button
                            class="header-action-btn"
                            type="button"
                            data-bs-toggle="dropdown"
                            data-bs-display="static"
                            aria-expanded="false"
                            aria-label="@lang('Notifications')">
                            <i class="las la-bell"></i>
                            <span id="merchantNotificationCount" class="header-notification-count {{ $headerUnreadNotificationCount > 0 ? '' : 'd-none' }}">
                                {{ $headerUnreadNotificationCount > 9 ? '9+' : $headerUnreadNotificationCount }}
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-right header-notification-menu">
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
                                        <div class="header-notification-item__subject">
                                            {{ __($headerNotification->subject ?: 'Notification') }}
                                        </div>
                                        <div class="header-notification-item__message">
                                            {{ $headerNotification->preview_message }}
                                        </div>
                                        <div class="header-notification-item__time">
                                            {{ diffForHumans($headerNotification->created_at) }}
                                        </div>
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
                    <a href="{{ route('user.profile.setting') }}" class="header-profile" aria-label="@lang('Profile')">
                        {{ $profileInitials }}
                    </a>
                </div>
            </div>
        </div>
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
            let lastUnreadCount = Number(@json((int) $headerUnreadNotificationCount));

            let merchantAudioCtx = null;
            let merchantAudioReady = false;

            function ensureMerchantAudioCtx() {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return null;
                if (!merchantAudioCtx) merchantAudioCtx = new Ctx();
                return merchantAudioCtx;
            }

            function unlockMerchantAudio() {
                const ctx = ensureMerchantAudioCtx();
                if (!ctx) return;
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }
                merchantAudioReady = true;
            }

            function playMerchantNotificationSound() {
                const ctx = ensureMerchantAudioCtx();
                if (!ctx || !merchantAudioReady) return;
                [880, 1175].forEach((freq, index) => {
                    const now = ctx.currentTime + (index * 0.11);
                    const oscillator = ctx.createOscillator();
                    const gainNode = ctx.createGain();
                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(freq, now);
                    gainNode.gain.setValueAtTime(0.0001, now);
                    gainNode.gain.exponentialRampToValueAtTime(0.075, now + 0.01);
                    gainNode.gain.exponentialRampToValueAtTime(0.0001, now + 0.09);
                    oscillator.connect(gainNode);
                    gainNode.connect(ctx.destination);
                    oscillator.start(now);
                    oscillator.stop(now + 0.1);
                });
            }

            $(document).one('click keydown touchstart', unlockMerchantAudio);

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
                    html += '<div class="header-notification-item__time">' + escapeHtml(notification.time) + '</div>';
                    html += '</a>';
                });
                $list.html(html);

                if (unreadCount > lastUnreadCount) {
                    playMerchantNotificationSound();
                }
                lastUnreadCount = unreadCount;
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
