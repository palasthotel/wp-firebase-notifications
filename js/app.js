(function(){

	window.FirebaseNotifications = function(){
		const isAndroid = (typeof AndroidAppSubscriptions !== typeof undefined);
		const isiOS = (typeof iOSNotifications !== typeof undefined);

		return {
			isAndroid,
			isiOS,
			isApp: isAndroid || isiOS,
			fn: {
				isNotificationsEnabled: async ()=>{
					if(isiOS) return iOSNotifications.isNotificationsEnabled();
					if(isAndroid) return AndroidAppSubscriptions.isNotificationsEnabled();
					console.error("No interface found. Could not check if notifications are enabled");
				},
				setNotificationsEnabled: async (setEnabled)=>{
					if(isiOS) return iOSNotifications.setNotificationsEnabled(setEnabled === true);
					if(isAndroid) return AndroidAppSubscriptions.setNotificationsEnabled(setEnabled === true);
					console.error("No interface found. Could not set notifications endabled to "+ setEnabled);
				},
				subscribe: async (topic)=>{
					if(isiOS) return iOSNotifications.subscribe(topic);
					if(isAndroid) return AndroidAppSubscriptions.subscribe(topic);
					console.error("No interface found. Could not subscribe to "+ topic);
				},
				unsubscribe: async (topic)=>{
					if(isiOS) return iOSNotifications.unsubscribe(topic);
					if(isAndroid) return AndroidAppSubscriptions.unsubscribe(topic);
					console.error("No interface found. Could not unsubscribe from "+ topic);
				},
				isSubscribed: async (topic)=>{
					if(isiOS) return iOSNotifications.isSubscribed(topic);
					if(isAndroid) return AndroidAppSubscriptions.isSubscribed(topic);
					console.error("No interface found. Could not check if is subscribed to "+ topic);
				}
			},
		}
	}();


})();