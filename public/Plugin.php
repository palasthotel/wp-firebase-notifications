<?php
/**
 * Plugin Name: Firebase Notifications
 * Plugin URI: https://github.com/palasthotel/wp-firebase-notifications
 * Description: send messages with firebase messaging
 * Version: 1.1.0
 * Author: Palasthotel <rezeption@palasthotel.de> (in person: Edward Bock)
 * Author URI: http://www.palasthotel.de
 * Requires at least: 5.0
 * Tested up to: 5.9.2
 * License: http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * Text Domain:       firebase-notifications
 * Domain Path:       /languages
 *
 * @copyright Copyright (c) 2022, Palasthotel
 * @package Palasthotel\FirebaseNotifications
 */

namespace Palasthotel\FirebaseNotifications;

require_once dirname( __FILE__ ) . "/vendor/autoload.php";

/**
 * @property NotificationsSettingsThemeTemplate $notificationsSettingsThemeTemplate
 * @property MetaBox metaBox
 * @property Topics topics
 * @property Ajax ajax
 * @property CloudMessagingApi cloudMessagingApi
 * @property Database database
 * @property ToolsPage toolsPage
 * @property Settings settings
 * @property DatabaseUpdates databaseUpdates
 * @property Permissions permissions
 * @property Schedule schedule
 * @property DesktopMessaging desktopMessaging
 * @property Assets assets
 * @property REST $rest
 * @property Component\TextdomainConfig $textdomainConfig
 */
class Plugin extends Component\Plugin {

	const DOMAIN = "firebase-notifications";

	const TEMPLATE = "firebase-notifications-settings.php";
	const HANDLE_ADMIN_API_JS = "firebase-notifications-admin-api-script";
	const HANDLE_MESSAGING_JS = "firebase-notifications-script";
	const HANDLE_FRONTEND_API_JS = "firebase-notifications-frontend-api-script";
	const HANDLE_FRONTEND_JS = "firebase-notifications-settings-frontend-script";
	const HANDLE_META_BOX_SCRIPT = "firebase-notifications-meta-box-script";
	const HANDLE_META_BOX_STYLE = "firebase-notifications-meta-box-style";
	const HANDLE_TOOLS_PAGE_SCRIPT = "firebase-notifications-tools-page-script";
	const HANDLE_TOOLS_PAGE_STYLE = "firebase-notifications-tools-page-style";

	const POST_META_DRAFT_TITLE = "firebase_notification_draft_title";
	const POST_META_DRAFT_BODY = "firebase_notification_draft_body";
	const POST_META_DRAFT_PLATFORMS = "firebase_notification_draft_platforms";
	const POST_META_DRAFT_TOPICS = "firebase_notification_draft_topics";
	const POST_META_DRAFT_TOPICS_PARSED = "firebase_notification_draft_topics_parsed";
	const POST_META_DRAFT_SCHEDULE = "firebase_notification_draft_schedule";

	const FILTER_CURRENT_USER_CAN_SEND_MESSAGE = "firebase_notifications_current_user_can_send_message";
	const FILTER_TOPICS = "firebase_notifications_topics";
	const FILTER_MESSAGE = "firebase_notifications_message";
    const FILTER_MESSAGE_PAYLOAD = "firebase_notifications_message_payload";
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
	const OPTION_WEBAPP_NOTIFICATION_ICON = "_firebase_notifications_webapp_notification_icon";
	const OPTION_DB_SCHEMA = "_firebase_notifications_db_schema";

	const SCHEDULE_SEND_MESSAGED = "firebase_notifications_send_messaged_schedule";

	/**
	 * Plugin constructor.
	 */
	function onCreate() {

		/**
		 * load translations
		 */
		$this->textdomainConfig = new Component\TextdomainConfig(
			Plugin::DOMAIN,
			"languages"
		);

		$this->assets                             = new Assets( $this );
		$this->cloudMessagingApi                  = new CloudMessagingApi( $this );
		$this->database                           = new Database();
		$this->databaseUpdates                    = new DatabaseUpdates( $this->database );
		$this->ajax                               = new Ajax( $this );
		$this->rest                               = new REST( $this );
		$this->notificationsSettingsThemeTemplate = new NotificationsSettingsThemeTemplate( $this );
		$this->desktopMessaging                   = new DesktopMessaging( $this );
		$this->metaBox                            = new MetaBox( $this );
		$this->topics                             = new Topics();
		$this->toolsPage                          = new ToolsPage( $this );
		$this->settings                           = new Settings( $this );
		$this->permissions                        = new Permissions();
		$this->schedule                           = new Schedule( $this );
	}

	/**
	 * on plugin activation
	 */
	function onSiteActivation() {
		$this->database->createTables();
		$this->databaseUpdates->setToLatestSchemaVersion();
		$this->notificationsSettingsThemeTemplate->add_endpoint();
		$this->desktopMessaging->add_endpoint();
		$this->schedule->start();
		flush_rewrite_rules();
	}

	/**
	 * on plugin deactivation
	 */
	function onSiteDeactivation() {
		$this->schedule->stop();
		flush_rewrite_rules();
	}
}

Plugin::instance();

require_once dirname( __FILE__ ) . "/public-functions.php";