
(function(webapp, firebase){

	// -----------------------------------
	// check for required objects
	// -----------------------------------
	if(!FirebaseMessagingWebapp){
		console.error("Could not find Webapp object");
		return;
	}
	if(!firebase){
		console.error("Could not find firebase object");
		return;
	}

	const firebaseConfig = FirebaseMessagingWebapp.config;

	if(!firebaseConfig){
		console.error("Could not find firebaseConfig object");
		return;
	}

	const iconUrl = FirebaseMessagingWebapp.iconUrl;

	// -----------------------------------
	// expose listener
	// -----------------------------------
	const buildListener = function(){
		let listeners = [];
		return {
			add: function(fn){
				if(listeners.includes(fn)) return;
				listeners.push(fn);
			},
			remove: function(fn){
				if(!listeners.includes(fn)) return;
				listeners = listeners.filter(function(tmp){
					return tmp !== fn;
				});
			},
			each: function(){
				const args = arguments;
				listeners.forEach(function(fn){
					fn.apply(undefined, args);
				});
			},
		}
	};

	const onMessageListener = buildListener();

	// -----------------------------------
	// initialize FCM
	// -----------------------------------
	const onInitListener = buildListener();

	let initialized = false;
	function FCMApp(){

		if(initialized) return
		initialized = true

		console.log("initialize FCMApp");
		// -----------------------------------
		// initialize firebase
		// -----------------------------------
		firebase.initializeApp(firebaseConfig);

		// -----------------------------------
		// initialize messaging
		// -----------------------------------
		const messaging = firebase.messaging();

		let _token = null;
		function getToken(){ return _token; }

		// request permission
		messaging
			.requestPermission()
			.then(function(){
				// permission granted
				return messaging.getToken();
			})
			.then(function(token){
				// save token on server?
				_token = token;
				onInitListener.each();
			})
			.catch(function(e){
				// permission denied
				console.error("Denied messaging", e);
			});

		// wait for messages
		messaging.onMessage(function(payload){

			console.log("received message", payload.notification, payload.data);

			onMessageListener.each(payload.notification, payload.data, payload.priority, payload);

			const notification = new Notification(payload.notification.title, {
				body: payload.notification.body,
				icon: iconUrl,
			});
			notification.onclick = function(e){
				window.open(payload.data.permalink,'_blank');
			}
		});

		return {
			getToken,
		}
	}

	let _FCMAppInstance = null;
	function firebaseInstance() {
		if(!_FCMAppInstance){
			_FCMAppInstance = FCMApp();
		}
		return _FCMAppInstance;
	}

	function getToken() {
		if(!firebaseInstance()) return false;
		return firebaseInstance().getToken()
	}

	// -----------------------------------
	// topic subscription api
	// -----------------------------------
	const _isActiveValue = "1";
	function setNotificationsEnabled(isEnabled){
		if(isEnabled){
			firebaseInstance();
			localStorage.setItem("fcm-is-enabled", _isActiveValue)
		}  else {
			localStorage.removeItem("fcm-is-enabled");
		}
	}
	function isNotificationsEnabled(){
		return localStorage.getItem("fcm-is-enabled") === _isActiveValue;
	}
	function _getSubscriptionKey (topic){
		const token = getToken();
		if(!token) return "";
		return "fcm-"+token+"-is-subscribed-"+topic;
	}
	function isSubscribed (topic){ return isNotificationsEnabled() && localStorage.getItem(_getSubscriptionKey(topic)) === "1";}
	function setSubscribed (topic){ localStorage.setItem(_getSubscriptionKey(topic), _isActiveValue); }
	function setUnsubscribed (topic){ localStorage.removeItem(_getSubscriptionKey(topic)); }

	const cloudFunctionsBaseUrl = "https://us-central1-"+firebaseConfig.projectId+".cloudfunctions.net";

	async function request(action, topic){
		const token = getToken();
		if(!token) throw "No request token";
		return fetch(
			cloudFunctionsBaseUrl+"/"+action+"?token="+token+"&topic="+topic,
			{mode: 'no-cors',}
		);
	}

	function subscribe (topic){
		return request("subscribe", topic).then(function(){
			setSubscribed(topic);
		});
	}
	function unsubscribe(topic) {
		return request("unsubscribe", topic).then(function(){
			setUnsubscribed(topic);
		});
	}

	webapp.isSupported = firebase.messaging.isSupported();

	// exposed api
	webapp.api = {
		setNotificationsEnabled,
		isNotificationsEnabled,
		subscribe,
		unsubscribe,
		isSubscribed,
		onFCMInitialized: onInitListener.add,
		offFCMInitialized: onInitListener.remove,
		onMessage: onMessageListener.add,
		offMessage: onMessageListener.remove,
		getToken,
	};

	if(isNotificationsEnabled()){
		firebaseInstance();
	}


})( FirebaseMessagingWebapp, firebase);
