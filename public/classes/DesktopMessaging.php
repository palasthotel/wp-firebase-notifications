<?php


namespace Palasthotel\FirebaseNotifications;


/**
 * @property Plugin plugin
 */
class DesktopMessaging {

	const PARAM_KEY = "firebase-service-worker";

	const PARAM_VALUE = "render-service-worker-js";

	/**
	 * DesktopMessaging constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

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
			header( 'Content-Type: application/javascript' );
			ob_start();
			$version = Assets::FB_VERSION;
			echo "const fbVersion = '$version';\n";
			echo "const messagingConfig = " . json_encode( $this->plugin->settings->getWebappConfig() ) . ";\n";
			$iconUrl = $this->plugin->settings->getNotificationIconURL();
			echo "const notificationIconUrl = '$iconUrl';\n";
			echo file_get_contents( $this->plugin->path . "/js/firebase-messaging-sw.js" );
			exit;
		}
	}


}