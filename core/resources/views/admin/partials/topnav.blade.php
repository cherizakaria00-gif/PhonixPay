@php
    $sidenav = json_decode($sidenav);

    $settings = file_get_contents(resource_path('views/admin/setting/settings.json'));
    $settings = json_decode($settings);

    $routesData = [];
    foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
        $name = $route->getName();
        if (strpos($name, 'admin') !== false) {
            $routeData = [
                $name => url($route->uri()),
            ];

            $routesData[] = $routeData;
        }
    }
@endphp

<!-- navbar-wrapper start -->
<nav class="navbar-wrapper bg--dark d-flex flex-wrap">
    <div class="navbar__left">
        <button type="button" class="res-sidebar-open-btn me-3"><i class="las la-bars"></i></button>
        <form class="navbar-search">
            <input type="search" name="#0" class="navbar-search-field" id="searchInput" autocomplete="off"
                placeholder="@lang('Search here...')">
            <i class="las la-search"></i>
            <ul class="search-list"></ul>
        </form>
    </div>
    <div class="navbar__right">
        <ul class="navbar__action-list">
            <li>
                <button type="button" class="primary--layer" data-bs-toggle="tooltip" data-bs-placement="bottom" title="@lang('Visit Website')">
                    <a href="{{ route('home') }}" target="_blank"><i class="las la-globe"></i></a>
                </button>
            </li>
            <li class="dropdown" id="adminNotificationDropdown" data-poll-url="{{ route('admin.notifications.poll') }}">
                <button type="button" class="primary--layer notification-bell" data-bs-toggle="dropdown" data-bs-display="static"
                    aria-haspopup="true" aria-expanded="false">
                    <span data-bs-toggle="tooltip" data-bs-placement="bottom" title="@lang('Unread Notifications')">
                        <i id="adminNotificationBellIcon" class="las la-bell @if($adminNotificationCount > 0) icon-left-right @endif"></i>
                    </span>
                    <span id="adminNotificationCountBadge" class="notification-count {{ $adminNotificationCount > 0 ? '' : 'd-none' }}">{{ $adminNotificationCount <= 9 ? $adminNotificationCount : '9+'}}</span>
                </button>
                <div class="dropdown-menu dropdown-menu--md p-0 border-0 dropdown-menu-right admin-notification-menu">
                    <div class="admin-notification-menu__head">
                        <h6 class="mb-0">@lang('Notifications')</h6>
                        <a id="adminNotificationMarkAllLink"
                           href="{{ route('admin.notifications.read.all') }}"
                           class="admin-notification-mark-all {{ $adminNotificationCount > 0 ? '' : 'd-none' }}">
                            @lang('Mark all')
                        </a>
                    </div>
                    <div id="adminNotificationList" class="admin-notification-menu__body">
                        @forelse($adminNotifications as $notification)
                            <a href="{{ route('admin.notification.read',$notification->id) }}"
                                class="admin-notification-item">
                                <div class="admin-notification-item__subject">{{ __($notification->title) }}</div>
                                <div class="admin-notification-item__time">
                                    <i class="far fa-clock"></i> {{ diffForHumans($notification->created_at) }}
                                </div>
                            </a>
                        @empty
                            <div class="admin-notification-empty">@lang('No unread notification found')</div>
                        @endforelse
                    </div>
                    <div class="admin-notification-menu__footer">
                        <a href="{{ route('admin.notifications') }}"
                            class="admin-notification-view-all">@lang('View all notifications')</a>
                    </div>
                </div>
            </li>
            <li>
                <button type="button" class="primary--layer" data-bs-toggle="tooltip" data-bs-placement="bottom" title="@lang('System Setting')">
                    <a href="{{ route('admin.setting.system') }}"><i class="las la-wrench"></i></a>
                </button>
            </li>
            <li class="dropdown d-flex profile-dropdown">
                <button type="button" data-bs-toggle="dropdown" data-display="static" aria-haspopup="true"
                    aria-expanded="false">
                    <span class="navbar-user">
                        <span class="navbar-user__thumb"><img src="{{ getImage(getFilePath('adminProfile').'/'. auth()->guard('admin')->user()->image,getFileSize('adminProfile'))}}" alt="image"></span>
                        <span class="navbar-user__info">
                            <span class="navbar-user__name">{{ auth()->guard('admin')->user()->username }}</span>
                        </span>
                        <span class="icon"><i class="las la-chevron-circle-down"></i></span>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu--sm p-0 border-0 box--shadow1 dropdown-menu-right">
                    <a href="{{ route('admin.profile') }}"
                        class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-user-circle"></i>
                        <span class="dropdown-menu__caption">@lang('Profile')</span>
                    </a>

                    <a href="{{ route('admin.password') }}"
                        class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-key"></i>
                        <span class="dropdown-menu__caption">@lang('Password')</span>
                    </a>

                    <a href="{{ route('admin.logout') }}" class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-sign-out-alt"></i>
                        <span class="dropdown-menu__caption">@lang('Logout')</span>
                    </a>
                </div>
                <button type="button" class="breadcrumb-nav-open ms-2 d-none">
                    <i class="las la-sliders-h"></i>
                </button>
            </li>
        </ul>
    </div>
