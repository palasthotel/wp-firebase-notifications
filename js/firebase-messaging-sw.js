// https://firebase.google.com/docs/cloud-messaging/js/receive

// Give the service worker access to Firebase Messaging.
// Note that you can only use Firebase Messaging here, other Firebase libraries
// are not available in the service worker.
importScripts('https://www.gstatic.com/firebasejs/7.3.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/7.3.0/firebase-messaging.js');

// Initialize the Firebase app in the service worker by passing in the
// messagingSenderId.
firebase.initializeApp({
	'messagingSenderId': messagingSenderId,
});

// Retrieve an instance of Firebase Messaging so that it can handle background
// messages.
const messaging = firebase.messaging();

let lastNotificationUrl = false;

// wait for message
messaging.setBackgroundMessageHandler(function(payload) {

	// Customize notification here
	const notificationTitle = payload.title;
	lastNotificationUrl = payload.permalink;
	console.log("sw got permalink "+payload.permalink, payload);

	return self.registration
		.showNotification(notificationTitle,{
			body: payload.body,
			icon: notificationIconUrl,
		});
});


self.addEventListener('notificationclick', function(event) {
	event.notification.close(); // Android needs explicit close.
	event.waitUntil(
		clients.matchAll({type: 'window'}).then( function(windowClients){
			// Check if there is already a window/tab open with the target URL
			for (var i = 0; i < windowClients.length; i++) {
				var client = windowClients[i];
				// If so, just focus it.
				if (client.url === lastNotificationUrl && 'focus' in client) {
					return client.focus();
				}
			}
			// If not, then open the target URL in a new window/tab.
			if (clients.openWindow) {
				return clients.openWindow(lastNotificationUrl);
			}
		})
	);
});
