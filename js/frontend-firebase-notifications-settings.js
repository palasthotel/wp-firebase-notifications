(function($){

	if(typeof AndroidNotifications === typeof undefined) return;



	const $global = $("[data-firebase-notifications-active]");
	$global.prop("checked", (AndroidNotifications.isNotificationsActive())? "checked": "")
	$global.on("change", function(e){
		AndroidNotifications.setNotifications($(this).is(":checked"));
	});

	const $topics = $("[data-firebase-notifications-topic]");
	$topics.each(function(){
		const $el = $(this);
		$el.prop(
			"checked",
			(AndroidNotifications.isSubscriptionActive(getTopic($el)))
				?"checked":""
		);
	});
	$topics.on("change", function(e){
		const $el = $(this);
		const topic = getTopic($el);
		if($el.is(":checked")){
			AndroidNotifications.subscribeTo(topic);
		} else {
			AndroidNotifications.unsubscribeFrom(topic);
		}

	});

	function getTopic($el) {
		return $el.attr("data-firebase-notifications-topic");
	}


})(jQuery);