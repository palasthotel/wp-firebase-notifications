<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-16
 * Time: 08:58
 */

namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Palasthotel\FirebaseNotifications\Vendor\Kreait\Firebase\Exception\FirebaseException;
use Palasthotel\FirebaseNotifications\Vendor\Kreait\Firebase\Exception\MessagingException;
use Palasthotel\FirebaseNotifications\Vendor\Kreait\Firebase\Messaging\TopicSubscription;
use Palasthotel\FirebaseNotifications\Component\Component;

class Ajax extends Component {
    
    public string $action_send;
    public string $action_delete;
    public string $action_subscribe;
    public string $action_unsubscribe;
    public string $action_topics;

	const NONCE_ACTION = "firebase_notifications_admin";

	public function onCreate() {
		$this->action_send = Plugin::DOMAIN."_send";
		$this->action_delete = Plugin::DOMAIN."_delete";
		$this->action_subscribe = Plugin::DOMAIN."_subscribe";
		$this->action_unsubscribe = Plugin::DOMAIN."_unsubscribe";
		$this->action_topics = Plugin::DOMAIN."_topics";
		add_action("wp_ajax_$this->action_send", array($this, 'send'));
		add_action("wp_ajax_$this->action_delete", array($this, 'delete'));
		add_action('wp_ajax_'.$this->action_subscribe, array($this, 'subscribe'));
		add_action('wp_ajax_nopriv_'.$this->action_subscribe, array($this, 'subscribe'));
		add_action('wp_ajax_'.$this->action_unsubscribe, array($this, 'unsubscribe'));
		add_action('wp_ajax_nopriv_'.$this->action_unsubscribe, array($this, 'unsubscribe'));
		add_action('wp_ajax_'.$this->action_topics, array($this, 'topics'));
		add_action('wp_ajax_nopriv_'.$this->action_topics, array($this, 'topics'));
	}

