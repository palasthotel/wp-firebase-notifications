<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-17
 * Time: 21:50
 */

namespace Palasthotel\FirebaseNotifications;


/**
 * @property string topic
 * @property string title
 * @property string body
 * @property array payload
 */
class Message {

	/**
	 * @var null|int
	 */
	var $id = null;

	/**
	 * @var null|array
	 */
	var $result = null;

	/**
	 * @var null|string
	 */
	var $created = null;

	/**
	 * @var null|string
	 */
	var $sent = null;

	/**
	 * Notification constructor.
	 *
	 * @param string $topic
	 * @param string $title
	 * @param string $body
	 * @param array $payload
	 */
	private function __construct( $topic, $title, $body, $payload ) {
		$this->topic   = $topic;
		$this->title   = $title;
		$this->body    = $body;
		$this->payload = $payload;
	}

	/**
	 * @param $topic
	 * @param $title
	 * @param $body
	 * @param $payload
	 *
	 * @return Message
	 */
	public static function build( $topic, $title, $body, $payload ) {
		return apply_filters(
			Plugin::FILTER_MESSAGE,
			new Message( $topic, $title, $body, $payload )
		);
	}

	/**
	 * @return array
	 */
	public function getCloudMessageArray() {
		return array(
			'topic'        => $this->topic,
			'notification' => array(
				'title' => $this->title,
				'body'  => $this->body,
			),
			'data'         => $this->payload,
		);
	}

}