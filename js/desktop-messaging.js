
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
	// initialize firebase
	// -----------------------------------
	firebase.initializeApp(firebaseConfig);

	// -----------------------------------
	// expose listener
	// -----------------------------------
	const buildListener = ()=>{
		let listeners = [];
		return {
			add: (fn)=>{
				if(listeners.includes(fn)) return;
				listeners.push(fn);
			},
			remove: (fn)=>{
				if(!listeners.includes(fn)) return;
				listeners = listeners.filter(function(tmp){ return tmp !== fn;});
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
	webapp.onMessage = onMessageListener.add;
	webapp.offMessage= onMessageListener.remove;


	// -----------------------------------
	// initialize messaging
	// -----------------------------------
	const messaging = firebase.messaging();

	let _token = null;
	const getToken = () => _token;

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
		})
		.catch(function(){
			// permission denied
			console.error("Denied messaging");
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

	// -----------------------------------
	// topic subscription api
	// -----------------------------------
	const _subscriptionValue = "1";
	const _getSubscriptionKey = (token, topic) => "fcm-"+token+"-is-subscribed-"+topic;
	const isSubscribed = (token, topic) => localStorage.getItem(_getSubscriptionKey(token, topic)) === "1";
	const setSubscribed = (token, topic) => localStorage.setItem(_getSubscriptionKey(token, topic), _subscriptionValue);
	const setUnsubscribed = (token, topic) => localStorage.removeItem(_getSubscriptionKey(token, topic));

	const cloudFunctionsBaseUrl = "https://us-central1-"+firebaseConfig.projectId+".cloudfunctions.net";

	const request = (action, token, topic)=> fetch(
			cloudFunctionsBaseUrl+"/"+action+"?token="+token+"&topic="+topic,
			{mode: 'no-cors',}
			);

	const subscribe = (topic)=>{
		return request("subscribe", getToken(), topic).then(function(){
			setSubscribed(getToken(), topic);
		});
	};
	const unsubscribe = (topic) => {
		return request("unsubscribe", getToken(), topic).then(function(){
			setUnsubscribed(getToken(), topic);
		});
	};

	// exposed api
	webapp.api = {
		subscribe,
		unsubscribe,
		isSubscribed: (topic) => isSubscribed(getToken(), topic),
	};


})( FirebaseMessagingWebapp, firebase);
