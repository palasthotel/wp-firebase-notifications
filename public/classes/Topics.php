<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-02
 * Time: 17:12
 */

namespace Palasthotel\FirebaseNotifications;


class Topics {

	public function getTopics() {
		return apply_filters(
			Plugin::FILTER_TOPICS,
			array(
				(object) array(
					// allowed format for id [a-zA-Z0-9-_.~%]{1,900}
					"id"    => "default",
					"name" => "Default topic",
				),
			)
		);
	}

	public function getTopicIds(){
		return array_map(function($item){return $item->id; }, $this->getTopics());
	}
}