<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-16
 * Time: 08:58
 */

namespace Palasthotel\FirebaseNotifications;


use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\TopicSubscription;
use Palasthotel\FirebaseNotifications\Component\Component;

class Ajax extends Component {
    
    public string $action_send;
    public string $action_delete;
    public string $action_subscribe;
    public string $action_unsubscribe;
    public string $action_topics;

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

		// TODO: make it configurable
		if(!current_user_can('publish_posts')) wp_send_json_error(__("No access", Plugin::DOMAIN));

		if(!$this->plugin->cloudMessagingApi->hasConfiguration()) wp_send_json_error("Google Services configuration invalid.");

		// topic conditions
		$conditions = $_REQUEST["conditions"];
		$topicsError = Validation::isValidConditions($conditions);
		if($topicsError instanceof \WP_Error){
			wp_send_json_error($topicsError->get_error_message());
		}
		$conditions = Validation::sanitizeConditions($conditions);

		$platforms = $_REQUEST["platforms"];
		$validPlatformsArray = Validation::isValidPlatformsArray($platforms);
		if($validPlatformsArray instanceof \WP_Error){
			wp_send_json_error($validPlatformsArray->get_error_message());
		}

		$title = sanitize_text_field(stripslashes($_REQUEST["title"]));
		$body = sanitize_textarea_field(stripslashes($_REQUEST["body"]));
		$payload = $_REQUEST["payload"];

		if(empty($payload)) wp_send_json_error("missing payloads");

		$sanitizedPayload = array();
		foreach ($payload as $key => $value){
			$sanitizedPayload[sanitize_text_field($key)] = sanitize_text_field($value);
		}

		if( empty($platforms) || empty($conditions) || empty($title) || empty($body) || empty($sanitizedPayload)) wp_send_json_error("missing fields");

		$message = Message::build($platforms,$conditions, $title, $body, $payload);
		do_action(Plugin::ACTION_SAVE_MESSAGE, $message);
		$message_id = $this->plugin->database->add($message);
		if(!$message_id) wp_send_json_error("Could not save notification message");
		do_action(Plugin::ACTION_SAVED_MESSAGE, $message);

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
		if(!current_user_can('publish_posts')) wp_send_json_error(__("No access", Plugin::DOMAIN));
		$message_id = intval($_REQUEST["message_id"]);

		$this->plugin->database->delete($message_id);
		wp_send_json_success($message_id);
	}

	public function subscribe(){
		$this->setSubscription(true);
	}

	public function unsubscribe(){
		$this->setSubscription(false);
	}

	/**
	 * @param $isActive
	 */
	public function setSubscription($isActive){
		if(!isset($_GET) || !isset($_GET["topic"]) || !isset($_GET["token"])){
			wp_send_json_error("Missing important data.");
			exit;
		}
		$token = $_GET["token"];
		$topic = sanitize_text_field($_GET["topic"]);
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
		$response = $this->plugin->cloudMessagingApi->getSubscriptions($_GET["token"]);
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

}