<?php

use Palasthotel\FirebaseNotifications\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function firebase_notifications_get_plugin(): Plugin{
	return Plugin::instance();
}

function firebase_notifications_get_topics(): array {
	return firebase_notifications_get_plugin()->topics->getTopics();
}