<?php
/**
 * Plugin Name: Firebase Notifications
 * Plugin URI: https://github.com/palasthotel/grid-wordpress
 * Description: We will see...
 * Version: 0.2
 * Author: Palasthotel <rezeption@palasthotel.de> (in person: Edward Bock)
 * Author URI: http://www.palasthotel.de
 * Requires at least: 5.0
 * Tested up to: 5.1.1
 * License: http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @copyright Copyright (c) 2019, Palasthotel
 * @package Palasthotel\FirebaseNotifications
 */

namespace Palasthotel\FirebaseNotifications;

/**
 * @property string $path
 * @property string url
 * @property NotificationsSettingsThemeTemplate $notificationsSettingsThemeTemplate
 * @property MetaBox metaBox
 * @property Topics topics
 * @property Ajax ajax
 * @property CloudMessagingApi cloudMessagingApi
 */
class Plugin {

	const DOMAIN = "firebase-notifications";

	const TEMPLATE = "firebase-notifications-settings.php";
	const HANDLE_FRONTEND_JS = "firebase-notifications-settings-frontend";

	const FILTER_TOPICS = "firebase_notifications_topics";

	/**
	 * Plugin constructor.
	 */
	private function __construct() {
		$this->path = plugin_dir_path(__FILE__);
		$this->url  = plugin_dir_url(__FILE__);

		require_once dirname( __FILE__ ) . "/vendor/autoload.php";

		$this->cloudMessagingApi = new CloudMessagingApi($this);
		$this->ajax = new Ajax($this);
		$this->notificationsSettingsThemeTemplate = new NotificationsSettingsThemeTemplate($this);
		$this->metaBox = new MetaBox($this);
		$this->topics = new Topics($this);

		/**
		 * on activate or deactivate plugin
		 */
		register_activation_hook( __FILE__, array( $this, "activation" ) );
		register_deactivation_hook( __FILE__, array( $this, "deactivation" ) );
	}

	/**
	 * on plugin activation
	 */
	function activation() {
		$this->notificationsSettingsThemeTemplate->add_endpoint();
		flush_rewrite_rules();
	}

	/**
	 * on plugin deactivation
	 */
	function deactivation() {
		flush_rewrite_rules();
	}

	/**
	 * @var Plugin $instance
	 */
	private static $instance;

	/**
	 * @returns Plugin
	 */
	public static function instance(){
		if(self::$instance == null) self::$instance = new Plugin();
		return self::$instance;
	}

}
Plugin::instance();

require_once dirname(__FILE__)."/public-functions.php";