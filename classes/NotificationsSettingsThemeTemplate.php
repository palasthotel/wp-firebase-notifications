<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-03-29
 * Time: 10:39
 */

namespace Palasthotel\FirebaseNotifications;


/**
 * @property Plugin plugin
 */
class NotificationsSettingsThemeTemplate {

	const URL = "__app/notifications";

	const PARAM_KEY = "firebase_notifications";

	const PARAM_VALUE = "show";

	/**
	 * @return string
	 */
	public static function url() {
		return  '/' . self::urlRelative() . '/';
	}

	/**
	 * relative url no front slashes
	 * @return string
	 */
	public static function urlRelative(){
		return ltrim(rtrim((apply_filters(Plugin::FILTER_SETTINGS_URL, self::URL )),'/\\'), '/\\');
	}

	/**
	 * FrontendNotificationSettings constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'init', array( $this, 'add_endpoint' ) );

		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		add_action( 'template_include', array( $this, 'change_template' ) );

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
	 * Add API Endpoint
	 * This is where the magic happens - brush up on your regex skillz
	 *
	 * @return void
	 */
	public function add_endpoint() {
		add_rewrite_rule(
			'^' . self::urlRelative() . '$',
			'index.php?' . self::PARAM_KEY . '=' . self::PARAM_VALUE,
			'top'
		);
	}

	/**
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public function change_template( $template ) {
		global $wp;
		if ( isset( $wp->query_vars[ self::PARAM_KEY ] ) && $wp->query_vars[ self::PARAM_KEY ] == self::PARAM_VALUE ) {

			if(isset($_GET["IS-APP-TEST"]) && $_GET["IS-APP-TEST"] == "true"){
				$this->enqueueTestScript();
			}
			$this->enqueueAppScript();

			//Check theme directory first
			$newTemplate = locate_template( array( Plugin::TEMPLATE ) );
			if ( '' != $newTemplate ) {
				return $newTemplate;
			}

			//Check plugin directory next
			$newTemplate = $this->plugin->path . 'templates/' . Plugin::TEMPLATE;
			if ( file_exists( $newTemplate ) ) {
				return $newTemplate;
			}
		}

		return $template;
	}

	public function enqueue_scripts(){
		$deps = array();
		if($this->plugin->settings->isWebappConfigValid()){
			$deps[] = Plugin::HANDLE_MESSAGING_JS;
		}
		wp_register_script(
			Plugin::HANDLE_APP_JS,
			$this->plugin->url . "/js/app.js",
			$deps,
			filemtime( $this->plugin->path . "/js/app.js"),
			true
		);
	}

	public function enqueueAppScript(){
		$this->enqueueJS(
			Plugin::HANDLE_FRONTEND_JS,
			"frontend-firebase-notifications-settings.js",
			array( "jquery", Plugin::HANDLE_APP_JS )
		);
	}
	public function enqueueTestScript(){
		$this->enqueueJS(
			Plugin::HANDLE_FRONTEND_JS."_test",
			"test.frontend-firebase-notifications.js"
		);
	}

	private function enqueueJS($handle, $filename, $deps = array()){
		wp_enqueue_script(
			$handle,
			$this->plugin->url . "/js/$filename",
			$deps,
			filemtime( $this->plugin->path . "/js/$filename"),
			true
		);
	}


}