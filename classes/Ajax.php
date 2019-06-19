<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-16
 * Time: 08:58
 */

namespace Palasthotel\FirebaseNotifications;


use PHPUnit\Runner\Exception;

/**
 * @property string action_send
 * @property string action_topics_list
 * @property Plugin plugin
 * @property string api_handle
 */
class Ajax {

	/**
	 * Ajax constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		$this->api_handle = Plugin::DOMAIN."-api";
		$this->action_send = Plugin::DOMAIN."_send";
		$this->action_topics_list = Plugin::DOMAIN."_topics_list";
		add_action("wp_ajax_$this->action_send", array($this, 'send'));
		add_action("wp_ajax_$this->action_topics_list", array($this, 'topics_list'));
	}

	/**
	 * enqueue javascript for api
	 */
	public function enqueueApiJs(){
		wp_enqueue_script(
			$this->api_handle,
			$this->plugin->url."/js/api.js",
			array('jquery'),
			filemtime( $this->plugin->path . "/js/api.js"),
			true
		);
		wp_localize_script(
			$this->api_handle,
			"FirebaseNotificationsApi",
			array(
				"ajax_url" => admin_url( 'admin-ajax.php' ),
				"actions" => array(
					"send" => $this->plugin->ajax->action_send,
					"topics_list" => $this->plugin->ajax->action_topics_list,
				),
			)
		);
	}

	/**
	 * send message
	 */
	public function send(){

		// TODO: make it configuratable
		if(!current_user_can('publish_posts')) wp_send_json_error(__("No access", Plugin::DOMAIN));

		if(!$this->plugin->cloudMessagingApi->hasConfiguration()) wp_send_json_error("Google Services configuration invalid.");

		$conditions = $_REQUEST["conditions"];
		foreach ($conditions as $index => $item){
			if(is_string($item)){
				$conditions[$index] = sanitize_text_field($item);
			} else if(is_array($item)) {
				foreach ($item as $_index => $_item){
					if(is_string($_item)){
						$item[$_index] = sanitize_text_field($_item);
					} else {
						wp_send_json_error("syntax error in conditions. String expected...");
					}
				}
			} else {
				wp_send_json_error("syntax error in conditions. String or array expected...");
			}
		}
		$plattforms = $_REQUEST["plattforms"];

		if(!is_array($plattforms)){
			wp_send_json_error("plattforms array not found");
		}
		$valid_plattforms = array("ios", "android", "web");
		foreach($plattforms as $p){
			if(!is_string($p)) wp_send_json_error("Plattforms array may only contain string values");
			if(!in_array($p,$valid_plattforms)) wp_send_json_error("Not a valid plattforms $p");
		}

		$title = sanitize_text_field(stripslashes($_REQUEST["title"]));
		$body = sanitize_textarea_field(stripslashes($_REQUEST["body"]));
		$payload = $_REQUEST["payload"];

		if(empty($payload)) wp_send_json_error("missing payloads");

		$sanitizedPayload = array();
		foreach ($payload as $key => $value){
			$sanitizedPayload[sanitize_text_field($key)] = sanitize_text_field($value);
		}

		if( empty($plattforms) || empty($conditions) || empty($title) || empty($body) || empty($sanitizedPayload)) wp_send_json_error("missing fields");

		$message = Message::build($plattforms,$conditions, $title, $body, $payload);
		do_action(Plugin::ACTION_SAVE_MESSAGE, $message);
		$message_id = $this->plugin->database->add($message);

		if(!$message_id) wp_send_json_error("Could not save notification");
		do_action(Plugin::ACTION_SAVED_MESSAGE, $message);

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
		}

	}

}