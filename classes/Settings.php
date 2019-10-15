<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-08
 * Time: 19:34
 */

namespace Palasthotel\FirebaseNotifications;


/**
 * @property Plugin plugin
 */
class Settings {

	/**
	 * Settings constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		add_action('admin_init', array($this,'custom_settings'));
		add_filter('plugin_action_links_' . $plugin->basename, array($this, 'add_action_links'));
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
			array($this, 'render_url_field'),
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
	 * render the setting field
	 */
	public function render_url_field(){
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
		foreach ( $post_types as $key => $post_type ) {
			$name = Plugin::OPTION_POST_TYPES."[]";
			$checked = (in_array($key, $activated))? "checked='checked'":"";
			echo "<input name='$name' type='checkbox' value='$key' $checked/> ".$post_type->labels->name."<br/>";
		}
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
}