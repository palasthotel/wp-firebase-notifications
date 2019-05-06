(function($, api, metaBox){

	$(function(){

		let isSending = false;
		let hasError = false;

		const $box = $("#firebase-notifications-meta-box");
		const $title = $("#firebase-notifications__title").on("keyup", resetNormalState);
		const $message = $("#firebase-notifications__body").on("keyup", resetNormalState);
		const $topic = $("#firebase-notifications__topic").on("change", resetNormalState);
		const $error = $box.find(".error-display");
		
		function resetNormalState() {
			$box.removeClass("has-error");
		}

		function showError(errorMessage){
			$box.removeClass("is-sending").addClass("has-error");
			$error.text(errorMessage);
		}

		$box.on("click", "input[type=submit]", function(e){
			e.preventDefault();

			const title = $title.val();
			const body = $message.val();
			const topic = $topic.val();

			if(title.length === 0){
				showError("Give me a message title, please.");
				return;
			}
			if(body.length === 0){
				showError("Type some body content.");
				return;
			}
			if(topic.length === 0){
				showError("Select a topic.");
				return;
			}

			if(isSending || hasError) return;
			isSending = true;

			$box.addClass("is-sending");

			api.send( topic, title, body, metaBox.payload )
				.then((response)=>{
					isSending = false;
					$box.removeClass("is-sending");
					if(!response.success){
						showError(response.data);
						hasError = true;
						return;
					}
					if(typeof response.data !== typeof {}){
						showError("I dont understand the returned value");
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
					showError(error.textStatus);
				});

		});

	});

})(jQuery, FirebaseNotificationsApi, FirebaseNotifications_MetaBox);