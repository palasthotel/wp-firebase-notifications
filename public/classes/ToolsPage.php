<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-17
 * Time: 18:36
 */

namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Palasthotel\FirebaseNotifications\Component\Component;

/**
 * Class ToolsPage
 *
 * @property Plugin plugin
 * @package Palasthotel\FirebaseNotifications
 */
class ToolsPage extends Component {

	public function onCreate() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * @param null|int $post_id
	 *
	 * @return string
	 */
	public function getUrl($post_id = null){
		$post = ($post_id)? "&post_id=$post_id": "";
		return admin_url("/tools.php?page=firebase-notifications-tools$post");
	}

	// The tools page only reads the list filters "paged" and "post_id" from the
	// query string, it changes nothing - there is no form submit to verify a nonce for.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended

	public function getPage(){
		$paged = isset( $_GET["paged"] ) ? absint( $_GET["paged"] ) : 1;
		return ($paged<1)? 1: $paged;
	}
	/**
	 * @return int
	 */
	public function getPaged(){
		return $this->getPage()-1;
	}

	public function getPostId(){
		$post_id = isset( $_GET["post_id"] ) ? absint( $_GET["post_id"] ) : 0;
		return $post_id > 0 ? $post_id : null;
	}

	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	/**
	 * add menu item
	 */
	public function admin_menu() {
		add_submenu_page(
			'tools.php',
			__( 'Tools ‹ Firebase Notifications', 'palasthotel-push-notifications-for-firebase' ),
			__( 'Firebase Notifications', 'palasthotel-push-notifications-for-firebase' ),
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
			<h1><?php esc_html_e( 'Firebase Notifications', 'palasthotel-push-notifications-for-firebase' ); ?></h1>
			<?php
			$this->renderForm();
			$this->renderHistory();
			?>
		</div>
		<?php
	}

	public function renderForm(){
		$post_id = $this->getPostId();
		?>
		<form method="get" action="<?php echo esc_url( admin_url( "tools.php" ) ); ?>">
			<input type="hidden" name="page" value="firebase-notifications-tools" />
			<label><?php esc_html_e( "Post ID:", 'palasthotel-push-notifications-for-firebase' ); ?> <input name="post_id" value="<?php echo esc_attr( $post_id ? (string) $post_id : "" ); ?>" /></label>
			<label><?php esc_html_e( "Page:", 'palasthotel-push-notifications-for-firebase' ); ?> <input type="number" name="paged" min="1" value="<?php echo esc_attr( (string) $this->getPage() ); ?>" style="width: 60px;"/></label>
			<button type="submit"><?php esc_html_e( "Find", 'palasthotel-push-notifications-for-firebase' ); ?></button>
		</form>
		<?php
	}

	public function renderHistory(){
	    $this->plugin->assets->enqueueToolsPageScript();
	    $this->plugin->assets->enqueueToolsPageStyle();
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		$timezone = wp_timezone();
		$format = "$date_format $time_format";
		$post_id = $this->getPostId();
		$paged = $this->getPaged();
		$count = 20;
		?>
		<ul class="firebase-notifications__list">
			<?php

			if($post_id){
				$notifications = $this->plugin->database->getByPostId($post_id, $paged, $count);
			} else {
				$notifications = $this->plugin->database->getAll($paged, $count);
			}
			foreach ($notifications as $item){
				$created = new \DateTime($item->created);
				$created->setTimezone($timezone);
				$sent = (!empty($item->sent))? new \DateTime($item->sent): null;
				if($sent) $sent->setTimezone($timezone);
				$readableCreated = date_i18n($format, $created->getTimestamp()+$created->getOffset());
				$readableSent = (empty($sent))?
					"🚨 "._x("Not sent", "Tools page", 'palasthotel-push-notifications-for-firebase')
					:
					" ✅ ".date_i18n($format, $sent->getTimestamp()+$sent->getOffset());
				echo "<li class='firebase-notifications__item card'>";
				echo "<div class='firebase-notifications__item--title'>" . esc_html( $item->title ) . "</div>";
				echo "<div class='firebase-notifications__item--body'>" . esc_html( $item->body ) . "</div>";
				echo "<div class='firebase-notifications__item--footer'>";

					echo "<div class='firebase-notifications__item--created'>" . esc_html__( "Created:", 'palasthotel-push-notifications-for-firebase' ) . " " . esc_html( $readableCreated ) . "</div>";
					echo "<div class='firebase-notifications__item--plattform'>" . esc_html( implode(", ",$item->plattforms) ) . "</div>";
					echo "<div class='firebase-notifications__item--conditions'>";
					echo "<span class='firebase-notifications__item--conditions-wrapper'>" . esc_html( $item->conditionForDisplay() ) . "</span>";
					echo "</div>";

					echo "<div class='firebase-notifications__item--sent'>" . esc_html__( "Sent:", 'palasthotel-push-notifications-for-firebase' ) . " " . esc_html( $readableSent ) . "</div>";



				echo "</div>";
				echo "<div class='firebase-notifications__item--communication'>";
					echo "<label class='firebase-notifications__item--payload-label'>" . esc_html__( "Payload", 'palasthotel-push-notifications-for-firebase' ) . "</label>";
					echo "<ul class='firebase-notifications__item--payload'>";
					foreach ($item->payload as $key => $value){
						echo "<li><strong>" . esc_html( $key ) . ":</strong> " . esc_html( is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) ) . "</li>";
					}
					echo "</ul>";
					if($item->sent != null){
						echo "<label class='firebase-notifications__item--result-label'>";
						esc_html_e("Answer from Firebase Cloud Messaging", 'palasthotel-push-notifications-for-firebase');
						echo "</label>";
						echo "<ul class='firebase-notifications__item--result'>";
						foreach ((array) $item->result as $key => $value){
							echo "<li><strong>" . esc_html( $key ) . ":</strong> " . esc_html( is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) ) . "</li>";
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