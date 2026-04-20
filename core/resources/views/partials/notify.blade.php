<link href="{{ asset('assets/global/css/iziToast.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/global/css/iziToast_custom.css') }}" rel="stylesheet">
<script src="{{ asset('assets/global/js/iziToast.min.js') }}"></script>

<script>
    "use strict";
    const colors = {
        success: '#28c76f',
        error: '#eb2222',
        warning: '#87c5a6',
        info: '#1e9ff2',
    }

    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-times-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-exclamation-circle',
    }

    const notifications = @json(session('notify', []));
    const errors = @json(@$errors ? collect($errors->all())->unique() : []);

    // Suppress duplicate toasts fired back-to-back (double-clicks, multiple handlers).
    const recentToasts = new Map();
    const TOAST_DEDUPE_WINDOW_MS = 1500;

    const shouldShowToast = (status, message) => {
        const key = `${status}:${String(message)}`;
        const now = Date.now();
        const last = recentToasts.get(key) || 0;
        if (now - last < TOAST_DEDUPE_WINDOW_MS) {
            return false;
        }
        recentToasts.set(key, now);
        return true;
    };

    const triggerToaster = (status, message) => {
        if (!shouldShowToast(status, message)) {
            return;
        }
        iziToast[status]({
            title: status.charAt(0).toUpperCase() + status.slice(1),
            message: message,
            position: "topRight",
            backgroundColor: '#fff',
            icon: icons[status],
            iconColor: colors[status],
            progressBarColor: colors[status],
            titleSize: '1rem',
            messageSize: '1rem',
            titleColor: '#474747',
            messageColor: '#a2a2a2',
            transitionIn: 'obunceInLeft'
        });
    }

    if (notifications.length) {
        // De-dupe identical notifications emitted by backend.
        const uniqueNotifications = new Set();
        notifications.forEach(element => {
            const status = element?.[0];
            const message = element?.[1];
            const key = `${status}:${String(message)}`;
            if (uniqueNotifications.has(key)) return;
            uniqueNotifications.add(key);
            triggerToaster(status, message);
        });
    }

    if (errors.length) {
        errors.forEach(error => {
            triggerToaster('error', error);
        });
    }

    function notify(status, message) {
        if (typeof message == 'string') {
            triggerToaster(status, message);
        } else {
            $.each(message, (i, val) => triggerToaster(status, val));
        }
    }
</script>
