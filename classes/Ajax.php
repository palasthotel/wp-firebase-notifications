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
		if(!current_user_can('publish_posts')) wp_send_json_error("No access");

		if(!$this->plugin->cloudMessagingApi->hasConfiguration()) wp_send_json_error("Google Services configuration invalid.");

		$topic = sanitize_text_field($_REQUEST["topic"]);
		$title = sanitize_text_field(stripslashes($_REQUEST["title"]));
		$body = sanitize_textarea_field(stripslashes($_REQUEST["body"]));
		$payload = $_REQUEST["payload"];

		if(empty($payload)) wp_send_json_error("missing payloads");

		$sanitizedPayload = array();
		foreach ($payload as $key => $value){
			$sanitizedPayload[sanitize_text_field($key)] = sanitize_text_field($value);
		}

		if(empty($topic) || empty($title) || empty($body) || empty($sanitizedPayload)) wp_send_json_error("missing fields");

		$message = Message::build($topic, $title, $body, $payload);

		$message = $this->plugin->database->add($message);
		if(!$message) wp_send_json_error("Could not save notification");

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