(function( $, api){

	const {
		actions,
		ajax_url
	} = api;

	api.send = async (topic, title, body, payload = {} ) => {
		if(isEmpty(topic) || isEmpty(title) || isEmpty(body)){
			throw "Missing arguments...";
		}

		console.log(topic, title, body);
		console.log(payload);

		return post(actions.send, {title, body, topic, payload});
	};

	function isEmpty(arg) {
		return isUndefined(arg) || arg === "";
	}
	function isUndefined(arg){
		return (typeof arg === typeof undefined)
	}

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