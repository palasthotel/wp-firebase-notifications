<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-01
 * Time: 17:42
 */

namespace Palasthotel\FirebaseNotifications;


use Exception;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

/**
 * @property Plugin plugin
 */
class CloudMessagingApi {

	private ?Factory $factory;

	private ?Messaging $messaging;

	/**
	 * CloudMessagingApi constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;

	}

	private function getFactory(): Factory {
		if($this->factory == null){
			$this->factory = ( new Factory )->withServiceAccount(
				$this->plugin->settings->getConfig(true)
			);
		}
		return $this->factory;
	}

	public function hasConfiguration(): bool{
		return !empty($this->getFactory()->getDebugInfo()["projectId"]);
	}

	public function getMessaging(): Messaging{
	    if($this->messaging == null){
            $this->messaging = $this->getFactory()->createMessaging();
        }

        return $this->messaging;
    }

	/**
	 * send message via firebase cloud messaging
	 *
	 * @return array
	 * @throws FirebaseException
	 * @throws MessagingException
	 * @throws Exception
	 */
	function send( Message $msg ): array {
		$arr = $msg->getCloudMessageArray();
		if(WP_DEBUG){
			return array(
				"WP_DEBUG" => true,
				"info" => __("No message was sent", "CloudMessagingApi class", Plugin::DOMAIN),
				"msg" => $msg
			);
		}
		return $this->getMessaging()->send($arr);
	}

	/**
	 * @param string $token
	 *
	 * @return Messaging\TopicSubscriptions
	 * @throws FirebaseException
	 */
	public function getSubscriptions($token){
		$instance = $this->getMessaging()->getAppInstance($token);
		return $instance->topicSubscriptions();
	}

	/**
	 * @param string $topic
	 * @param string $token
	 *
	 * @return array|false
	 */
	public function subscribe($topic, $token){

		try{
			return $this->getMessaging()->subscribeToTopic($topic, $token);
		} catch ( MessagingException $e ) {
			error_log($e->getMessage());
			return false;
		} catch ( FirebaseException $e ) {
			error_log($e->getMessage());
			return false;
		}
	}

	/**
	 * @param string $topic
	 * @param string $token
	 *
	 * @return array|false
	 */
	public function unsubscribe($topic, $token){
		try {
			return $this->getMessaging()->unsubscribeFromTopic( $topic, $token );
		} catch ( MessagingException $e ) {
			error_log($e->getMessage());
			return false;
		} catch ( FirebaseException $e ) {
			error_log($e->getMessage());
			return false;
		}
	}
}