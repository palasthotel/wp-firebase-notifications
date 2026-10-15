<?php

namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Palasthotel\FirebaseNotifications\Component\Component;

class REST extends Component {

	const NAMESPACE = "firebase-notifications/v1";

	public function onCreate() {
		add_action( 'rest_api_init', [ $this, 'init' ] );
	}

	public function init() {
		register_rest_route(
			static::NAMESPACE,
			'/count/?',
			array(
				'methods'             => "GET",
				'callback'            => array( $this, 'count' ),
				'permission_callback' => array( $this, 'count_permission' ),
				'args'                => [
					"since" => array(
						'required'          => false,
						'type'              => 'number',
						'default'           => strtotime("today"),
						'validate_callback' => function ( $param, $request, $key ) {
							return $param < time();
						},
					),
				]
			)
		);
	}

	/**
	 * The count is public by default, as it always was: apps read it without
	 * logging in. Sites can restrict it with the filter
	 * firebase_notifications_rest_count_permission, e.g.
	 * add_filter( 'firebase_notifications_rest_count_permission', fn() => current_user_can( 'edit_posts' ) );
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function count_permission( \WP_REST_Request $request ) {
		/**
		 * Whether the current request may read the number of sent notifications.
		 *
		 * @param bool|\WP_Error   $allowed default true
		 * @param \WP_REST_Request $request
		 */
		$allowed = apply_filters( Plugin::FILTER_REST_COUNT_PERMISSION, true, $request ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant
		return is_wp_error( $allowed ) ? $allowed : (bool) $allowed;
	}

	public function count( \WP_REST_Request $request ) {
		$since = $request->get_param( "since" );
		return $this->plugin->database->count($since);
	}
}