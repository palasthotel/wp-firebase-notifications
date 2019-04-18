<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-17
 * Time: 18:36
 */

namespace Palasthotel\FirebaseNotifications;

/**
 * Class ToolsPage
 *
 * @property Plugin plugin
 * @package Palasthotel\FirebaseNotifications
 */
class ToolsPage {

	/**
	 * ToolsPage constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * add menu item
	 */
	public function admin_menu() {
		add_submenu_page(
			'tools.php',
			__( 'Tools ‹ Firebase Notifications', Plugin::DOMAIN ),
			__( 'Firebase Notifications', Plugin::DOMAIN ),
			'publish_posts',
			'firebase-notifications-tools',
			array( $this, 'render' )
		);
	}

	/**
	 * render the page
	 */
	public function render() {
		?>
		<div class="wrap firebase-notifications__tools-page">
			<h1><?php _e( 'Firebase Notifications', Plugin::DOMAIN ); ?></h1>
			<?php
			$this->renderHistory();
			?>
		</div>
		<?php
	}

	public function renderHistory(){
		$this->plugin->ajax->enqueueApiJs();
		wp_enqueue_script(
			"firebase-notifications-tools-page-js",
			$this->plugin->url."/js/tools-page.js",
			array("jquery", $this->plugin->ajax->api_handle),
			filemtime($this->plugin->path."/js/tools-page.js")
		);
		wp_enqueue_style(
			"firebase-notifications-tools-page-css",
			$this->plugin->url."/css/tools-page.css",
			null,
			filemtime($this->plugin->path."/css/tools-page.css")
		);
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		$format = "$date_format $time_format";
		?>
		<ul class="firebase-notifications__list">
			<?php
			$notifications = $this->plugin->database->getAll(0, 10);
			foreach ($notifications as $item){
				$readableCreated = date_i18n($format, strtotime($item->sent));
				$readableSent = (empty($item->sent))?
					__(" 🚨 Not sent", Plugin::DOMAIN)
					:
					" ✅ ".date_i18n($format, strtotime($item->sent));
				echo "<li class='firebase-notifications__item card'>";
				echo "<div class='firebase-notifications__item--title'>$item->title</div>";
				echo "<div class='firebase-notifications__item--body'>$item->body</div>";
				echo "<div class='firebase-notifications__item--footer'>";

					echo "<div class='firebase-notifications__item--sent'>Sent: $readableSent</div>";
					echo "<div class='firebase-notifications__item--topic'>Topic: ";
					echo "<span class='firebase-notifications__item--topic-wrapper'>$item->topic</span>";
					echo "</div>";
					echo "<div class='firebase-notifications__item--created'>Created: $readableCreated</div>";

				echo "</div>";
				echo "<div class='firebase-notifications__item--communication'>";
					echo "<label class='firebase-notifications__item--payload-label'>Payload</label>";
					echo "<ul class='firebase-notifications__item--payload'>";
					foreach ($item->payload as $key => $value){
						echo "<li><strong>$key:</strong> $value</li>";
					}
					echo "</ul>";
					if($item->sent != null){
						echo "<label class='firebase-notifications__item--result-label'>Answer from Firebase Cloud Messaging</label>";
						echo "<ul class='firebase-notifications__item--result'>";
						foreach ($item->result as $key => $value){
							echo "<li><strong>$key:</strong> $value</li>";
						}
						echo "</ul>";
					}
				echo "</div>";
				echo "</li>";
			}
			?>
		</ul>
		<?php
	}
}