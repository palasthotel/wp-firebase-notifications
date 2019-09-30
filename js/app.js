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
					return AndroidAppSubscriptions.isNotificationsEnabled();
				},
				setNotificationsEnabled: async (setEnabled)=>{
					if(isiOS) return iOSNotifications.setNotificationsEnabled(setEnabled === true);
					return AndroidAppSubscriptions.setNotificationsEnabled(setEnabled === true);
				},
				subscribe: async (topic)=>{
					if(isiOS) return iOSNotifications.subscribe(topic);
					return AndroidAppSubscriptions.subscribe(topic);
				},
				unsubscribe: async (topic)=>{
					if(isiOS) return iOSNotifications.unsubscribe(topic);
					return AndroidAppSubscriptions.unsubscribe(topic);
				},
				isSubscribed: async (topic)=>{
					if(isiOS) return iOSNotifications.isSubscribed(topic);
					return AndroidAppSubscriptions.isSubscribed(topic);
				}
			},
		}
	}();


})();