</nav>
<!-- navbar-wrapper end -->

@push('script')
<script>
    "use strict";
    var routes = @json($routesData);
    var settingsData = Object.assign({}, @json($settings), @json($sidenav));

    $('.navbar__action-list .dropdown-menu').on('click', function(event){
        event.stopPropagation();
    });

    (function ($) {
        const $dropdown = $('#adminNotificationDropdown');
        if (!$dropdown.length) return;

        const pollUrl = $dropdown.data('poll-url');
        const $badge = $('#adminNotificationCountBadge');
        const $markAllLink = $('#adminNotificationMarkAllLink');
        const $list = $('#adminNotificationList');
        const $bellIcon = $('#adminNotificationBellIcon');
        const emptyHtml = '<div class="admin-notification-empty">' + @json(__('No unread notification found')) + '</div>';
        let lastUnreadCount = Number(@json((int) $adminNotificationCount));

        let adminAudioCtx = null;
        let adminAudioReady = false;

        function ensureAdminAudioCtx() {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return null;
            if (!adminAudioCtx) adminAudioCtx = new Ctx();
            return adminAudioCtx;
        }

        function unlockAdminAudio() {
            const ctx = ensureAdminAudioCtx();
            if (!ctx) return;
            if (ctx.state === 'suspended') {
                ctx.resume();
            }
            adminAudioReady = true;
        }

        function playAdminNotificationSound() {
            const ctx = ensureAdminAudioCtx();
            if (!ctx || !adminAudioReady) return;
            [900, 1200].forEach((freq, index) => {
                const now = ctx.currentTime + (index * 0.11);
                const oscillator = ctx.createOscillator();
                const gainNode = ctx.createGain();
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(freq, now);
                gainNode.gain.setValueAtTime(0.0001, now);
                gainNode.gain.exponentialRampToValueAtTime(0.08, now + 0.01);
                gainNode.gain.exponentialRampToValueAtTime(0.0001, now + 0.09);
                oscillator.connect(gainNode);
                gainNode.connect(ctx.destination);
                oscillator.start(now);
                oscillator.stop(now + 0.1);
            });
        }

        $(document).one('click keydown touchstart', unlockAdminAudio);

        function escapeHtml(value) {
            return $('<div/>').text(value || '').html();
        }

        function renderAdminNotifications(data) {
            const unreadCount = Number(data.unread_count || 0);

            if (unreadCount > 0) {
                $badge.text(unreadCount > 9 ? '9+' : unreadCount).removeClass('d-none');
                $markAllLink.removeClass('d-none');
                $bellIcon.addClass('icon-left-right');
            } else {
                $badge.addClass('d-none');
                $markAllLink.addClass('d-none');
                $bellIcon.removeClass('icon-left-right');
            }

            if (!Array.isArray(data.notifications) || !data.notifications.length) {
                $list.html(emptyHtml);
            } else {
                let html = '';
                data.notifications.forEach(function (notification) {
                    html += '<a href="' + escapeHtml(notification.url) + '" class="admin-notification-item">';
                    html += '<div class="admin-notification-item__subject">' + escapeHtml(notification.title) + '</div>';
                    html += '<div class="admin-notification-item__time"><i class="far fa-clock"></i> ' + escapeHtml(notification.time) + '</div>';
                    html += '</a>';
                });
                $list.html(html);
            }

            if (unreadCount > lastUnreadCount) {
                playAdminNotificationSound();
            }
            lastUnreadCount = unreadCount;
        }

        function pollAdminNotifications() {
            $.get(pollUrl, function (response) {
                if (response && response.status === 'success') {
                    renderAdminNotifications(response);
                }
            });
        }

        setInterval(pollAdminNotifications, 30000);
        pollAdminNotifications();
    })(jQuery);
</script>
<script src="{{ asset('assets/admin/js/search.js') }}"></script>
<script>
    "use strict";
    function getEmptyMessage(){
        return `<li class="text-muted">
                <div class="empty-search text-center">
                    <img src="{{ getImage('assets/images/empty_list.png') }}" alt="empty">
                    <p class="text-muted">No search result found</p>
                </div>
            </li>`
    }
</script>
@endpush
