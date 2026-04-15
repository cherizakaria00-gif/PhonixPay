/* global importScripts, firebase, firebaseConfig, clients */

// Must live at site root for full-site scope.
importScripts('/assets/global/js/firebase/firebase-8.3.2.js');
importScripts('/assets/global/js/firebase/configs.js');

if (typeof firebaseConfig !== 'object' || !firebaseConfig || !firebaseConfig.messagingSenderId) {
  // eslint-disable-next-line no-console
  console.warn('[firebase-messaging-sw] Missing firebaseConfig; background notifications disabled');
} else {
  try {
    firebase.initializeApp(firebaseConfig);
  } catch (e) {
    // ignore "already exists"
  }

  const messaging = firebase.messaging();

  // Firebase v8 background handler.
  messaging.setBackgroundMessageHandler(function (payload) {
    const title = payload?.notification?.title || 'Notification';
    const body = payload?.notification?.body || '';
    const icon = payload?.data?.icon || '/assets/images/logo_icon/favicon.png';
    const image = payload?.notification?.image;
    const clickAction = payload?.data?.click_action || payload?.fcmOptions?.link || '/';

    const options = {
      body,
      icon,
      image,
      data: { click_action: clickAction },
    };

    return self.registration.showNotification(title, options);
  });

  self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const url = event.notification?.data?.click_action || '/';

    event.waitUntil(
      clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i];
          if (client && 'focus' in client) {
            return client.focus();
          }
        }
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
    );
  });
}

