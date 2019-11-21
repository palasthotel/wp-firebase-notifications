(async function($,firebaseNotifications){

	if(!firebaseNotifications.isApp) return;

	const isAndroid = firebaseNotifications.isAndroid;
	const isiOS = firebaseNotifications.isiOS;
	const isWeb = firebaseNotifications.isWeb;

	const AppNotifications = firebaseNotifications.fn;

	// topics
	const $topics = $("[data-firebase-notifications-topic]");
	function updateTopics(){
		$topics.each(async function(){
			const $el = $(this);
			$el.prop(
				"checked",
				(await AppNotifications.isSubscribed(getTopic($el)))
					?"checked":""
			);
		});
	}

	// global web or android activate notifications
	const $globalNotifications = $("[data-firebase-notifications-active]");
	if(isAndroid || isWeb){
		$globalNotifications.prop("checked", (await AppNotifications.isNotificationsEnabled())? "checked": "");
		$globalNotifications.on("change", function(e){
			AppNotifications.setNotificationsEnabled($(this).is(":checked"));
			updateTopics();
		});
	} else {
		$globalNotifications.closest("[data-firebase-notifications-global]").remove();
	}

	// wait for initialization
	if(isWeb){
		FirebaseMessagingWebapp.api.onFCMInitialized(function(){
			updateTopics();
		});
	}

	// ios notification settings link
	const $globaliOS = $("[data-firebase-notifications-link]");
	if(isiOS){
		setInterval(async function(){
			const url = await iOSNotifications.getSettingsURL();
			if(url != null) $globaliOS.attr("href", url);
		}, 1000);
	} else {
		$globaliOS.closest("[data-firebase-notifications-global]").remove();
	}


	updateTopics();

	$topics.on("change", function(e){
		const $el = $(this);
		const topic = getTopic($el);
		if($el.is(":checked")){
			execute(AppNotifications.subscribe,topic);
			// AppNotifications.subscribe(topic);
			if(isAndroid || isWeb) $globalNotifications.prop("checked", "checked").trigger("change");
		} else {
			execute(AppNotifications.unsubscribe,topic);
			// AppNotifications.unsubscribe(topic);
		}

	});

	function execute(promisingFunction, topic){
		const $row = $("[data-firebase-notifications-wrapper-of=\""+topic+"\"]");
		$row.attr("data-firebase-notifications-is-loading", true);
		promisingFunction(topic).then(function(){
			$row.removeAttr("data-firebase-notifications-is-loading");
		});
	}

	function getTopic($el) {
		return $el.attr("data-firebase-notifications-topic");
	}

})(jQuery, window.FirebaseNotifications);