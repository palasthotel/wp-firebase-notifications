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

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'sniff_requests' ), 0 );

		add_action( 'wp_enqueue_scripts', function () {
			wp_enqueue_script( 'firebase-notifications-script' );
		} );
	}

	/**
	 * initialize components
	 */
	public function init() {

		// if there is no valid configuration we can skip here
		if ( ! $this->plugin->settings->isWebappConfigValid() ) {
			return;
		}

		// ------------------
		// register scripts
		// ------------------
		wp_register_script(
			"firebase-core",
			$this->plugin->url . "/js/firebase-app.7.17.2.js",
			array(),
			"7.3.0",
			true
		);
		wp_register_script(
			"firebase-messaging",
			$this->plugin->url . "/js/firebase-messaging.7.17.2.js",
			array( "firebase-core" ),
			"7.3.0",
			true
		);
		wp_register_script(
			Plugin::HANDLE_MESSAGING_JS,
			$this->plugin->url . "/js/desktop-messaging.js",
			array( "firebase-core", "firebase-messaging" ),
			filemtime( $this->plugin->path . "/js/desktop-messaging.js" )
		);
		wp_localize_script(
			Plugin::HANDLE_MESSAGING_JS,
			"FirebaseMessagingWebapp",
			array(
				"config" => $this->plugin->settings->getWebappConfig( true ),
				"iconUrl" => $this->plugin->settings->getNotificationIconURL(),
				"ajax" => array(
					"subscribe" => admin_url("admin-ajax.php?action=".$this->plugin->ajax->action_subscribe),
					"unsubscribe" => admin_url("admin-ajax.php?action=".$this->plugin->ajax->action_unsubscribe),
					"topics" => admin_url("admin-ajax.php?action=".$this->plugin->ajax->action_topics),
				)
			)
		);

	}

	/**
	 * service worker endpoint
	 */
	public function add_endpoint(){
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
			$id = $this->plugin->settings->getWebappConfig()->messagingSenderId;
			$iconUrl = $this->plugin->settings->getNotificationIconURL();
			echo "const messagingSenderId = '$id'\n";
			echo "const notificationIconUrl = '$iconUrl'\n";
			echo file_get_contents( $this->plugin->path . "/js/firebase-messaging-sw.js" );
			exit;
		}
	}


}