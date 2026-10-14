<?php


namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Palasthotel\FirebaseNotifications\Component\Component;

/**
 * @property Plugin plugin
 */
class DesktopMessaging extends Component {

	const PARAM_KEY = "firebase-service-worker";

	const PARAM_VALUE = "render-service-worker-js";

	public function onCreate( ) {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'sniff_requests' ), 0 );
	}

	/**
	 * service worker endpoint
	 */
	public function add_endpoint() {
		add_rewrite_rule(
			'^firebase-messaging-sw\.js$',
			'index.php?' . self::PARAM_KEY . '=' . self::PARAM_VALUE,
			'top'
		);
	}

	/**
	 * Add public query vars
	 *
	 * @param array $vars List of current public query vars
	 *
	 * @return array $vars
	 */
	public function add_query_vars( $vars ) {
		$vars[] = self::PARAM_KEY;

		return $vars;
	}

	/**
	 * Sniff Requests
	 */
	public function sniff_requests() {

		// with no valid configuration we can skip
		if ( ! $this->plugin->settings->isWebappConfigValid() ) {
			return;
		}

		global $wp;
		if ( isset( $wp->query_vars[ self::PARAM_KEY ] ) && $wp->query_vars[ self::PARAM_KEY ] == self::PARAM_VALUE ) {
			header( 'Content-Type: application/javascript; charset=utf-8' );
			// A JavaScript response, not HTML: the values are JSON-encoded into it,
			// HTML escaping would break them.
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "const fbVersion = " . wp_json_encode( Assets::FB_VERSION ) . ";\n";
			echo "const messagingConfig = " . wp_json_encode( $this->plugin->settings->getWebappConfig() ) . ";\n";
			echo "const notificationIconUrl = " . wp_json_encode( $this->plugin->settings->getNotificationIconURL() ) . ";\n";
			echo file_get_contents( $this->plugin->path . "js/firebase-messaging-sw.js" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a file of the plugin itself
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}
	}


}