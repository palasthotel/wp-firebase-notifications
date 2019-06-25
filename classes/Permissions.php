<?php


namespace Palasthotel\FirebaseNotifications;


class Permissions {

	public function canSendMessages(){
		return apply_filters(
			Plugin::FILTER_CURRENT_USER_CAN_SEND_MESSAGE,
			current_user_can("editor")
		);
	}

}