jQuery(function($){

	const firebaseNotifications = window.FirebaseNotifications;

	if(!firebaseNotifications.isApp) return;

	const isAndroid = firebaseNotifications.isAndroid;
	const isiOS = firebaseNotifications.isiOS;
	const isWeb = firebaseNotifications.isWeb;

	const AppNotifications = firebaseNotifications.fn;

	// topics
	const $topics = $("[data-firebase-notifications-topic]");
	function updateTopics(){
		$topics.each(function(){
			const $el = $(this);
			AppNotifications.isSubscribed(getTopic($el)).then(function(value){
				$el.prop("checked", value ? "checked":"");
			});
		});
	}

	// sync topics interval
	let updateInterval = null;
	function restartUpdateInterval(){
		clearInterval(updateInterval);
		updateInterval = setInterval(function(){
			updateTopics();
		}, 3000);
	}
	restartUpdateInterval();

	// global web or android activate notifications
	const $globalNotifications = $("[data-firebase-notifications-active]");
	if(isAndroid || isWeb){
		AppNotifications.isNotificationsEnabled().then(function(value){
			$globalNotifications.prop("checked", value ? "checked": "");
		})
		$globalNotifications.on("change", function(e){
			AppNotifications.setNotificationsEnabled($(this).is(":checked"));
			restartUpdateInterval();
		});
	} else {
		$globalNotifications.closest("[data-firebase-notifications-global]").remove();
	}

	// wait for initialization
	if(isWeb){
		FirebaseMessagingWebapp.api.onFCMInitialized(function(){
			restartUpdateInterval();
		});
	}

	// ios notification settings link
	const $globaliOS = $("[data-firebase-notifications-link]");
	if(isiOS){
		setInterval(function(){
			iOSNotifications.getSettingsURL().then(function(url){
				if(url != null) $globaliOS.attr("href", url);
			});
		}, 1000);
	} else {
		$globaliOS.closest("[data-firebase-notifications-global]").remove();
	}

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


});