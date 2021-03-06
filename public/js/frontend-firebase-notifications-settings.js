jQuery(function($){

	const firebaseNotifications = window.FirebaseNotifications;

	if(!firebaseNotifications.isApp) return;

	if(!firebaseNotifications.isSupported()){
		//check browser compatibility
		$("body").addClass("firebase-notifications__is-not-supported");
		return;
	}

	const isAndroid = firebaseNotifications.isAndroid;
	const isiOS = firebaseNotifications.isiOS;
	const isWeb = firebaseNotifications.isWeb;

	const AppNotifications = firebaseNotifications.fn;

	// topics
	const changingTopics = {}; // topics that are subscribed or unsubscribed right now
	const $topics = $("[data-firebase-notifications-topic]");

	function updateTopics(){
		$topics.each(function(){
			const $el = $(this);
			const topic = getTopic($el);
			if (changingTopics[topic] !== true) {
				AppNotifications.isSubscribed(getTopic($el)).then(function (value) {
					// we get a string returned, so we're working around this
					if (value === 'false' || value === false) {
						value = false;
					} else if (value === 'false' || value === false) {
						value = true;
					}
					$el.prop("checked", value ? "checked" : "");
				});
			}
		});
	}

	// sync topics interval
	let updateInterval = null;
	function restartUpdateInterval(){
		clearInterval(updateInterval);
		updateInterval = setInterval(function(){
			updateTopics();
		}, 2000);
	}
	restartUpdateInterval();

	// global web or android activate notifications
	const $globalNotifications = $("[data-firebase-notifications-active]");
	if (!isiOS && (isAndroid || isWeb)) {
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
		/*
		setInterval(function() {
			iOSNotifications.getSettingsURL().then(function(url){
				if(url != null) $globaliOS.attr("href", url);
			});
		}, 1000);
		*/
	} else {
		$globaliOS.closest("[data-firebase-notifications-global]").remove();
	}

	$topics.on("change", function (e) {
		const $el = $(this);
		const topic = getTopic($el);
		const topicId = topic;
		changingTopics[topic] = true;
		if ($el.is(":checked")) {
			execute(AppNotifications.subscribe, topic)
				.then(function () {
					changingTopics[topic] = false;
					const subscribed = true;
					document.dispatchEvent(new CustomEvent('firebase_notifications_subscription_change', {detail: {subscribed, topicId}}))
				})
				.catch((error) => {
					console.log('not subscribed');
					console.log(error)
					addErrorMessage();
				});
			if (isAndroid || isWeb) $globalNotifications.prop("checked", "checked").trigger("change");
		} else {
			execute(AppNotifications.unsubscribe, topic)
				.then(function () {
					changingTopics[topic] = false;
					const subscribed = false;
					document.dispatchEvent(new CustomEvent('firebase_notifications_subscription_change', {detail: {subscribed, topicId}}))
				})
				.catch((error) => {
					console.log('not unsubscribed');
					console.log(error)
					addErrorMessage();
				});
		}

	});

	function execute(promisingFunction, topic){
		const $row = $("[data-firebase-notifications-wrapper-of=\""+topic+"\"]");
		$row.attr("data-firebase-notifications-is-loading", true);
		return promisingFunction(topic).then(function(){
			$row.removeAttr("data-firebase-notifications-is-loading");
		});
	}

	function getTopic($el) {
		return $el.attr("data-firebase-notifications-topic");
	}


});