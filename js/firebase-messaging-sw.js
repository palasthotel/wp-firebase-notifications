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

// TODO: customize notification
messaging.setBackgroundMessageHandler(function(payload) {

	// Customize notification here
	const notificationTitle = payload.title;
	const notificationOptions = {
		body: payload.body,
		icon: '/firebase-logo.png'
	};

	return self.registration.showNotification(
		notificationTitle,
		notificationOptions
	);
});