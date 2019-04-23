(async function($){

	const isAndroid = (typeof AndroidNotifications !== typeof undefined);
	const isiOS = (typeof iOSNotifications !== typeof undefined);

	if(!isAndroid && !isiOS) return;

	const AppNotifications = {
		isNotificationsEnabled: async ()=>{
			if(isiOS) return iOSNotifications.isNotificationsEnabled();
			return AndroidNotifications.isNotificationsActive();
		},
		setNotificationsEnabled: async (setEnabled)=>{
			if(isiOS) return iOSNotifications.setNotificationsEnabled(setEnabled === true);
			return AndroidNotifications.setNotifications(setEnabled === true);
		},
		subscribe: async (topic)=>{
			if(isiOS) return iOSNotifications.subscribe(topic);
			return AndroidNotifications.subscribeTo(topic);
		},
		unsubscribe: async (topic)=>{
			if(isiOS) return iOSNotifications.unsubscribe(topic);
			return AndroidNotifications.unsubscribeFrom(topic);
		},
		isSubscribed: async (topic)=>{
			if(isiOS) return iOSNotifications.isSubscribed(topic);
			return AndroidNotifications.isSubscriptionActive(topic);
		}
	};

	// global android activate notifications
	const $globalAndroid = $("[data-firebase-notifications-active]");
	if(isAndroid){
		$globalAndroid.prop("checked", (await AppNotifications.isNotificationsEnabled())? "checked": "")
		$globalAndroid.on("change", function(e){
			AppNotifications.setNotificationsEnabled($(this).is(":checked"));
		});
	} else {
		$globalAndroid.closest("[data-firebase-notifications-global]").remove();
	}

	// ios notification settings link
	const $globaliOS = $("[data-firebase-notifications-link]");
	if(isiOS){
		setTimeout(async ()=>{
			const url = await iOSNotifications.getSettingsURL();
			if(url != null) $globaliOS.attr("href", url);
		}, 1000);
	} else {
		$globaliOS.closest("[data-firebase-notifications-global]").remove();
	}

	const $topics = $("[data-firebase-notifications-topic]");
	$topics.each(async function(){
		const $el = $(this);
		$el.prop(
			"checked",
			(await AppNotifications.isSubscribed(getTopic($el)))
				?"checked":""
		);
	});
	$topics.on("change", function(e){
		const $el = $(this);
		const topic = getTopic($el);
		if($el.is(":checked")){
			AppNotifications.subscribe(topic);
		} else {
			AppNotifications.unsubscribe(topic);
		}

	});

	function getTopic($el) {
		return $el.attr("data-firebase-notifications-topic");
	}

})(jQuery);