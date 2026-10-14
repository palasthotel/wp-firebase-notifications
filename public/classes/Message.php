<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-17
 * Time: 21:50
 */

namespace Palasthotel\FirebaseNotifications;


class Message {

	/**
	 * @var string[] platforms the message is for: android, ios, web
	 */
	public $plattforms;

	/**
	 * @var array topic condition: topic ids alternating with AND/OR, nested arrays for brackets
	 */
	public $conditions;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $body;

	/**
	 * @var array data payload, string values
	 */
	public $payload;

	/**
	 * @var null|int
	 */
	public $id = null;

	/**
	 * @var null|array|object
	 */
	public $result = null;

	/**
	 * @var null|string
	 */
	public $created = null;

	/**
	 * @var null|string
	 */
	public $sent = null;

	/**
	 * @var null|string
	 */
	public $publish = null;

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
		return new Message( $plattforms, $conditions, $title, $body, $payload );
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
		$countConditions = 0;
		foreach ($items as $i => $item){
			if($i % 2){
				if(++$countConditions > 4) throw new \Exception("Maximum of 4 logical operators supported.");
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
	 * @return bool
	 */
	public function isForIOS(){
		return in_array("ios",$this->plattforms);
	}

	/**
	 * @return bool
	 */
	public function isForAndroid(){
		return in_array("android",$this->plattforms);
	}

	/**
	 * @return bool
	 */
	public function isForWeb(){
		return in_array("web",$this->plattforms);
	}

	/**
	 * @return bool
	 */
	public function isForAllPlattforms(){
		return $this->isForIOS() && $this->isForAndroid() && $this->isForWeb();
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function getPayload($key){
		return (isset($this->payload[$key]))? $this->payload[$key] : null;
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function setPayload($key, $value){
		$this->payload[$key] = $value;
	}

	public function unsetPayload($key_to_delete){
		$new = array();
		foreach ($this->payload as $key => $value){
			if($key == $key_to_delete) continue;
			$new[$key] = $value;
		}
		$this->payload = $new;
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

		if($this->isForAllPlattforms()){
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
		}

		if($this->isForAndroid()){
			$msg = array_merge(
				$msg,
				array(
					'android' => array(
						'priority' => 'normal',
						'notification' => array(
							'title' => $this->title,
							'body' => $this->body,
						)
					)
				)
			);
		}
		if($this->isForIOS()){
			$msg = array_merge(
				$msg,
				array(
					'apns' => array(
						'headers' => [
							'apns-priority' => '10',
						],
						'payload' => array(
                            'notification' => array(
                                'title' => $this->title,
                                'body'  => $this->body,
                            ),
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
		if($this->isForWeb()){
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


		return apply_filters(Plugin::FILTER_MESSAGE, $msg);

	}

}