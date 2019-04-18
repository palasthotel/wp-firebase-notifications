(function($, api, metaBox){

	$(function(){

		let isSending = false;
		let hasError = false;

		const $box = $("#firebase-notifications-meta-box");
		const $title = $("#firebase-notifications__title");
		const $message = $("#firebase-notifications__body");
		const $topic = $("#firebase-notifications__topic");
		const $error = $box.find(".error-display");

		$box.on("click", "input[type=submit]", function(e){
			e.preventDefault();

			if(isSending || hasError) return;
			isSending = true;

			$box.addClass("is-sending");

			const title = $title.val();
			const body = $message.val();
			const topic = $topic.val();

			api.send( topic, title, body, metaBox.payload )
				.then((response)=>{
					isSending = false;
					$box.removeClass("is-sending");
					if(!response.success){
						$box.addClass("has-error");
						$error.text(response.data);
						hasError = true;
						return;
					}
					if(typeof response.data !== typeof {}){
						$box.addClass("has-error");
						$error.text("I dont understand the returned value");
						hasError = true;
						return;
					}
					$box.addClass("was-sent");

					$title.attr("readonly", "readonly");
					$message.attr("readonly", "readonly");
					$topic.attr("disabled", "disabled");
				})
				.catch((error)=>{
					isSending = false;
					hasError = true;
					console.error(error);
					$box.removeClass("is-sending").addClass("has-error");
					$error.text(error.statusText);
				});

		});

	});

})(jQuery, FirebaseNotificationsApi, FirebaseNotifications_MetaBox);