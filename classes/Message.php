<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-17
 * Time: 21:50
 */

namespace Palasthotel\FirebaseNotifications;


/**
 * @property array $conditions
 * @property string title
 * @property string body
 * @property array payload
 */
class Message {

	/**
	 * @var null|int
	 */
	var $id = NULL;

	/**
	 * @var null|array
	 */
	var $result = NULL;

	/**
	 * @var null|string
	 */
	var $created = NULL;

	/**
	 * @var null|string
	 */
	var $sent = NULL;

	/**
	 * Notification constructor.
	 *
	 * @param array $conditions
	 * @param string $title
	 * @param string $body
	 * @param array $payload
	 */
	private function __construct( $conditions, $title, $body, $payload ) {
		$this->conditions = $conditions;
		$this->title      = $title;
		$this->body       = $body;
		$this->payload    = $payload;
	}

	/**
	 * @param $conditions
	 * @param $title
	 * @param $body
	 * @param $payload
	 *
	 * @return Message
	 */
	public static function build( $conditions, $title, $body, $payload ) {
		return apply_filters(
			Plugin::FILTER_MESSAGE,
			new Message( $conditions, $title, $body, $payload )
		);
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function conditionForDisplay() {
		return $this->buildConditionForDisplay($this->conditions);
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function conditionForNotifications() {
		return $this->buildConditionForNotifications($this->conditions);
	}

	/**
	 * @param $items
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function buildConditionForDisplay($items){
		$parts=array();
		foreach ($items as $i => $item){
			if($i % 2){
				$upper = strtoupper($item);
				if($upper == "AND" || $upper == "OR"){
					$parts[] = $upper;
				} else {
					throw new \Exception("AND or OR is allowed here...");
				}
			} else {
				if(is_string($item)){
					$parts[] = $item;
				} else if(is_array($item)){
					$parts[] = "( ".$this->buildConditionForDisplay($item) . " )";
				} else {
					throw new \Exception("Unknown condition type");
				}
			}
		}
		return join(" ", $parts);
	}

	/**
	 * @param $items
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function buildConditionForNotifications($items){
		$parts=array();
		foreach ($items as $i => $item){
			if($i % 2){
				$upper = strtoupper($item);
				if($upper == "AND"){
					$parts[] = "&&";
				} else if($upper == "OR"){
					$parts[]="||";
				} else {
					throw new \Exception("AND or OR is allowed here...");
				}
			} else {
				if(is_string($item)){
					$parts[] = "'$item' in topics";
				} else if(is_array($item)){
					$parts[] = "(".$this->buildConditionForNotifications($item) . ")";
				} else {
					throw new \Exception("Unknown condition type");
				}
			}
		}
		return join(" ", $parts);
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function getCloudMessageArray() {
		//https://firebase-php.readthedocs.io/en/latest/cloud-messaging.html
		return array(
			'condition'    => $this->conditionForNotifications(),
			'notification' => array(
				'title' => $this->title,
				'body'  => $this->body,
			),
			'data'         => $this->payload,
		);
	}

}