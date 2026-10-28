<?php
/**
 * Plugin Name:       Palasthotel Push Notifications for Firebase
 * Plugin URI:        https://github.com/palasthotel/wp-firebase-notifications
 * Description:       Send push notifications from the post editor to Android, iOS and web apps via Firebase Cloud Messaging topics.
 * Version:           2.0.0
 * Requires at least: 5.3
 * Requires PHP:      8.1
 * Author:            Palasthotel
 * Author URI:        https://palasthotel.de
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       palasthotel-push-notifications-for-firebase
 * Domain Path:       /languages
 *
 * @copyright Copyright (c) 2022, Palasthotel
 * @package Palasthotel\FirebaseNotifications
 */

namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The Firebase SDK and its dependencies are bundled with prefixed namespaces
// (Palasthotel\FirebaseNotifications\Vendor\...) in vendor/prefixed/, so they never
// collide with another copy of guzzle, google/auth or kreait/firebase-php on the site.
// vendor/autoload.php only loads the plugin's own classes. Both are written by the
// build (bin/prefix-dependencies.sh) and the release pack, they are not in git.
$firebase_notifications_autoloaders = array(
	__DIR__ . '/vendor/prefixed/autoload.php',
	__DIR__ . '/vendor/autoload.php',
);
foreach ( $firebase_notifications_autoloaders as $firebase_notifications_autoloader ) {
	if ( ! is_readable( $firebase_notifications_autoloader ) ) {
		add_action( 'admin_notices', function () {
			// for those who can fix it, where plugins are managed
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! current_user_can( 'activate_plugins' ) || ! $screen || ! in_array( $screen->id, array( 'plugins', 'plugins-network', 'dashboard' ), true ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'Palasthotel Push Notifications for Firebase is incomplete: its bundled dependencies are missing. Please install the release ZIP or run bin/prefix-dependencies.sh.', 'palasthotel-push-notifications-for-firebase' )
				. '</p></div>';
		} );
		return;
	}
}
foreach ( $firebase_notifications_autoloaders as $firebase_notifications_autoloader ) {
	require_once $firebase_notifications_autoloader;
}
unset( $firebase_notifications_autoloaders, $firebase_notifications_autoloader );

class Plugin extends Component\Plugin {

    public NotificationsSettingsThemeTemplate $notificationsSettingsThemeTemplate;
    public MetaBox $metaBox;
    public Topics $topics;
    public Ajax $ajax;
    public CloudMessagingApi $cloudMessagingApi;
    public Database $database;
    public ToolsPage $toolsPage;
    public Settings $settings;
    public DatabaseUpdates $databaseUpdates;
    public Permissions $permissions;
    public Schedule $schedule;
    public DesktopMessaging $desktopMessaging;
    public Assets $assets;
    public REST $rest;

	/**
	 * Internal prefix of the AJAX actions, the meta box id and the settings anchor.
	 * Not the text domain: since the plugin is called "Palasthotel Push Notifications
	 * for Firebase" its text domain is the wordpress.org slug
	 * 'palasthotel-push-notifications-for-firebase', written as a literal in every
	 * gettext call. The value stays so that apps and scripts calling the AJAX actions
	 * keep working.
	 */
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
	const FILTER_REST_COUNT_PERMISSION = "firebase_notifications_rest_count_permission";

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
	const OPTION_TOPICS = "_firebase_notifications_topics";

	/**
	 * Name of the constant for wp-config.php with the service account: the path to
	 * its JSON file or the JSON itself. If defined it is used instead of the
	 * service account in the settings (OPTION_CONFIG).
	 */
	const CONSTANT_SERVICE_ACCOUNT = "FIREBASE_NOTIFICATIONS_SERVICE_ACCOUNT";

	const SCHEDULE_SEND_MESSAGED = "firebase_notifications_send_messaged_schedule";

	/**
	 * Plugin constructor.
	 */
	function onCreate() {

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
