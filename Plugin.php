<?php
/**
 * Plugin Name: Firebase Notifications
 * Plugin URI: https://github.com/palasthotel/wp-firebase-notifications
 * Description: send messages with firebase messaging
 * Version: 0.6
 * Author: Palasthotel <rezeption@palasthotel.de> (in person: Edward Bock)
 * Author URI: http://www.palasthotel.de
 * Requires at least: 5.0
 * Tested up to: 5.2.2
 * License: http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * Text Domain:       firebase-notifications
 * Domain Path:       /languages
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
 * @property DatabaseUpdates databaseUpdates
 */
class Plugin {

	const DOMAIN = "firebase-notifications";

	const TEMPLATE = "firebase-notifications-settings.php";
	const HANDLE_FRONTEND_JS = "firebase-notifications-settings-frontend";

	const FILTER_TOPICS = "firebase_notifications_topics";
	const FILTER_MESSAGE = "firebase_notifications_message";

	const ACTION_META_BOX_CUSTOM = "firebase_notifications_meta_box_custom";
	const ACTION_ENQUEUE_META_BOX_ENQUEUE_SCRIPT = "firebase_notification_meta_box_enqueue_script";
	const ACTION_SAVE_MESSAGE = "firebase_notifications_save_message";
	const ACTION_SAVED_MESSAGE = "firebase_notifications_saved_message";
	const ACTION_MESSAGE_SENT = "firebase_notifications_message_sent";

	const OPTION_CONFIG = "_firebase_notifications_config_json";
	const OPTION_DB_SCHEMA = "_firebase_notifications_db_schema";

	/**
	 * Plugin constructor.
	 */
	private function __construct() {

		/**
		 * load translations
		 */
		load_plugin_textdomain(
			Plugin::DOMAIN,
			FALSE,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		$this->path = plugin_dir_path(__FILE__);
		$this->url  = plugin_dir_url(__FILE__);
		$this->basename = plugin_basename(__FILE__);

		require_once dirname( __FILE__ ) . "/vendor/autoload.php";

		$this->cloudMessagingApi = new CloudMessagingApi($this);
		$this->database = new Database();
		$this->databaseUpdates = new DatabaseUpdates($this->database);
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
		$this->databaseUpdates->setToLatestSchemaVersion();
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