	/**
	 * send message
	 */
	public function send(){

		check_ajax_referer( self::NONCE_ACTION );
		if(!$this->plugin->permissions->canSendMessages()) wp_send_json_error(__("No access", 'palasthotel-push-notifications-for-firebase'), 403);

		if(!$this->plugin->cloudMessagingApi->hasConfiguration()) wp_send_json_error("Google Services configuration invalid.");

		// topic conditions: nested arrays of strings, checked by Validation and then
		// sanitized by Validation::sanitizeConditions()
		$conditions = isset( $_REQUEST["conditions"] ) ? wp_unslash( $_REQUEST["conditions"] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated and sanitized below
		$topicsError = Validation::isValidConditions($conditions);
		if($topicsError instanceof \WP_Error){
			wp_send_json_error($topicsError->get_error_message());
		}
		$conditions = Validation::sanitizeConditions($conditions);

		$platforms = isset( $_REQUEST["platforms"] ) && is_array( $_REQUEST["platforms"] )
			? array_map( 'sanitize_text_field', wp_unslash( $_REQUEST["platforms"] ) )
			: null;
		$validPlatformsArray = Validation::isValidPlatformsArray($platforms);
		if($validPlatformsArray instanceof \WP_Error){
			wp_send_json_error($validPlatformsArray->get_error_message());
		}

		$title = isset( $_REQUEST["title"] ) ? sanitize_text_field( wp_unslash( $_REQUEST["title"] ) ) : "";
		$body = isset( $_REQUEST["body"] ) ? sanitize_textarea_field( wp_unslash( $_REQUEST["body"] ) ) : "";
		// every value is sanitized in the loop below
		$payload = isset( $_REQUEST["payload"] ) ? wp_unslash( $_REQUEST["payload"] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per key and value below

		if(empty($payload) || !is_array($payload)) wp_send_json_error("missing payloads");

		$sanitizedPayload = array();
		foreach ($payload as $key => $value){
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$sanitizedPayload[sanitize_text_field($key)] = sanitize_text_field( (string) $value );
		}

		if( empty($platforms) || empty($conditions) || empty($title) || empty($body) || empty($sanitizedPayload)) wp_send_json_error("missing fields");

		$message = Message::build($platforms,$conditions, $title, $body, $sanitizedPayload);
		do_action(Plugin::ACTION_SAVE_MESSAGE, $message); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant
		$message_id = $this->plugin->database->add($message);
		if(!$message_id) wp_send_json_error("Could not save notification message");
		do_action(Plugin::ACTION_SAVED_MESSAGE, $message); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant

		// schedule
		if(isset($_REQUEST["schedule"]) && !empty($_REQUEST["schedule"])){
			$schedule_timestamp = intval($_REQUEST["schedule"]);
			$this->plugin->database->setSchedule($message->id, $schedule_timestamp);
			$message = $this->plugin->database->getMessage($message->id);
			wp_send_json_success($message);
			return;
		}

		try{

			$result = $this->plugin->cloudMessagingApi->send($message);
			$message->result = $result;
			$success = $this->plugin->database->setSent( $message->id, $result);

			if($success){
				wp_send_json_success($message);
			} else {
				wp_send_json_error("Firebase Service connection not working. Check the settings.");
			}
		} catch (\Exception $e){
			wp_send_json_error($e->getMessage());
		} catch ( MessagingException $e ) {
			wp_send_json_error($e->getMessage());
		} catch ( FirebaseException $e ) {
			wp_send_json_error($e->getMessage());
		}

	}

	/**
	 * delete a single message
	 */
	public function delete(){
		check_ajax_referer( self::NONCE_ACTION );
		if(!$this->plugin->permissions->canSendMessages()) wp_send_json_error(__("No access", 'palasthotel-push-notifications-for-firebase'), 403);
		$message_id = isset( $_REQUEST["message_id"] ) ? absint( $_REQUEST["message_id"] ) : 0;

		$this->plugin->database->delete($message_id);
		wp_send_json_success($message_id);
	}

	public function subscribe(){
		$this->setSubscription(true);
	}

	public function unsubscribe(){
		$this->setSubscription(false);
	}

	// The subscription actions are public: browsers call them for their own FCM
	// registration token, also on cached pages, so there is no nonce to check. They
	// change nothing in WordPress, only the token's topic subscriptions at Firebase,
	// and subscribing is limited to the topics the site offers.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended

	/**
	 * @param $isActive
	 */
	public function setSubscription($isActive){
		if(!isset($_GET) || !isset($_GET["topic"]) || !isset($_GET["token"])){
			wp_send_json_error("Missing important data.");
			exit;
		}
		if ( ! $this->plugin->cloudMessagingApi->hasConfiguration() ) {
			wp_send_json_error( "Push notifications are not configured.", 503 );
		}
		$token = sanitize_text_field( wp_unslash( $_GET["token"] ) );
		$topic = sanitize_text_field( wp_unslash( $_GET["topic"] ) );
		// subscribe only to the topics the site offers (FILTER_TOPICS), not to hidden or
		// internal ones; unsubscribing stays possible from any topic
		if ( $isActive && ! in_array( $topic, $this->plugin->topics->getTopicIds(), true ) ) {
			wp_send_json_error( "Unknown topic.", 400 );
		}
		if($isActive){
			$result = $this->plugin->cloudMessagingApi->subscribe($topic, $token);
		} else {
			$result = $this->plugin->cloudMessagingApi->unsubscribe($topic, $token);
		}

		if(
			false != $result
			&&
			is_array($result)
			&&
			isset($result["results"])
			&&
			count($result["results"]) > 0
		){
			wp_send_json_success();
		} else {
			wp_send_json_error("Could not perform action");
		}
	}

	public function topics(){
		if(!isset($_GET) || !isset($_GET["token"])){
			wp_send_json_error("Missing important data.");
			exit;
		}
		if ( ! $this->plugin->cloudMessagingApi->hasConfiguration() ) {
			wp_send_json_error( "Push notifications are not configured.", 503 );
		}
		try {
			$response = $this->plugin->cloudMessagingApi->getSubscriptions( sanitize_text_field( wp_unslash( $_GET["token"] ) ) );
		} catch ( \Throwable $e ) {
			wp_send_json_error( "Could not load the subscriptions." );
		}
		if(!is_countable($response)){
			wp_send_json_error("Response is not countable");
		}
		$result=[];
		foreach ($response as $subscription){
			/**
			 * @var TopicSubscription $subscription
			 */
			$result[] = $subscription->jsonSerialize();

		}
		wp_send_json_success($result);
	}

	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}