(function( $, api){

	const {
		actions,
		ajax_url
	} = api;

	api.send = async (conditions, title, body, payload = {} ) => {
		if(isEmpty(conditions) || isEmpty(title) || isEmpty(body)){
			throw "Missing arguments...";
		}

		console.log(conditions, title, body);
		console.log(payload);

		return post(actions.send, {title, body, conditions, payload});
	};

	function isEmpty(arg) {
		return isUndefined(arg) || arg === "" || arg.length < 1;
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