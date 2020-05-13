(function(web, android, ios){

	window.FirebaseNotifications = function(){
		const isAndroid = (typeof android !== typeof undefined);
		const isiOS = (typeof ios !== typeof undefined);
		const isWeb = (typeof web !== typeof undefined && typeof web.api !== typeof undefined);
		return {
			isAndroid,
			isiOS,
			isWeb,
			isApp: isAndroid || isiOS || isWeb,
			fn: {
				isNotificationsEnabled: function(){
					if(isiOS) return ios.isNotificationsEnabled();
					if(isAndroid) return android.isNotificationsEnabled();
					if(isWeb) return web.api.isNotificationsEnabled(); // browser handles it?
					console.error("No interface found. Could not check if notifications are enabled");
				},
				setNotificationsEnabled: function(setEnabled){
					if(isiOS) return ios.setNotificationsEnabled(setEnabled === true);
					if(isAndroid) return android.setNotificationsEnabled(setEnabled === true);
					if(isWeb) return web.api.setNotificationsEnabled(setEnabled); // browser handles it?
					console.error("No interface found. Could not set notifications endabled to "+ setEnabled);
				},
				subscribe: function(topic){
					if(isiOS) return ios.subscribe(topic);
					if(isAndroid) return android.subscribe(topic);
					if(isWeb) return web.api.subscribe(topic);
					console.error("No interface found. Could not subscribe to "+ topic);
				},
				unsubscribe: function(topic){
					if(isiOS) return ios.unsubscribe(topic);
					if(isAndroid) return android.unsubscribe(topic);
					if(isWeb) return web.api.unsubscribe(topic);
					console.error("No interface found. Could not unsubscribe from "+ topic);
				},
				isSubscribed: function(topic){
					if(isiOS) return ios.isSubscribed(topic);
					if(isAndroid) return android.isSubscribed(topic);
					if(isWeb) return web.api.isSubscribed(topic);
					console.error("No interface found. Could not check if is subscribed to "+ topic);
				}
			},
		}
	}();


})(
	window.FirebaseMessagingWebapp,
	window.AndroidAppSubscriptions,
	window.iOSNotifications
);