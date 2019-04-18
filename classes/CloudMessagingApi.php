<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-01
 * Time: 17:42
 */

namespace Palasthotel\FirebaseNotifications;


use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

/**
 * @property Plugin plugin
 */
class CloudMessagingApi {

	/**
	 * @var \Kreait\Firebase
	 */
	private $firebase;

	/**
	 * CloudMessagingApi constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * @return bool
	 */
	public function hasConfiguration(){
		return $this->getFirebase() != null;
	}

	/**
	 * @return \Kreait\Firebase
	 */
	public function getFirebase(){
		if($this->firebase == null){

			$config = $this->plugin->settings->getConfig(true);
			if($config == null) return null;

			$serviceAccount =ServiceAccount::fromArray($config);
			$this->firebase = ( new Factory )->withServiceAccount( $serviceAccount )
			                                 ->create();
		}
		return $this->firebase;
	}

	/**
	 * send message via firebase cloud messaging
	 *
	 * @param Message $msg
	 *
	 * @return array
	 * @throws \Exception
	 */
	function send( $msg ) {
		return $this->firebase->getMessaging()->send(
			$msg->getCloudMessageArray()
		);
	}
}