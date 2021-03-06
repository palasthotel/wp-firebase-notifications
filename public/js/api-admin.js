(function( $, api){

	const {
		actions,
		ajax_url
	} = api;

	api.send = function(platforms, conditions, title, body, payload = {}, schedule_timestamp = null ){
		return new Promise((resolve, reject)=>{
			if(isEmpty(platforms) || isEmpty(conditions) || isEmpty(title) || isEmpty(body)){
				reject("Missing arguments...");
				return;
			}
			post(actions.send, {
				title, body, platforms, conditions,
				schedule: schedule_timestamp,
				payload
			}).then(resolve).catch(reject);
		})
	};

	api.delete = function (message_id){
		return post(actions.delete, {message_id})
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
		return new Promise(function(resolve, reject){
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