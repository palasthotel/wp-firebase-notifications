(function($, api){

	$(function(){
		const $list = $(".firebase-notifications__list");
		$list.on("click",".firebase-notifications__item--show-communication", function(){
			console.log("show");
			const $this = $(this);
			const $item = $this.closest(".firebase-notifications__item");
			$item.find(".firebase-notifications__item--communication").show();
		});

	});

})(jQuery, FirebaseNotificationsApi);