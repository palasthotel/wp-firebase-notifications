<?php
/**
 * Plugin Name: Firebase Notifications
 * Plugin URI: https://github.com/palasthotel/grid-wordpress
 * Description: We will see...
 * Version: 0.4
 * Author: Palasthotel <rezeption@palasthotel.de> (in person: Edward Bock)
 * Author URI: http://www.palasthotel.de
 * Requires at least: 5.0
 * Tested up to: 5.2
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
 * @property Database database
 * @property ToolsPage toolsPage
 * @property Settings settings
 * @property string basename
 */
class Plugin {

	const DOMAIN = "firebase-notifications";

	const TEMPLATE = "firebase-notifications-settings.php";
	const HANDLE_FRONTEND_JS = "firebase-notifications-settings-frontend";

	const FILTER_TOPICS = "firebase_notifications_topics";
	const FILTER_TOPIC_ANDROID = "firebase_notifications_topic_android";
	const FILTER_TOPIC_IOS = "firebase_notifications_topic_ios";
	const FILTER_MESSAGE = "firebase_notifications_message";

	const ACTION_MESSAGE_ADD = "firebase_notifications_message_add";
	const ACTION_MESSAGE_SENT = "firebase_notifications_message_sent";

	const OPTION_CONFIG = "_firebase_notifications_config_json";

	/**
	 * Plugin constructor.
	 */
	private function __construct() {
		$this->path = plugin_dir_path(__FILE__);
		$this->url  = plugin_dir_url(__FILE__);
		$this->basename = plugin_basename(__FILE__);

		require_once dirname( __FILE__ ) . "/vendor/autoload.php";

		$this->cloudMessagingApi = new CloudMessagingApi($this);
		$this->database = new Database();
		$this->ajax = new Ajax($this);
		$this->notificationsSettingsThemeTemplate = new NotificationsSettingsThemeTemplate($this);
		$this->metaBox = new MetaBox($this);
		$this->topics = new Topics($this);
		$this->toolsPage = new ToolsPage($this);
		$this->settings = new Settings($this);

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
		$this->database->create();
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