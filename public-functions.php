<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-02
 * Time: 17:16
 */

/**
 * @return \Palasthotel\FirebaseNotifications\Plugin
 */
function firebase_notifications_get_plugin(){
	return \Palasthotel\FirebaseNotifications\Plugin::instance();
}

/**
 * array of topic objects
 * @return array
 */
function firebase_notifications_get_topics(){
	return firebase_notifications_get_plugin()->topics->getTopics();
}