(function( $, api){

	const {
		actions,
		ajax_url
	} = api;

	api.send = async function(plattforms, conditions, title, body, payload = {}, schedule_timestamp = null ){
		if(isEmpty(plattforms) || isEmpty(conditions) || isEmpty(title) || isEmpty(body)){
			throw "Missing arguments...";
		}

		console.log(schedule_timestamp);
		console.log(plattforms);
		console.log(conditions);
		console.log(title, body);
		console.log(payload);

		return post(actions.send, {
			title, body, plattforms, conditions,
			schedule: (schedule_timestamp)? Math.round(schedule_timestamp/1000): null,
			payload
		});
	};

	api.delete = async function (message_id){
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