<?php
/**
 * Plugin Name: Firebase Notifications
 * Plugin URI: https://github.com/palasthotel/wp-firebase-notifications
 * Description: send messages with firebase messaging
 * Version: 1.0.2
 * Author: Palasthotel <rezeption@palasthotel.de> (in person: Edward Bock)
 * Author URI: http://www.palasthotel.de
 * Requires at least: 5.0
 * Tested up to: 5.2.4
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
 * @property Permissions permissions
 * @property Schedule schedule
 * @property DesktopMessaging desktopMessaging
 */
class Plugin {

	const DOMAIN = "firebase-notifications";

	const TEMPLATE = "firebase-notifications-settings.php";
	const HANDLE_APP_JS = "firebase-notifications-app";
	const HANDLE_FRONTEND_JS = "firebase-notifications-settings-frontend";

	const FILTER_CURRENT_USER_CAN_SEND_MESSAGE = "firebase_notifications_current_user_can_send_message";
	const FILTER_TOPICS = "firebase_notifications_topics";
	const FILTER_MESSAGE = "firebase_notifications_message";
	const FILTER_META_BOX_RESTRICTIONS = "firebase_notifications_meta_box_restrictions";
	const FILTER_SETTINGS_URL = "firebase_notifications_settings_url";

	const ACTION_META_BOX_CUSTOM = "firebase_notifications_meta_box_custom";
	const ACTION_ENQUEUE_META_BOX_ENQUEUE_SCRIPT = "firebase_notification_meta_box_enqueue_script";
	const ACTION_SAVE_MESSAGE = "firebase_notifications_save_message";
	const ACTION_SAVED_MESSAGE = "firebase_notifications_saved_message";
	const ACTION_MESSAGE_SENT = "firebase_notifications_message_sent";

	const OPTION_CONFIG = "_firebase_notifications_config_json";
	const OPTION_POST_TYPES = "_firebase_notifications_post_types";
	const OPTION_WEBAPP_CONFIG = "_firebase_notifications_webapp_config_json";
	const OPTION_DB_SCHEMA = "_firebase_notifications_db_schema";

	const SCHEDULE_SEND_MESSAGED = "firebase_notifications_send_messaged_schedule";

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
		$this->desktopMessaging = new DesktopMessaging($this);
		$this->metaBox = new MetaBox($this);
		$this->topics = new Topics($this);
		$this->toolsPage = new ToolsPage($this);
		$this->settings = new Settings($this);
		$this->permissions = new Permissions();
		$this->schedule = new Schedule($this);

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
		$this->schedule->start();
		flush_rewrite_rules();
	}

	/**
	 * on plugin deactivation
	 */
	function deactivation() {
		$this->schedule->stop();
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