(function($, api, metaBox){

	$(function(){

		let isSending = false;
		let hasError = false;

		const topic_ids = metaBox.topic_ids;

		const $box = $("#firebase-notifications-meta-box");
		const $title = $("#firebase-notifications__title").on("keyup", resetNormalState);
		const $message = $("#firebase-notifications__body").on("keyup", resetNormalState);

		// json encoded topic configuration
		const $conditionsValid = $("#firebase-notifications_conditions--valid");
		const $conditions = $("#firebase-notifications__conditions").on("keyup", function(){
			resetNormalState();
			resetConditionValid();

			// expression string
			const expression = $conditions.val();

			if(!expression.length){
				setConditionsInvalid();
				return;
			}

			try{
				const result = parseConditions(expression);
				const isValid = isValidConditions(result, topic_ids);
				(isValid) ? setConditionsValid(): setConditionsInvalid();
			} catch (e) {
				setConditionsInvalid();
				console.error(e);
			}
			
		});
		$conditions.trigger("keyup");

		const $topic = $("#firebase-notifications__topic").on("change", resetNormalState);
		const $error = $box.find(".error-display");
		
		function resetConditionValid(){
			$conditionsValid.removeClass("is-invalid").removeClass("is-valid");
		}
		function setConditionsInvalid(){
			$conditionsValid.text("invalid").addClass("is-invalid");
		}
		function setConditionsValid(){
			$conditionsValid.text("valid").addClass("is-valid");
		}
		function resetNormalState() {
			$box.removeClass("has-error");
		}

		function showError(errorMessage){
			$box.removeClass("is-sending").addClass("has-error");
			$error.text(errorMessage);
		}

		function buildTopicsSelect(){

			return $("<select>").append($options);
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

	// ------------------------------------
	// conditions validation
	// ------------------------------------
	function isValidConditions(conditions, allowed_topic_ids){
		let index;
		for( index in conditions){
			if(!conditions.hasOwnProperty(index)) continue;
			const cond = conditions[index];

			if(index % 2) continue;

			if(typeof cond === typeof []){
				if(!isValidConditions(cond, allowed_topic_ids)) return false;
			} else if(typeof cond === typeof ""){
				if(allowed_topic_ids.indexOf(cond) < 0) return false;
			} else {
				return false;
			}
		}
		return true;
	}

	// ------------------------------------
	// conditions parser
	// ------------------------------------
	const EXCEPTION_TYPE_BRACKETS = "brackets";
	const EXCEPTION_TYPE_AND_OR = "and_or";
	const EXCEPTION_TYPE_ARGUMENT_NUMBER = "argument_number";

	function buildParseException(msg, type){
		return {
			message: msg,
			type: type,
		};
	}

	function parseConditions(conditions){

		// replace all double spaces
		conditions = conditions.replace(/ {1,}/g," ");

		// find nested conditions
		const nestedConditions = [];
		let i = 0;
		do{
			const pos = conditions.indexOf("(");
			if(pos < 0) break;
			const posEnd = conditions.indexOf(")");
			if(posEnd < 0) throw buildParseException("Syntax error: missing closing bracket.", EXCEPTION_TYPE_BRACKETS);

			const brackets = conditions.substring(pos+1, posEnd);
			nestedConditions.push(brackets);
			conditions = conditions.replace(`(${brackets})`, `$${i}$`);
			i++;
		}while(i < 100);

		// break down to main condition parts and filter empty items
		const mainConditions = conditions.split(" ").filter((item)=> item);

		if(mainConditions.length % 2 !== 1){
			throw buildParseException(
				`Syntax error: expression seems to have a weired argument number of ${mainConditions.length}. This should be an odd number.`,
				EXCEPTION_TYPE_ARGUMENT_NUMBER
			);
		}


		// validate and map to result
		return mainConditions.map((item, index)=>{
			const isOdd = index % 2;
			if(isOdd && item.toUpperCase() !== "AND" && item.toUpperCase() !== "OR"){
				throw buildParseException(`Unknown item: ${item} . Should be AND or OR`, EXCEPTION_TYPE_AND_OR);
			} else if(m = /^\$(\d+)\$$/gm.exec(item)){
				const number = m[1];
				const nested = nestedConditions[number];
				return parseConditions(nested);
			}
			return item;
		});
	}

})(jQuery, FirebaseNotificationsApi, FirebaseNotifications_MetaBox);