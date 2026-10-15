<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-08
 * Time: 19:34
 */

namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Palasthotel\FirebaseNotifications\Component\Component;

/**
 * @property Plugin plugin
 */
class Settings extends Component {

	public function onCreate() {
		add_filter('plugin_action_links_' . $this->plugin->basename, array($this, 'add_action_links'));
		add_action('admin_init', array($this,'custom_settings'));
		add_action('admin_enqueue_scripts', array($this, 'load_wp_media_files') );
	}

	/**
	 * action link to settings on plugins list page
	 * @param $links
	 *
	 * @return array
	 */
	public function add_action_links($links){
		return array_merge($links, array(
			'<a href="' . esc_url( admin_url( 'options-writing.php#' . Plugin::DOMAIN ) ) . '">' . esc_html__( 'Settings', 'palasthotel-push-notifications-for-firebase' ) . '</a>'
		));
	}

	/**
	 * register settings
	 */
	public function custom_settings() {
		add_settings_section(
			'firebase-notifications-settings', // ID
			'Firebase Notifications', // Section title
			function(){
				echo "<span id='" . esc_attr( Plugin::DOMAIN ) . "'></span>";
			}, // Callback for your function
			'writing' // Location (Settings > Permalinks)
		);

		add_settings_field(
			Plugin::OPTION_CONFIG,
			__('Google Services', 'palasthotel-push-notifications-for-firebase'),
			array($this, 'render_services_config_field'),
			'writing',
			'firebase-notifications-settings'
		);
		register_setting(
			'writing',
			Plugin::OPTION_CONFIG,
			array($this, 'sanitize_config')
		);

		add_settings_field(
			Plugin::OPTION_POST_TYPES,
			__('Post types', 'palasthotel-push-notifications-for-firebase'),
			array($this, 'render_post_types'),
			'writing',
			'firebase-notifications-settings'
		);
		register_setting(
			'writing',
			Plugin::OPTION_POST_TYPES,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_post_types' ),
			)
		);


