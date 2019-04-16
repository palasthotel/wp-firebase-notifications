(function(){

	if(typeof AndroidNotifications !== typeof undefined){
		console.log("✅ AndroidNotifications is available");
		testAndroid();
	} else if(typeof iOSNotifications !== typeof undefined){
		console.log("✅ iOSNotifications is available");
		testiOS();
	} else {
		console.log("🚨 Neither AndroidNotifications nor iOSNotifications was found.");
	}

	function testAndroid(){
		if(typeof AndroidNotifications === typeof undefined){
			console.log("🚨 AndroidNotifications is not available");
			return;
		} else {
			console.log("✅ AndroidNotifications is available");
		}

		let methodsFound = true;
		function testMethod(name){
			if(typeof AndroidNotifications[name] !== "function"){
				console.log("🚨 Method '"+name+"' in AndroidNotifications not found");
				methodsFound = false;
			} else {
				console.log("✅ Method '"+name+"' found");
			}
		}

		testMethod("setNotifications");
		testMethod("isNotificationsActive");
		testMethod("subscribeTo");
		testMethod("unsubscribeFrom");
		testMethod("isSubscriptionActive");

		if(!methodsFound) return;

		function test(value, expected, msg) {
			if(value !== expected){
				console.error("🚨 "+msg);
			}  else {
				console.log("✅ "+msg)
			}
		}

		AndroidNotifications.setNotifications(false);
		test(AndroidNotifications.isNotificationsActive(), false, "should be inactive");
		AndroidNotifications.setNotifications(true);
		test(AndroidNotifications.isNotificationsActive(), true, "Should be active now");

		AndroidNotifications.unsubscribeFrom("test");
		test(AndroidNotifications.isSubscriptionActive("test"), false, "topic should be inactive");
		AndroidNotifications.subscribeTo("test");
		test(AndroidNotifications.isSubscriptionActive("test"), true, "topic should be active");

		AndroidNotifications.subscribeTo("other-topic");
		test(AndroidNotifications.isSubscriptionActive("other-topic"), true, "other topic should be inactive");
		AndroidNotifications.unsubscribeFrom("other-topic");
		test(AndroidNotifications.isSubscriptionActive("other-topic"), false, "other topic should be active");
	}
	
	async function testiOS() {
		if(typeof iOSNotifications === typeof undefined){
			console.log("🚨 iOSNotifications is not available");
			return;
		} else {
			console.log("✅ iOSNotifications is available");
		}

		let methodsFound = true;
		function testMethod(name){
			if(typeof iOSNotifications[name] !== "function"){
				console.log("🚨 Method '"+name+"' in iOSNotifications not found");
				methodsFound = false;
			} else {
				console.log("✅ Method '"+name+"' found");
			}
		}

		testMethod("isNotificationsEnabled");
		testMethod("setNotificationsEnabled");
		testMethod("subscribe");
		testMethod("unsubscribe");
		testMethod("isSubscribed");

		if(!methodsFound) return;

		function test(value, expected, msg) {
			if(value !== expected){
				console.error("🚨 "+msg);
			}  else {
				console.log("✅ "+msg)
			}
		}

		await iOSNotifications.setNotificationsEnabled(false);
		test(await iOSNotifications.isNotificationsEnabled(), false, "should be inactive");
		await iOSNotifications.setNotificationsEnabled(true);
		test(await iOSNotifications.isNotificationsEnabled(), true, "Should be active now");

		await iOSNotifications.unsubscribe("test");
		test(await iOSNotifications.isSubscribed("test"), false, "topic should be inactive");
		await iOSNotifications.subscribe("test");
		test(await iOSNotifications.isSubscribed("test"), true, "topic should be active");

		await iOSNotifications.subscribe("other-topic");
		test(await iOSNotifications.isSubscribed("other-topic"), true, "other topic should be inactive");
		await iOSNotifications.unsubscribe("other-topic");
		test(await iOSNotifications.isSubscribed("other-topic"), false, "other topic should be active");
	}

})();