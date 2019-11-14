(async function($,firebaseNotifications){

	if(!firebaseNotifications.isApp) return;

	const isAndroid = firebaseNotifications.isAndroid;
	const isiOS = firebaseNotifications.isiOS;
	const isWeb = firebaseNotifications.isWeb;

	const AppNotifications = firebaseNotifications.fn;

	// global android activate notifications
	const $globalAndroid = $("[data-firebase-notifications-active]");
	if(isAndroid){
		$globalAndroid.prop("checked", (await AppNotifications.isNotificationsEnabled())? "checked": "");
		$globalAndroid.on("change", function(e){
			AppNotifications.setNotificationsEnabled($(this).is(":checked"));
		});
	} else {
		$globalAndroid.closest("[data-firebase-notifications-global]").remove();
	}

	// ios notification settings link
	const $globaliOS = $("[data-firebase-notifications-link]");
	if(isiOS){
		setInterval(async ()=>{
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
			if(isAndroid) $globalAndroid.prop("checked", "checked").trigger("change");
		} else {
			AppNotifications.unsubscribe(topic);
		}

	});

	function getTopic($el) {
		return $el.attr("data-firebase-notifications-topic");
	}

})(jQuery, window.FirebaseNotifications);