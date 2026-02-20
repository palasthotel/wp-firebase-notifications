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
			isSupported: function(){
				return isiOS || isAndroid || (isWeb && web.isSupported);
			},
			fn: {
				isNotificationsEnabled: function(){
					return new Promise(function(resolve, reject){
						// iOS has a more convoluted approach, so the status is differentiated between:
						//   open, allowed, denied
						if (isiOS) {
							ios.getNotificationsStatus()
								.then(status => {
									if ('allowed' === status) {
										resolve(true);
									} else {
										resolve(false);
									}
								})
								.catch(err => {
									console.error("Problem getting notification status from iOS", err)
								})
						}
						else if (isAndroid) resolve(android.isNotificationsEnabled());
						else if (isWeb) resolve(web.api.isNotificationsEnabled()); // browser handles it?
						else {
							console.error("No interface found. Could not check if notifications are enabled");
							reject();
						}
					})
				},
				setNotificationsEnabled: function(setEnabled){
					return new Promise(function(resolve, reject){
						if (isiOS) {
							ios.getNotificationsStatus()
								.then(status => {
									if ('open' === status) {
										ios.requestNotificationsPermission() // Promise which resolves to one of => open, allowed, denied
											.then(newStatus => {
												resolve(newStatus);
											});
									} else {
										resolve(ios.openSettingsPage());
									}
								})
								.catch(err => {
									console.error("Problem getting notification status from iOS", err)
								})
						}
						else if(isAndroid) resolve(android.setNotificationsEnabled(setEnabled === true));
						else if(isWeb) resolve(web.api.setNotificationsEnabled(setEnabled)); // browser handles it?
						else {
							console.error("No interface found. Could not set notifications endabled to "+ setEnabled);
							reject();
						}
					})
				},
				subscribe: function(topic){
					return new Promise(function(resolve, reject){
						if(isiOS) resolve(ios.subscribe(topic));
						else if(isAndroid) resolve(android.subscribe(topic));
						else if(isWeb) resolve(web.api.subscribe(topic));
						else {
							console.error("No interface found. Could not subscribe to "+ topic);
							reject();
						}
					})

				},
				unsubscribe: function(topic){
					return new Promise(function(resolve, reject){
						if(isiOS) resolve(ios.unsubscribe(topic));
						else if(isAndroid) resolve(android.unsubscribe(topic));
						else if(isWeb) resolve(web.api.unsubscribe(topic));
						else {
							console.error("No interface found. Could not unsubscribe from "+ topic);
							reject();
						}
					})
				},
				isSubscribed: function(topic){
					return new Promise(function(resolve, reject){
						if(isiOS) resolve(ios.isSubscribed(topic));
						else if(isAndroid) resolve(android.isSubscribed(topic));
						else if(isWeb) resolve(web.api.isSubscribed(topic));
						else {
							console.error("No interface found. Could not check if is subscribed to "+ topic);
							reject();
						}
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