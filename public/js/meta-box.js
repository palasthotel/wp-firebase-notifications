(function($, api, metaBox){

	const restrictions = metaBox.restrictions;
	const i18n = metaBox.i18n;
	const topic_ids = metaBox.topic_ids;

	function CountChars($countable, fn){
		$countable.on("keyup",(e)=>{
			fn($countable.val().length);
		});
		$countable.trigger("keyup");
	}

	function CountableWrapper($countable, longLength, tooLongLength, shortLength) {
		const $wrap = $("<div></div>").addClass("counter-warp");
		$countable.after($wrap);
		$wrap.append($countable);
		const $counter = $("<span></span>");
		$wrap.append($counter);
		CountChars($countable,(length)=>{
			let text = i18n.countable.text.replace("%d", length);
			$counter.removeClass("is-short");
			$counter.removeClass("is-good");
			$counter.removeClass("is-long");
			$counter.removeClass("is-too-long");

			if( length >= tooLongLength ){
				text+= " "+i18n.countable.too_long;
				$counter.addClass("is-too-long");
			} else if( length >= longLength) {
				text+= " "+i18n.countable.long;
				$counter.addClass("is-long");
			} else if(shortLength > 0 && length >= shortLength){
				text+=" "+i18n.countable.good;
				$counter.addClass("is-good");
			} else if(length <= shortLength){
				text+=" "+i18n.countable.short;
				$counter.addClass("is-short");
			}
			$counter.text(text);
		});
	}

	/**
	 * Hooks
	 */
	function Hooks() {
		const hooks = [];
		const trigger = (hook, data) => {
			hooks
				.filter(h => h.name === hook)
				.forEach((h)=>{
					h.fn(data);
				})
		};
		const on = (hook, fn)=>{
			hooks.push({
				name: hook,
				fn,
			});
		};
		return {
			on,
			trigger,
		};
	}
	const hooks = metaBox.hooks = Hooks();

	/**
	 * Plugins handler
	 */
	function Plugins(){
		const plugins = [];
		const hooks = [];
		const get = () => plugins;
		const add = (plugin)=> plugins.push(plugin);
		const getFunctions = (name)=>{
			return plugins
				.filter((p)=> typeof p[name] === "function")
				.map((p)=>p[name]);
		};
		return {
			get,
			add,
			getFunctions,
			setPayloads: ()=> getFunctions("set_payload"),
		};
	}
	const plugins = metaBox.plugins = Plugins();

	const _isKeyDown = {};

	const isKeyDown =(key) => typeof _isKeyDown[key] !== typeof undefined && _isKeyDown[key] === true;
	const setKeyDown = (key, isDown) =>{ _isKeyDown[key] = isDown };
	const isAltDown = () => isKeyDown("alt");
	const setAltDown = (isDown) => { setKeyDown("alt", isDown) };
	const isShiftDown = () => isKeyDown("shift");
	const setShiftDown = (isDown)=>{setKeyDown("shift", isDown) };

	function keyEvent(e, isDown){
		switch (e.keyCode) {
			case 18:
				setAltDown(isDown);
				break;
			case 16:
				setShiftDown(isDown);
				break;
		}
	}

	$(window)
		.on("keydown",function(e){ keyEvent(e, true)})
		.on("keyup", function(e){ keyEvent(e, false)});

	$(function(){

		let isSending = false;
		let hasError = false;
		let generatedValue = false;
		let conditions = null;

		const $box = $("#firebase-notifications-meta-box");
		const $title = $("#firebase-notifications__title").on("keyup", resetNormalState);
		CountableWrapper($title, restrictions.title.long, restrictions.title.too_long, restrictions.title.short);
		const $message = $("#firebase-notifications__body").on("keyup", resetNormalState);
		CountableWrapper($message, restrictions.text.long, restrictions.text.too_long, restrictions.text.short);
		const $plattforms = $box.find("[name='plattform[]']");

		$plattforms.on("change", function(){
			const values = getPlattforms();
			if(values.length){
				resetNormalState();
			} else {
				showPlattformsError();
			}
		});
		const $schedule = $box.find("[name=firebase_schedule]");
		const $schedule_datetime = $box.find("[name=firebase_schedule_datetime]");
		$schedule.on("change", function(){
			if(this.value === "plan"){
				$box.find("input[type=submit]").val(i18n.submit.plan);
				$schedule_datetime.closest("label").show();
			} else {
				$box.find("input[type=submit]").val(i18n.submit.now);
				$schedule_datetime.closest("label").hide();
			}
		});
		$schedule_datetime.closest("label").hide();

		hooks.on("resetNormalState", ()=> resetNormalState());


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
				if(!isValid){
					setConditionsInvalid();
					return;
				}

				const inLimitations = isInConditionLimitations(result);
				if(inLimitations){
					conditions = result;
					setConditionsValid()
				} else {
					setConditionsNotInLimitations();
				}

			} catch (e) {
				conditions = null;
				setConditionsInvalid();
				console.error(e);
			}
			
		});
		$conditions.trigger("keyup");

		$box.on("click",".firebase-notifications__topic--copy", function(){
			if($conditions.val() !== "" && $conditionsValid.hasClass("is-invalid")){
				if(!confirm(i18n.confirms.overwrite_conditions)){
					return;
				}
				$conditions.val("");
			}
			let conditionsValue = $conditions.val();
			if(conditionsValue.length > 0){
				conditionsValue += " OR ";
			}
			$conditions.val(conditionsValue+$(this).text()).trigger("keyup");
		});

		const $error = $box.find(".error-display");

		function getPlattforms() {
			const values = [];
			$plattforms.each((index,el)=>{
				const $el = $(el);
				if($el.is(":checked")) values.push($el.val());
			});
			return values;
		}
		function isScheduled(){
			return $box.find("[name=firebase_schedule]:checked").val() === "plan";
		}
		function getScheduleDatetime() {
			return $schedule_datetime.val();
		}
		function getScheduleTimestamp() {
			const date = new Date($schedule_datetime.val());
			// convert milliseconds to full seconds
			return Math.round(date.getTime() / 1000);
		}
		
		function resetConditionValid(){
			$conditionsValid.removeClass("is-invalid").removeClass("is-valid");
		}
		function setConditionsInvalid(){
			$conditionsValid.text(
				($conditions.val() === "")? i18n.empty_conditions:i18n.invalid
			).addClass("is-invalid");
		}
		function setConditionsNotInLimitations(){
			$conditionsValid.text( i18n.limitation_conditions).addClass("is-invalid");
		}
		function setConditionsValid(){
			$conditionsValid.text(i18n.valid).addClass("is-valid");
		}
		function resetNormalState() {
			$box.removeClass("has-error");
		}
		function showPlattformsError(){
			showError(i18n.errors.plattforms);
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
			$schedule_datetime.attr("readonly", "readonly");
			$schedule.attr("disabled", "disabled");
			hooks.trigger("lockUI");
		}
		function unlockUI(){
			$title.removeAttr("readonly");
			$message.removeAttr("readonly");
			$conditions.removeAttr("readonly");
			$plattforms.removeAttr("disabled");
			$schedule_datetime.removeAttr("readonly");
			$schedule.removeAttr("disabled");
			hooks.trigger("unlockUI");
		}

		$box.on("click", "input[type=submit]", function(e){
			e.preventDefault();
			resetNormalState();

			const title = $title.val();
			const body = $message.val();
			const plattforms = getPlattforms();
			const payload = metaBox.payload;
			const schedule = (isScheduled())? getScheduleTimestamp():null;

			if(title.length === 0){
				showError(i18n.errors.title);
				return;
			}
			if(body.length === 0){
				showError(i18n.errors.body);
				return;
			}
			if(conditions == null){
				showError(i18n.errors.conditions);
				return;
			}
			if(!plattforms.length){
				showPlattformsError();
				return;
			}

			if( isScheduled() ) {
				if (typeof schedule !== typeof 1 || !schedule) {
					showError(i18n.errors.schedule.invalid);
					return;
				}
				else if (schedule <= (new Date()).getTime() / 1000) {
						showError(i18n.errors.schedule.in_the_past);
						return;
				}
			}

			let error = false;
			for(const setPayload of plugins.setPayloads()){
				setPayload(payload,(_error)=>{
					error = _error;
				});
				if(error) break;
			}
			if(error){
				showError(error);
				return;
			}

			if(isSending || hasError) return;
			isSending = true;

			$box.addClass("is-sending");
			lockUI();

			api.send( plattforms, conditions, title, body, payload, schedule )
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
					console.log(response.data);
					if(schedule){
						$box.addClass("was-scheduled");
					} else {
						$box.addClass("was-sent");
					}

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
			generatedValue = true;
		});

		// ------------------------------------
		// history
		// ------------------------------------
		$(".firebase-notifications__history").on("click",".delete", function(e) {
			e.preventDefault();
			const $msg = $(this).closest("[data-message-id]");
			const id = $msg.attr("data-message-id");
			if(id){
				api.delete(id).then(()=>{
					$msg.remove();
				});
			}
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

	function isInConditionLimitations(conditions){
		const doesCount = (item)=> item.toUpperCase() === "AND" || item.toUpperCase() === "OR";
		// max of 4 conditionals
		const reduced = conditions.reduce((value, item)=>{
			if(typeof item === typeof []) return value + item.reduce((value, item)=> (doesCount(item))?value+1:value,0);
			if(doesCount(item)) return value+1;
			return value;
		}, 0);

		return reduced <= 4;
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