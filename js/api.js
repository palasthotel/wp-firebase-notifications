(function( $, api){

	const {
		actions,
		ajax_url
	} = api;

	api.send = (title, message, topic = null) => {
		return post(actions.send, {title, message, topic})
			.then(console.log)
			.catch(console.error);
	};

	api.topics = {
		list: post(actions.topics_list),
	};

	/**
	 *
	 * @param {string} action
	 * @param {object} params
	 * @return {Promise<*>}
	 */
	async function post(action, params) {
		return new Promise((resolve, reject) => {
			$.ajax(
				ajax_url + "?action=" + action,
				{
					method: 'POST',
					data: params,
					success: resolve,
					error: reject,
				}
			);
		});
	}



})(jQuery, FirebaseNotificationsApi);