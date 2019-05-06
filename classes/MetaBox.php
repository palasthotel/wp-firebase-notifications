<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-03-29
 * Time: 10:38
 */

namespace Palasthotel\FirebaseNotifications;


/**
 * @property Plugin plugin
 */
class MetaBox {

	/**
	 * MetaBox constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		add_action( 'add_meta_boxes_post', array( $this, 'add_meta_box' ) );
		add_action(Plugin::ACTION_MESSAGE_ADD, array($this, 'message_add') );
	}

	/**
	 *  register meta box
	 */
	public function add_meta_box() {
		add_meta_box(
			Plugin::DOMAIN . '-meta-box',
			__( 'Firebase Notifications', Plugin::DOMAIN ),
			array( $this, 'render' ),
			'post'
		);
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render( $post ) {
		wp_enqueue_style(
				Plugin::DOMAIN."-meta-box",
			$this->plugin->url."/css/meta-box.css"
		);
		$this->plugin->ajax->enqueueApiJs();
		wp_enqueue_script(
			Plugin::DOMAIN . "-meta-box",
			$this->plugin->url . "/js/meta-box.js",
			array( "jquery", $this->plugin->ajax->api_handle ),
			1,
			true
		);
		wp_localize_script(
			Plugin::DOMAIN . "-meta-box",
			"FirebaseNotifications_MetaBox",
			array(
				"payload" => array(
					"post_id"   => $post->ID,
					"permalink" => get_permalink( $post ),
				),
			)
		);
		?>
		<div class="components-base-control">
			<div class="components-base-control__field">
				<label class="components-base-control__label"
				       for="firebase-notifications__title"
				>Title</label>
				<input class="components-text-control__input"
				       type="text"
				       id="firebase-notifications__title"
				       value="<?php the_title(); ?>"
				/>
			</div>
			<div class="components-base-control__field">
				<label class="components-base-control__label"
				       for="firebase-notifications__body">
					Body
				</label>
				<textarea class="components-textarea-control__input"
				          id="firebase-notifications__body"
				          rows="4"
				><?php echo $post->post_excerpt; ?></textarea>
			</div>
		</div>
		<?php
		$topics = $this->plugin->topics->getTopics();
		if ( count( $topics ) ) {
			echo "<div class='components-base-control__field'>";
				echo '<label class="components-base-control__label" for="firebase-notifications__topic">Topic: </label>';
				echo '<select id="firebase-notifications__topic">';
				foreach ( $topics as $topic ) {
					echo '<option value="' . $topic->id . '">' . $topic->name . '</option>';
				}
				echo '</select>';
			echo '</div>';

			echo "<p>";
				submit_button( "Send", "primary", "firebase-notifications-submit", false );
				echo "<span class='is-loading'> Sending message</span>";
				echo "<span class='result-display'> ✅ Message has been sent!</span>";
				echo "<span class='error-display'> 🚨 Error.</span>";
			echo "</p>";

		} else {
			echo "<p>" . __( 'There are no topics defined', Plugin::DOMAIN ) . "</p>";
		}


		// history
		$messages = $this->plugin->database->getPostMessages($post->ID);
		$count = count($messages);
		if($count > 0 ){
			echo "<h3>".__("History", Plugin::DOMAIN)."</h3>";
			echo "<ul class='firebase-notifications__history'>";
			foreach ($messages as $index => $msg){
				if($index >= 3) break;
				?>
				<li class="firebase-notifications__history--item">
					<strong class="history-item__title"><?php echo $msg->title; ?></strong>
					<span class="history-item__date"><?php echo  $msg->created; ?></span>
				</li>
				<?php
			}
			echo "</ul>";
		}
		if($count > 3){
			$url = $this->plugin->toolsPage->getUrl($post->ID);
			echo "<p><a href='$url'>Show complete history of $count items.</a></p>";
		}

	}

	/**
	 * @param Message $message
	 */
	public function message_add($message){
		if(isset($message->payload["post_id"])){
			$post_id = intval($message->payload["post_id"]);
			if( $post_id > 0 ){
				$this->plugin->database->addPostMessage($message->id, $post_id);
			}
		}
	}

}