		add_settings_field(
			Plugin::OPTION_WEBAPP_CONFIG,
			__('Webapp', 'palasthotel-push-notifications-for-firebase'),
			array($this, 'render_webapp_config_field'),
			'writing',
			'firebase-notifications-settings'
		);
		register_setting(
			'writing',
			Plugin::OPTION_WEBAPP_CONFIG,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_webapp_config' ),
			)
		);

		add_settings_field(
			Plugin::OPTION_WEBAPP_NOTIFICATION_ICON,
			__('Notification Icon', 'palasthotel-push-notifications-for-firebase'),
			array($this, 'render_notification_icon_field'),
			'writing',
			'firebase-notifications-settings'
		);
		register_setting(
			'writing',
			Plugin::OPTION_WEBAPP_NOTIFICATION_ICON,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_notification_icon' ),
			)
		);

	}

	/**
	 * Keeps the private key when it comes back masked ("***"), and the old value if
	 * the field was not sent (the service account comes from wp-config.php) or the
	 * JSON is invalid.
	 *
	 * @param mixed $option
	 *
	 * @return string
	 */
	public function sanitize_config($option){
		$old = (string) get_option( Plugin::OPTION_CONFIG, "" );
		if ( null === $option || ! is_string( $option ) ) {
			return $old;
		}
		$option = trim( $option );
		if ( "" === $option ) {
			return "";
		}
		$json = json_decode( $option );
		if ( ! is_object( $json ) ) {
			add_settings_error(
				Plugin::OPTION_CONFIG,
				'invalid-json',
				__( 'The service account is not valid JSON and was not saved.', 'palasthotel-push-notifications-for-firebase' )
			);
			return $old;
		}
		$old_json = json_decode( $old );
		foreach ( array( "private_key_id", "private_key" ) as $key ) {
			if ( isset( $json->{$key} ) && "***" === $json->{$key} ) {
				if ( is_object( $old_json ) && isset( $old_json->{$key} ) ) {
					$json->{$key} = $old_json->{$key};
				} else {
					unset( $json->{$key} );
				}
			}
		}
		return (string) wp_json_encode( $json );
	}

	/**
	 * Whether the service account comes from the constant in wp-config.php.
	 *
	 * @return bool
	 */
	public function isConfigFromConstant(){
		return defined( Plugin::CONSTANT_SERVICE_ACCOUNT );
	}

	/**
	 * The service account JSON: from the constant FIREBASE_NOTIFICATIONS_SERVICE_ACCOUNT
	 * if it is defined - the JSON itself or the path to the JSON file -, otherwise from
	 * the settings.
	 *
	 * @return string
	 */
	private function getConfigJson(){
		if ( ! $this->isConfigFromConstant() ) {
			return (string) get_option( Plugin::OPTION_CONFIG, "" );
		}
		$value = constant( Plugin::CONSTANT_SERVICE_ACCOUNT );
		if ( ! is_string( $value ) ) {
			return "";
		}
		$value = trim( $value );
		if ( "{" === substr( $value, 0, 1 ) ) {
			return $value;
		}
		if ( "" !== $value && is_file( $value ) && is_readable( $value ) ) {
			return (string) file_get_contents( $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file the site owner configured, no URL
		}
		return "";
	}

	/**
	 * Only post types that exist, as their keys.
	 *
	 * @param mixed $value
	 *
	 * @return string[]
	 */
	public function sanitize_post_types( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$post_types = array();
		foreach ( $value as $post_type ) {
			$post_type = sanitize_key( (string) $post_type );
			if ( post_type_exists( $post_type ) ) {
				$post_types[] = $post_type;
			}
		}
		return array_values( array_unique( $post_types ) );
	}

	/**
	 * The web app config has to be a JSON object; anything else keeps the old value.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function sanitize_webapp_config( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( '' === $value ) {
			return '';
		}
		$config = json_decode( $value );
		if ( ! is_object( $config ) ) {
			add_settings_error(
				Plugin::OPTION_WEBAPP_CONFIG,
				'invalid-json',
				__( 'The web app configuration is not valid JSON and was not saved.', 'palasthotel-push-notifications-for-firebase' )
			);
			return (string) get_option( Plugin::OPTION_WEBAPP_CONFIG, '' );
		}
		return (string) wp_json_encode( $config );
	}

	/**
	 * The notification icon is an attachment ID.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function sanitize_notification_icon( $value ) {
		$id = absint( $value );
		return $id > 0 ? (string) $id : '';
	}

	/**
	 * @param bool $assoc
	 *
	 * @return object|array|null
	 */
	public function getConfig($assoc = false){
		return json_decode( $this->getConfigJson(), $assoc );
	}

	/**
	 * @return bool
	 */
	public function isConfigValid(){
		$config = $this->getConfig();
		if($config == null) return false;
		if(!isset($config->private_key_id)) return false;
		if(!isset($config->private_key)) return false;
		if(!isset($config->client_id)) return false;
		return true;
	}

	/**
	 * @param bool $assoc
	 *
	 * @return object|array|null
	 */
	public function getWebappConfig($assoc = false){
		$config = get_option(Plugin::OPTION_WEBAPP_CONFIG, "");
		return json_decode($config, $assoc);
	}

	/**
	 *
	 * @return string
	 */
	public function getNotificationIconImageId(){
		return $this->sanitize_notification_icon( get_option( Plugin::OPTION_WEBAPP_NOTIFICATION_ICON, "" ) );
	}

	/**
	 * @return string
	 */
	public function getNotificationIconURL(){
		$iconId = $this->plugin->settings->getNotificationIconImageId();
		$iconUrl = "";
		if(!empty($iconId)){
			$url = wp_get_attachment_image_url($iconId);
			if($url !== false) $iconUrl = $url;
		}
		return $iconUrl;
	}

	/**
	 * @return bool
	 */
	public function isWebappConfigValid(){
		$config = $this->getWebappConfig();
		if($config == null) return false;
		if(!isset($config->apiKey)) return false;
		if(!isset($config->authDomain)) return false;
		if(!isset($config->projectId)) return false;
		if(!isset($config->storageBucket)) return false;
		if(!isset($config->messagingSenderId)) return false;
		if(!isset($config->appId)) return false;
		if(!isset($config->measurementId)) return false;
		return true;
	}

	/**
	 * render the setting field
	 */
	public function render_services_config_field(){
		if ( $this->isConfigFromConstant() ) {
			// managed in wp-config.php: never print the service account, not even masked
			$config = $this->getConfig();
			echo '<p>';
			echo esc_html(
				sprintf(
					/* translators: %s: name of the PHP constant */
					__( 'The service account is set in wp-config.php with the constant %s and cannot be changed here.', 'palasthotel-push-notifications-for-firebase' ),
					Plugin::CONSTANT_SERVICE_ACCOUNT
				)
			);
			echo '</p><p class="description">';
			if ( $this->isConfigValid() ) {
				echo '✅ ' . esc_html(
					sprintf(
						/* translators: %s: Firebase project ID */
						__( 'Found a service account for the Firebase project %s.', 'palasthotel-push-notifications-for-firebase' ),
						is_object( $config ) && isset( $config->project_id ) ? (string) $config->project_id : '?'
					)
				);
			} else {
				echo '🚨 ' . esc_html__( 'The constant is defined, but it is neither the service account JSON nor the path to a readable JSON file with a private key.', 'palasthotel-push-notifications-for-firebase' );
			}
			echo '</p>';
			return;
		}

		$config = $this->getConfig();
		if($config == null){
			$config = "";
		} else {
			if(isset($config->private_key_id)) $config->private_key_id = "***";
			if(isset($config->private_key)) $config->private_key = "***";
			$config = wp_json_encode($config, JSON_PRETTY_PRINT);
		}
		?>
		<ol class="description">
			<li>Goto <a href="https://console.firebase.google.com">Firebase Console</a></li>
			<li>Choose your project</li>
			<li>Goto "Project Settings"</li>
			<li>Goto "Service Accounts"</li>
			<li>Generate new private key and download the json file</li>
			<li>Copy and past contents of json file here</li>
		</ol>
		<textarea
			style="width: 100%"
			rows="13"
			id="<?php echo esc_attr( Plugin::OPTION_CONFIG ); ?>"
			name="<?php echo esc_attr( Plugin::OPTION_CONFIG ); ?>"
		><?php echo esc_textarea( $config ); ?></textarea>
		<?php
		echo '<p class="description">';
		if($this->isConfigValid()){
			echo "✅ Found Google Services configuration.";
		} else {
			echo "🚨 There is no Google Services configuration.";
		}
		echo '</p><p class="description">';
		echo esc_html(
			sprintf(
				/* translators: %s: name of the PHP constant */
				__( 'To keep the private key out of the database, define the constant %s in wp-config.php instead, with the path to the JSON file (outside the web root) or the JSON itself.', 'palasthotel-push-notifications-for-firebase' ),
				Plugin::CONSTANT_SERVICE_ACCOUNT
			)
		);
		echo '</p>';
	}

	function getActivatedPostTypes(){
		return get_option(Plugin::OPTION_POST_TYPES, array("post"));
	}

	function render_post_types(){
		$activated = $this->getActivatedPostTypes();
		$post_types = get_post_types( array('public' => true, 'show_ui' => true), 'objects' );
		echo "<p>";
		foreach ( $post_types as $key => $post_type ) {
			$name = Plugin::OPTION_POST_TYPES."[]";
			echo "<label><input name='" . esc_attr( $name ) . "' type='checkbox' value='" . esc_attr( $key ) . "' " . checked( in_array( $key, (array) $activated, true ), true, false ) . " /> " . esc_html( $post_type->labels->name ) . "</label><br/>";
		}
		echo "</p>";
		echo "<p class='description'>Activate post types for firebase push notifications.</p>";
	}

	/**
	 * render the setting field
	 */
	public function render_webapp_config_field(){
		$config = $this->getWebappConfig();
		if($config == null){
			$config = "";
		} else {
			$config = json_encode($config, JSON_PRETTY_PRINT);
		}
		?>
		<ol class="description">
			<li>Goto <a href="https://console.firebase.google.com">Firebase Console</a></li>
			<li>Choose your project</li>
			<li>Goto "Project Settings"</li>
			<li>Scroll down to "My Apps"</li>
			<li>Select an existing or add a new "Web app"</li>
			<li>Choose "Configuration" for the Firebase SDK snippet.</li>
			<li>Copy and past it here.</li>
		</ol>
		<textarea
				style="width: 100%"
				rows="13"
				id="<?php echo esc_attr( Plugin::OPTION_WEBAPP_CONFIG ); ?>"
				name="<?php echo esc_attr( Plugin::OPTION_WEBAPP_CONFIG ); ?>"
		><?php echo esc_textarea( $config ); ?></textarea>
		<script>
			jQuery(function($){
				const variableDefRegex = /^( *(const|let|var) firebaseConfig = *{)/gm;
				const regex = /(\w+):( +|")/gm;
				const endsWithSemicolonRegex = /(;)$/gm;
				const subst = `"$1":`;
				$(<?php echo wp_json_encode( "#" . Plugin::OPTION_WEBAPP_CONFIG ); ?>).on("keyup", function(){
					let val = this.value;
					val = val.replace(variableDefRegex, "{", val);
					val = val.replace(endsWithSemicolonRegex, "", val);
					this.value = val.replace(regex, subst);
				});
			});
		</script>
		<?php
		echo '<p class="description">';
		if($this->isWebappConfigValid()){
			echo "✅ Found Webapp configuration.";
		} else {
			echo "🚨 There is no webapp configuration.";
		}
		echo '</p>'
		?>
		<p>After that you have to add two cloud function so users can subscribe to topics</p>
		<?php
	}

	function load_wp_media_files( $page ) {
		// change to the $page where you want to enqueue the script
		if( $page == 'options-writing.php' ) {
			// Enqueue WordPress media scripts
			wp_enqueue_media();
		}
	}

	function render_notification_icon_field(){
		?>
		<div
			style="border: 1px solid #a3a3a3; height: 192px; width: 192px; margin-bottom: 10px;"
		>
			<img
					id="firebase-notifications-icon-preview"
					src=""
			/>
		</div>
		<div>
		<button
				class="button button-small button-secondary"
				id="firebase-notifications-icon-add"
		>
			Add image
		</button>
		<button
				class="button button-small button-secondary"
				id="firebase-notifications-icon-remove"
			>
			Remove image
		</button>
		</div>
		<input
				name="<?php echo esc_attr( Plugin::OPTION_WEBAPP_NOTIFICATION_ICON ); ?>"
				value="<?php echo esc_attr( $this->getNotificationIconImageId() ); ?>"
				type="hidden"
		/>
		<p class="description">Square image with 192px x 192px.</p>
		<script>
			jQuery(function($) {
				const $imagePreview = $("#firebase-notifications-icon-preview");
				const $buttonAdd = $('#firebase-notifications-icon-add');
				const $buttonRemove = $('#firebase-notifications-icon-remove');
				const $input = $(<?php echo wp_json_encode( "[name=" . Plugin::OPTION_WEBAPP_NOTIFICATION_ICON . "]" ); ?>);

				$buttonRemove.click(function(e){
					e.preventDefault();
					updateState("","");
				});

				let image_frame = null;
				$buttonAdd.click(function(e) {
					e.preventDefault();

					if(image_frame){
						image_frame.open();
					}
					// Define image_frame as wp.media object
					image_frame = wp.media({
						title: 'Select Notification Icon',
						multiple : false,
						library : {
							type : 'image',
						}
					});

					image_frame.on('close',function() {
						const selection =  image_frame.state().get('selection');
						selection.each(function(attachment){ // should be a single value array
							updateState(attachment['id'], attachment.get('url'));
						});
					});

					image_frame.on('open',function() {
						const selection = image_frame.state().get('selection');
						const attachment = wp.media.attachment($input.val());
						selection.add( attachment ? [ attachment ] : [] );
					});

					image_frame.open();
				});

				function updateState(id, url){
					$input.val(id);

					if(typeof url === typeof "" && url !== ""){
						$imagePreview.attr("src", url);
						$imagePreview.show();
						$buttonRemove.show();
					} else {
						$buttonRemove.hide();
						$imagePreview.hide();
					}
				}

				// update application state to initial state
				updateState(
					<?php echo wp_json_encode( $this->getNotificationIconImageId() ); ?>,
					<?php echo wp_json_encode( (string) wp_get_attachment_image_url( (int) $this->getNotificationIconImageId(), 'full' ) ); ?>
				);

			});
		</script>
<?php
	}


}