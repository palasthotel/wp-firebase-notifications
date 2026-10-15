<?php

namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Topics {

	/**
	 * The topics as the settings store them, or the "default" topic as long as
	 * there are none - what the plugin always offered before topics had settings.
	 *
	 * @return object[] each with id and name
	 */
	public function getStoredTopics() {
		$topics = array();
		$stored = get_option( Plugin::OPTION_TOPICS, array() );
		foreach ( is_array( $stored ) ? $stored : array() as $topic ) {
			if ( is_array( $topic ) && isset( $topic["id"] ) && is_string( $topic["id"] ) && preg_match( Validation::TOPIC_PATTERN, $topic["id"] ) ) {
				$topics[] = (object) array(
					"id"   => $topic["id"],
					"name" => isset( $topic["name"] ) && "" !== $topic["name"] ? (string) $topic["name"] : $topic["id"],
				);
			}
		}
		if ( count( $topics ) === 0 ) {
			$topics[] = (object) array(
				"id"   => "default",
				"name" => "Default topic",
			);
		}
		return $topics;
	}

	/**
	 * The topics the site offers: the stored ones, changed by the filter
	 * firebase_notifications_topics.
	 *
	 * @return object[] each with id - allowed format [a-zA-Z0-9-_.~%]{1,900} - and name
	 */
	public function getTopics() {
		return apply_filters(
			Plugin::FILTER_TOPICS, // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant
			$this->getStoredTopics()
		);
	}

	public function getTopicIds(){
		return array_map(function($item){return $item->id; }, $this->getTopics());
	}
}
