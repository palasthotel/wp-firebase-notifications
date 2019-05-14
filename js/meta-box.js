(function($, api, metaBox){

	$(function(){

		let isSending = false;
		let hasError = false;

		const i18n = metaBox.i18n;
		const topic_ids = metaBox.topic_ids;
		let conditions = null;

		const $box = $("#firebase-notifications-meta-box");
		const $title = $("#firebase-notifications__title").on("keyup", resetNormalState);
		const $message = $("#firebase-notifications__body").on("keyup", resetNormalState);
		const $plattforms = $box.find("[name='plattform[]']");
		$plattforms.on("change", function(){
			const values = getPlattforms();
			if(values.length){
				resetNormalState();
			} else {
				showPlattformsError();
			}
		});

		// json encoded topic configuration
		const $conditionsValid = $("#firebase-notifications_conditions--valid");
		const $conditions = $("#firebase-notifications__conditions").on("keyup", function(){
			resetNormalState();
			resetConditionValid();

			// expression string
			const expression = $conditions.val();

			if(!expression.length){
				conditions = null;
				setConditionsInvalid();
				return;
			}

			try{
				const result = parseConditions(expression);
				const isValid = isValidConditions(result, topic_ids);
				if(isValid){
					conditions = result;
					setConditionsValid()
				} else {
					setConditionsInvalid();
				}
			} catch (e) {
				conditions = null;
				setConditionsInvalid();
				console.error(e);
			}
			
		});
		$conditions.trigger("keyup");

		const $error = $box.find(".error-display");

		function getPlattforms() {
			const values = [];
			$plattforms.each((index,el)=>{
				const $el = $(el);
				if($el.is(":checked")) values.push($el.val());
			});
			return values;
		}
		
		function resetConditionValid(){
			$conditionsValid.removeClass("is-invalid").removeClass("is-valid");
		}
		function setConditionsInvalid(){
			$conditionsValid.text(i18n.invalid).addClass("is-invalid");
		}
		function setConditionsValid(){
			$conditionsValid.text(i18n.valid).addClass("is-valid");
		}
		function resetNormalState() {
			$box.removeClass("has-error");
		}
		function showPlattformsError(){
			showError("At least one plattform needs to be activated.");
		}
		function showError(errorMessage){
			$box.removeClass("is-sending").addClass("has-error");
			$error.text(errorMessage);
		}

		function lockUI(){
			$title.attr("readonly", "readonly");
			$message.attr("readonly", "readonly");
			$conditions.attr("readonly", "readonly");
			$plattforms.attr("disabled", "disabled");
		}
		function unlockUI(){
			$title.removeAttr("readonly");
			$message.removeAttr("readonly");
			$conditions.removeAttr("readonly");
			$plattforms.removeAttr("disabled");
		}

		$box.on("click", "input[type=submit]", function(e){
			e.preventDefault();

			const title = $title.val();
			const body = $message.val();
			const plattforms = getPlattforms();

			if(title.length === 0){
				showError("Give me a message title, please.");
				return;
			}
			if(body.length === 0){
				showError("Type some body content.");
				return;
			}
			if(conditions == null){
				showError("Please define your topic conditions.");
				return;
			}
			if(!plattforms.length){
				showPlattformsError();
				return;
			}

			if(isSending || hasError) return;
			isSending = true;

			$box.addClass("is-sending");
			lockUI();

			api.send( plattforms, conditions, title, body, metaBox.payload )
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
				})
				.catch((error)=>{
					isSending = false;
					hasError = true;
					console.error(error);
					showError(error.textStatus);
				});

		});

		// ------------------------------------
		// examples
		// ------------------------------------
		const $examples = $box.find(".firebase-notifications__examples");
		const $examples_content = $examples.find(".examples__content");
		$examples.on("click", ".examples__header", function(){
			$examples_content.toggle();
		});
		$examples.on("click", ".examples__code--wrapper", function() {
			$conditions.val($(this).find(".examples__code").text()).trigger("keyup");
		})

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