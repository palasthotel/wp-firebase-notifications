<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-16
 * Time: 08:58
 */

namespace Palasthotel\FirebaseNotifications;


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

		$topic = sanitize_text_field($_POST["topic"]);
		$title = sanitize_text_field($_POST["title"]);
		$message = sanitize_textarea_field($_POST["message"]);

		$result = $this->plugin->cloudMessagingApi->send($topic,$message, $title);
		wp_send_json_success($result);
	}

	/**
	 * list topics
	 */
	public function topics_list(){
		wp_send_json_success();
	}
}