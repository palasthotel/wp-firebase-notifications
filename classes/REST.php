<?php

namespace Palasthotel\FirebaseNotifications;

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
				'permission_callback' => '__return_true',
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

	public function count( \WP_REST_Request $request ) {
		$since = $request->get_param( "since" );
		return $this->plugin->database->count($since);
	}
}