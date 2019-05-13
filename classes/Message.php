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
 * @property string[] plattforms
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
	 * @param array $plattforms
	 * @param array $conditions
	 * @param string $title
	 * @param string $body
	 * @param array $payload
	 */
	private function __construct( $plattforms, $conditions, $title, $body, $payload ) {
		$this->plattforms = $plattforms;
		$this->conditions = $conditions;
		$this->title      = $title;
		$this->body       = $body;
		$this->payload    = $payload;
	}

	/**
	 * @param string[] $plattforms
	 * @param array $conditions
	 * @param string $title
	 * @param string $body
	 * @param array $payload
	 *
	 * @return Message
	 */
	public static function build( $plattforms, $conditions, $title, $body, $payload ) {
		return apply_filters(
			Plugin::FILTER_MESSAGE,
			new Message( $plattforms, $conditions, $title, $body, $payload )
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

		// base
		$msg = array(
			'condition' => $this->conditionForNotifications(),
			'data'         => $this->payload,
		);

		if(count($this->plattforms) == 3){
			// send to all plattforms
			$msg = array_merge(
				$msg,
				array(
					'notification' => array(
						'title' => $this->title,
						'body'  => $this->body,
					)
				)
			);
		} else {
			if(in_array("android",$this->plattforms)){
				$msg = array_merge(
					$msg,
					array(
						'android' => array(
							'notification' => array(
								'title' => $this->title,
								'body' => $this->body,
							)
						)
					)
				);
			}
			if(in_array("ios", $this->plattforms)){
				$msg = array_merge(
					$msg,
					array(
						'apns' => array(
							'payload' => array(
								'aps' => array(
									'alert' => array(
										'title' => $this->title,
										'body' => $this->body,
									),
								),
							),
						),
					)
				);
			}
			if(in_array("web", $this->plattforms)){
				$msg = array_merge(
					$msg,
					array(
						'webpush' => array(
							'notification' => array(
								'title' => $this->title,
								'body' => $this->body,
							),
						),
					)
				);
			}
		}

		return $msg;

	}

}