<?php


namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Permissions {

	public function canSendMessages(){
		return apply_filters(
			Plugin::FILTER_CURRENT_USER_CAN_SEND_MESSAGE, // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant
			current_user_can("publish_posts")
		);
	}

}