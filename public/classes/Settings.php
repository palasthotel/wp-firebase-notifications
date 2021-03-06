<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-08
 * Time: 19:34
 */

namespace Palasthotel\FirebaseNotifications;


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
			'<a href="'.admin_url('options-writing.php#'.Plugin::DOMAIN).'">Settings</a>'
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
				echo "<span id='".Plugin::DOMAIN."'></span>";
			}, // Callback for your function
			'writing' // Location (Settings > Permalinks)
		);

		add_settings_field(
			Plugin::OPTION_CONFIG,
			__('Google Services', Plugin::DOMAIN),
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
			__('Post types', Plugin::DOMAIN),
			array($this, 'render_post_types'),
			'writing',
			'firebase-notifications-settings'
		);
		register_setting(
			'writing',
			Plugin::OPTION_POST_TYPES
		);


		add_settings_field(
			Plugin::OPTION_WEBAPP_CONFIG,
			__('Webapp', Plugin::DOMAIN),
			array($this, 'render_webapp_config_field'),
			'writing',
			'firebase-notifications-settings'
		);
		register_setting(
			'writing',
			Plugin::OPTION_WEBAPP_CONFIG
		);

		add_settings_field(
			Plugin::OPTION_WEBAPP_NOTIFICATION_ICON,
			__('Notification Icon', Plugin::DOMAIN),
			array($this, 'render_notification_icon_field'),
			'writing',
			'firebase-notifications-settings'
		);
		register_setting(
			'writing',
			Plugin::OPTION_WEBAPP_NOTIFICATION_ICON
		);

	}

	/**
	 * @param $option
	 *
	 * @return mixed
	 */
	public function sanitize_config($option){
		if($option != ""){
			$json = json_decode($option);
			$old = json_decode(get_option(Plugin::OPTION_CONFIG));
			if($json->private_key_id == "***") $json->private_key_id = $old->private_key_id;
			if($json->private_key == "***") $json->private_key = $old->private_key;
			$option = json_encode($json);
		}
		return $option;
	}

	/**
	 * @param bool $assoc
	 *
	 * @return object|array|null
	 */
	public function getConfig($assoc = false){
		$config = get_option(Plugin::OPTION_CONFIG, "");
		return json_decode($config, $assoc);
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
		return get_option(Plugin::OPTION_WEBAPP_NOTIFICATION_ICON, "");
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
		$config = $this->getConfig();
		if($config == null){
			$config = "";
		} else {
			if(isset($config->private_key_id)) $config->private_key_id = "***";
			if(isset($config->private_key)) $config->private_key = "***";
			$config = json_encode($config, JSON_PRETTY_PRINT);
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
			id="<?php echo Plugin::OPTION_CONFIG; ?>"
			name="<?php echo Plugin::OPTION_CONFIG; ?>"
		><?php echo $config ?></textarea>
		<?php
		echo '<p class="description">';
		if($this->isConfigValid()){
			echo "✅ Found Google Services configuration.";
		} else {
			echo "🚨 There is no Google Services configuration.";
		}
		echo '</p>'
		?>

		<?php
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
			$checked = (in_array($key, $activated))? "checked='checked'":"";
			echo "<label><input name='$name' type='checkbox' value='$key' $checked /> ".$post_type->labels->name."</label><br/>";
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
				id="<?php echo Plugin::OPTION_WEBAPP_CONFIG; ?>"
				name="<?php echo Plugin::OPTION_WEBAPP_CONFIG; ?>"
		><?php echo $config ?></textarea>
		<script>
			jQuery(function($){
				const variableDefRegex = /^( *(const|let|var) firebaseConfig = *{)/gm;
				const regex = /(\w+):( +|")/gm;
				const endsWithSemicolonRegex = /(;)$/gm;
				const subst = `"$1":`;
				$("#<?php echo Plugin::OPTION_WEBAPP_CONFIG; ?>").on("keyup", function(){
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
				name="<?php echo Plugin::OPTION_WEBAPP_NOTIFICATION_ICON; ?>"
				value="<?php echo $this->getNotificationIconImageId(); ?>"
				type="hidden"
		/>
		<p class="description">Square image with 192px x 192px.</p>
		<script>
			jQuery(function($) {
				const $imagePreview = $("#firebase-notifications-icon-preview");
				const $buttonAdd = $('#firebase-notifications-icon-add');
				const $buttonRemove = $('#firebase-notifications-icon-remove');
				const $input = $("[name=<?php echo Plugin::OPTION_WEBAPP_NOTIFICATION_ICON; ?>]");

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
					"<?php echo $this->getNotificationIconImageId() ?>",
					"<?php echo wp_get_attachment_image_url($this->getNotificationIconImageId(), 'full'); ?>"
				);

			});
		</script>
<?php
	}


}