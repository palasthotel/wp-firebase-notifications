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
		add_action( Plugin::ACTION_MESSAGE_ADD, array( $this, 'message_add' ) );
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
			Plugin::DOMAIN . "-meta-box",
			$this->plugin->url . "/css/meta-box.css"
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
				"topic_ids" => $this->plugin->topics->getTopicIds(),
				"payload"   => array(
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
			<div class="components-base-control__field">
				<label class="components-base-control__label">
					Plattforms
				</label>
				<p>
					<label><input type="checkbox" name="plattform[]" checked="checked" value="android" /> Android</label>
					<label><input type="checkbox" name="plattform[]" checked="checked" value="ios" /> iOS</label>
					<label><input type="checkbox" name="plattform[]" checked="checked" value="web" /> Web</label>
				</p>
			</div>

			<?php
			$topics = $this->plugin->topics->getTopics();
			if ( count( $topics ) ) {

				$examples = array();
				if(count($topics) > 2){
					$examples[] = $topics[2]->id." AND ( ".$topics[0]->id." OR ".$topics[1]->id." )";
				} else {
					$examples[] =  $topics[0]->id." AND ".$topics[1]->id;
					$examples[] =  $topics[0]->id." OR ".$topics[1]->id;
				}

				?>
				<div class="components-base-control__field">
					<label class="components-base-control__label"
					       for="firebase-notifications__conditions">
						Topics <span
								id="firebase-notifications_conditions--valid"></span>
					</label>
					<input class="components-text-control__input"
					       type="text"
					       id="firebase-notifications__conditions"
					       value=""
					/>
					<p class="description">
						Topics: <?php echo implode( ", ", array_map( function ( $item ) {
							return $item->id;
						}, $topics ) ); ?><br/>
						Examples: "<?php echo implode('" , "', $examples); ?>"
					</p>
				</div>
				<?php
			}
			?>

		</div>
		<?php

		if ( count( $topics ) ) {

			echo "<p>";
			submit_button( "Send", "primary", "firebase-notifications-submit", false );
			echo "<span class='is-loading'> Sending message</span>";
			echo "<span class='result-display'> ✅ Message has been sent!</span>";
			echo "<span class='error-display'> 🚨 Error.</span>";
			echo "</p>";

		} else {
			echo "<p> 🚨" . __( 'Configuration missing. There are no topics defined', Plugin::DOMAIN ) . "</p>";
		}


		// history
		$messages = $this->plugin->database->getPostMessages( $post->ID );
		$count    = count( $messages );
		if ( $count > 0 ) {
			echo "<h3>" . __( "History", Plugin::DOMAIN ) . "</h3>";
			echo "<ul class='firebase-notifications__history'>";
			foreach ( $messages as $index => $msg ) {
				if ( $index >= 3 ) {
					break;
				}
				?>
				<li class="firebase-notifications__history--item">
					<div class="history-item__left">
						<div class="history-item__title"><?php echo $msg->title; ?></div>
						<div class="history-item__conditions"><span><?php echo $msg->conditionForDisplay(); ?></span></div>
					</div>
					<div class="history-item__right">
						<span class="history-item__date"><?php echo $msg->created; ?><br><?php echo implode(", ", $msg->plattforms) ?></span>
					</div>

				</li>
				<?php
			}
			echo "</ul>";
		}
		if ( $count > 3 ) {
			$url = $this->plugin->toolsPage->getUrl( $post->ID );
			echo "<p><a href='$url'>Show complete history of $count items.</a></p>";
		}

	}

	/**
	 * @param Message $message
	 */
	public function message_add( $message ) {
		if ( isset( $message->payload["post_id"] ) ) {
			$post_id = intval( $message->payload["post_id"] );
			if ( $post_id > 0 ) {
				$this->plugin->database->addPostMessage( $message->id, $post_id );
			}
		}
	}

}