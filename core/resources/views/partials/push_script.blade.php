<script src="{{ asset('assets/global/js/firebase/firebase-8.3.2.js') }}"></script>
<script src="{{ asset('assets/global/js/firebase/configs.js') }}"></script>

<script>
    "use strict";

    var permission = null;
    var authenticated = '{{ auth()->user() ? true : false }}';
    var pushNotify = @json(gs('pn'));
    // Prefer server-saved config in DB; fall back to configs.js if present.
    // Never hard-code a project config here or it will silently override admin settings.
    var firebaseConfig = Object.assign({}, (typeof window.firebaseConfig === 'object' ? window.firebaseConfig : {}), @json(gs('firebase_config')) || {});

    var pushAudioCtx = null;
    var pushAudioReady = false;

    function ensurePushAudioCtx() {
        var Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return null;
        if (!pushAudioCtx) pushAudioCtx = new Ctx();
        return pushAudioCtx;
    }

    function unlockPushAudio() {
        var ctx = ensurePushAudioCtx();
        if (!ctx) return;
        if (ctx.state === 'suspended') {
            ctx.resume();
        }
        pushAudioReady = true;
    }

    function playPushSound() {
        var ctx = ensurePushAudioCtx();
        if (!ctx || !pushAudioReady) return;

        [880, 1175].forEach(function(freq, index) {
            var now = ctx.currentTime + (index * 0.11);
            var oscillator = ctx.createOscillator();
            var gainNode = ctx.createGain();
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

    document.addEventListener('click', unlockPushAudio, { once: true });
    document.addEventListener('keydown', unlockPushAudio, { once: true });
    document.addEventListener('touchstart', unlockPushAudio, { once: true });

    function hasFirebaseConfig(config) {
        if (!config || typeof config !== 'object') return false;
        return !!(config.apiKey && config.authDomain && config.projectId && config.messagingSenderId && config.appId);
    }

    function ensureEnableButton() {
        if (!authenticated) return;
        if (!pushNotify) return;
        if (!document.querySelector('.notice')) return;
        if (document.getElementById('enablePushBtn')) return;

        // Button that triggers permission prompt with a user gesture (required by browsers).
        $('.notice').append(`
            <div class="alert border border--info mt-2" role="alert">
                <div class="alert__icon d-flex align-items-center text--info">
                    <i class="fas fa-bell"></i>
                </div>
                <p class="alert__message">
                    <span class="fw-bold title">@lang('Enable push notifications')</span>
                    <br>
                    <small class="content">@lang('Click to allow browser notifications and register your device.')</small>
                </p>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn--base" id="enablePushBtn">@lang('Enable')</button>
                </div>
            </div>
        `);
    }

    function pushNotifyAction(){
        permission = Notification.permission;

        if(!('Notification' in window)){
            notify('info', 'Push notifications not available in your browser. Try Chromium.')
        }
        else if(permission === 'denied' || permission == 'default'){ //Notice for users dashboard
            $('.notice').append(`
                <div class="alert border border--warning" role="alert">
                    <div class="alert__icon d-flex align-items-center text--warning">
                        <i class="fas fa-bell"></i>
                    </div>
                    <p class="alert__message">
                        <span class="fw-bold title">@lang('Please Allow / Reset Browser Notification')</span>
                        <br>
                        <small class="content">
                            @lang('If you want to get push notification then you have to allow notification from your browser.')
                        </small>
                    </p>
                </div>
            `);
            ensureEnableButton();
        }
    }

    //If enable push notification from admin panel
    if(pushNotify == 1){
        pushNotifyAction();
    }

    //When users allow browser notification
    if(permission != 'denied' && hasFirebaseConfig(firebaseConfig)){

        //Firebase
        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();

        // IMPORTANT: Service worker must be registered at site root for full-site scope.
        navigator.serviceWorker.register("/firebase-messaging-sw.js")

        .then((registration) => {
            messaging.useServiceWorker(registration);

            function saveToken(token) {
                if (!token) return;
                $.ajax({
                    url: '{{ route("user.add.device.token") }}',
                    type: 'POST',
                    data: {
                        token: token,
                        '_token': "{{ csrf_token() }}"
                    },
                    success: function(response){
                        console.info('[push] token saved', response);
                    },
                    error: function (xhr) {
                        console.warn('[push] token save failed', xhr?.responseJSON || xhr?.responseText || xhr);
                    },
                });
            }

            async function initFirebaseMessagingRegistration() {
                try {
                    if (!('Notification' in window)) return;

                    const perm = await Notification.requestPermission();
                    console.info('[push] permission', perm);
                    if (perm !== 'granted') return;

                    const opts = {};
                    if (firebaseConfig && firebaseConfig.vapidKey) {
                        opts.vapidKey = firebaseConfig.vapidKey;
                    }
                    const token = await messaging.getToken(opts);
                    console.info('[push] token generated', token ? (token.slice(0, 10) + '...' + token.slice(-10)) : null);
                    saveToken(token);
                } catch (error) {
                    console.warn('[push] registration failed', error);
                }
            }

            messaging.onMessage(function (payload){
                const title = payload?.notification?.title || "{{ gs('push_title') ?? 'Notification' }}";
                const options = {
                    body: payload?.notification?.body || '',
                    icon: payload?.data?.icon,
                    image: payload?.notification?.image,
                    data: {
                        click_action: payload?.data?.click_action || null,
                    },
                    vibrate: [200, 100, 200]
                };
                try {
                    new Notification(title, options);
                } catch (e) {}
                playPushSound();
            });

            //For authenticated users
            if(authenticated){
                initFirebaseMessagingRegistration();
            }

            // Allow manual enable with user gesture
            $(document).on('click', '#enablePushBtn', function () {
                initFirebaseMessagingRegistration();
            });

            // Refresh token handling (older SDKs)
            try {
                if (typeof messaging.onTokenRefresh === 'function') {
                    messaging.onTokenRefresh(function () {
                        messaging.getToken().then(saveToken).catch(function (err) {
                            console.warn('[push] token refresh failed', err);
                        });
                    });
                }
            } catch (e) {}

        });

    }
</script>
