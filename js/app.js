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
					return new Promise(function(resolve){
						if(isiOS) resolve(ios.isNotificationsEnabled());
						else if(isAndroid) resolve(android.isNotificationsEnabled());
						else if(isWeb) resolve(web.api.isNotificationsEnabled()); // browser handles it?
						else console.error("No interface found. Could not check if notifications are enabled");
					})
				},
				setNotificationsEnabled: function(setEnabled){
					return new Promise(function(resolve){
						if(isiOS) resolve(ios.setNotificationsEnabled(setEnabled === true));
						else if(isAndroid) resolve(android.setNotificationsEnabled(setEnabled === true));
						else if(isWeb) resolve(web.api.setNotificationsEnabled(setEnabled)); // browser handles it?
						else console.error("No interface found. Could not set notifications endabled to "+ setEnabled);
					})
				},
				subscribe: function(topic){
					return new Promise(function(resolve){
						if(isiOS) resolve(ios.subscribe(topic));
						else if(isAndroid) resolve(android.subscribe(topic));
						else if(isWeb) resolve(web.api.subscribe(topic));
						else console.error("No interface found. Could not subscribe to "+ topic);
					})

				},
				unsubscribe: function(topic){
					return new Promise(function(resolve){
						if(isiOS) resolve(ios.unsubscribe(topic));
						if(isAndroid) resolve(android.unsubscribe(topic));
						if(isWeb) resolve(web.api.unsubscribe(topic));
						else console.error("No interface found. Could not unsubscribe from "+ topic);
					})
				},
				isSubscribed: function(topic){
					return new Promise(function(resolve){
						if(isiOS) resolve(ios.isSubscribed(topic));
						else if(isAndroid) resolve(android.isSubscribed(topic));
						else if(isWeb) resolve(web.api.isSubscribed(topic));
						else console.error("No interface found. Could not check if is subscribed to "+ topic);
					})
				}
			},
		}
	}();


})(
	window.FirebaseMessagingWebapp,
	window.AndroidAppSubscriptions,
	window.iOSNotifications
);