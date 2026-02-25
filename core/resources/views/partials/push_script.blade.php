<script src="{{asset('assets/global/js/firebase/firebase-8.3.2.js')}}"></script>

<script>
    "use strict";

    var permission = null;
    var authenticated = '{{ auth()->user() ? true : false }}';
    var pushNotify = @json(gs('pn'));
    var firebaseConfig = @json(gs('firebase_config'));
    var staticFirebaseConfig = {
        apiKey: "AIzaSyCT5g8JZJbMjHhl7a9bgS_d-pv6yVWE_tA",
        authDomain: "phonixpay-6e800.firebaseapp.com",
        projectId: "phonixpay-6e800",
        storageBucket: "phonixpay-6e800.firebasestorage.app",
        messagingSenderId: "964922869148",
        appId: "1:964922869148:web:11c8d1eb94735974403459",
        measurementId: "G-LNND00MPKV"
    };
    firebaseConfig = Object.assign({}, firebaseConfig || {}, staticFirebaseConfig);

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

        navigator.serviceWorker.register("{{ asset('assets/global/js/firebase/firebase-messaging-sw.js') }}")

        .then((registration) => {
            messaging.useServiceWorker(registration);

            function initFirebaseMessagingRegistration() {
                messaging
                .requestPermission()
                .then(function () {
                    return messaging.getToken()
                })
                .then(function (token){
                    $.ajax({
                        url: '{{ route("user.add.device.token") }}',
                        type: 'POST',
                        data: {
                            token: token,
                            '_token': "{{ csrf_token() }}"
                        },
                        success: function(response){
                        },
                        error: function (err) {
                        },
                    });
                }).catch(function (error){
                });
            }

            messaging.onMessage(function (payload){
                const title = payload.notification.title;
                const options = {
                    body: payload.notification.body,
                    icon: payload.data.icon,
                    image: payload.notification.image,
                    click_action:payload.data.click_action,
                    vibrate: [200, 100, 200]
                };
                new Notification(title, options);
                playPushSound();
            });

            //For authenticated users
            if(authenticated){
                initFirebaseMessagingRegistration();
            }

        });

    }
</script>
