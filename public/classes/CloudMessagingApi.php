<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-01
 * Time: 17:42
 */

namespace Palasthotel\FirebaseNotifications;


use Exception;
use Palasthotel\FirebaseNotifications\Vendor\Kreait\Firebase\Contract\Messaging;
use Palasthotel\FirebaseNotifications\Vendor\Kreait\Firebase\Exception\FirebaseException;
use Palasthotel\FirebaseNotifications\Vendor\Kreait\Firebase\Exception\MessagingException;
use Palasthotel\FirebaseNotifications\Vendor\Kreait\Firebase\Factory;
use Palasthotel\FirebaseNotifications\Vendor\Kreait\Firebase\Messaging\CloudMessage;
use Palasthotel\FirebaseNotifications\Vendor\Kreait\Firebase\Messaging\TopicSubscriptions;

class CloudMessagingApi {

	/**
	 * the decoded service account JSON from the settings
	 *
	 * @var array|null
	 */
	private $serviceAccount;
    /**
     * @var Messaging
     */
	private $messaging;
    public Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * @return bool
	 */
	public function hasConfiguration(){
		return $this->getServiceAccount() != null;
	}

	/**
	 * The service account as configured in the settings. Firebase SDK 7 has no
	 * ServiceAccount::fromValue() anymore, the factory takes the decoded array.
	 *
	 * @return array|null
	 */
	public function getServiceAccount(){
		if($this->serviceAccount == null){

			$config = $this->plugin->settings->getConfig(true);
			if(!is_array($config) || empty($config)) return null;
			$this->serviceAccount = $config;
		}
		return $this->serviceAccount;
	}

    /**
     * @return Messaging
     */
	public function getMessaging(){
	    if($this->messaging == null){
            $this->messaging = ( new Factory )->withServiceAccount(
                $this->getServiceAccount()
            )->createMessaging();
        }

        return $this->messaging;
    }

	/**
	 * Firebase SDK 7 sends a plain array as it is. Building the CloudMessage keeps the
	 * validation SDK 5 did for arrays, e.g. that data values are strings.
	 *
	 * @param Message $msg
	 *
	 * @return CloudMessage
	 * @throws Exception
	 */
	public function getCloudMessage( $msg ) {
		return CloudMessage::fromArray( $msg->getCloudMessageArray() );
	}

	/**
	 * send message via firebase cloud messaging
	 *
	 * @param Message $msg
	 *
	 * @return array
	 * @throws FirebaseException
	 * @throws MessagingException
	 * @throws Exception
	 */
	function send( $msg ) {
		$cloudMessage = $this->getCloudMessage( $msg );
		if(WP_DEBUG){
			return array(
				"WP_DEBUG" => true,
				"info" => __("No message was sent", Plugin::DOMAIN),
				"msg" => $msg
			);
		}
		return $this->getMessaging()->send($cloudMessage);
	}

	/**
	 * @param string $token
	 *
	 * @return TopicSubscriptions
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