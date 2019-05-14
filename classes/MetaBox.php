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
		add_action( Plugin::ACTION_MESSAGE_CREATED, array( $this, 'message_created' ) );
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
				><?php _e("Title", Plugin::DOMAIN); ?></label>
				<input class="components-text-control__input"
				       type="text"
				       id="firebase-notifications__title"
				       value="<?php the_title(); ?>"
				/>
			</div>
			<div class="components-base-control__field">
				<label class="components-base-control__label"
				       for="firebase-notifications__body"><?php _e("Body", Plugin::DOMAIN); ?></label>
				<textarea class="components-textarea-control__input"
				          id="firebase-notifications__body"
				          rows="4"
				><?php echo $post->post_excerpt; ?></textarea>
			</div>
			<div class="components-base-control__field">
				<label class="components-base-control__label"><?php _e("Plattforms", Plugin::DOMAIN); ?></label>
				<p class="firebase--notifications__plafforms">
					<label><input type="checkbox" name="plattform[]" checked="checked" value="android" /> Android</label>
					<label><input type="checkbox" name="plattform[]" checked="checked" value="ios" /> iOS</label>
					<label><input type="checkbox" name="plattform[]" checked="checked" value="web" /> Web</label>
				</p>
			</div>

			<?php
			$topics = $this->plugin->topics->getTopics();
			$tcount = count($topics);
			if ( $tcount > 0 ) {

				$examples = array();
				$descriptions = array();

				if( $tcount > 1 ) {
					$topic0 = $topics[0]->id;
					$topic1 = $topics[1]->id;

					$examples[] =  $topics[0]->id." AND ".$topics[1]->id;
					$descriptions[] = sprintf(__(
							"Send a message to all devices that are subscribed to '%s' and are also subscribed to the topic '%s'.",
							Plugin::DOMAIN
					), $topic0, $topic1);

					$examples[] =  $topics[0]->id." OR ".$topics[1]->id;
					$descriptions[] = sprintf(__(
							"Send a message to all devices that are subscribed to '%s' or to '%s' or to both topics.",
							Plugin::DOMAIN
					), $topic0, $topic1);

					if($tcount > 2){
						$topic2 = $topics[2]->id;
						$examples[] = "$topic0 AND ( $topic1 OR $topic2 )";
						$descriptions[] = sprintf(__(
								"Send a message to all devices that are subscribed to '%s' and are also subscribed to '%s' or to '%s' or to both topics.",
								Plugin::DOMAIN
						), $topic0, $topic1, $topic2);
						$examples[] = "$topic0 OR ( $topic1 AND $topic2 )";
						$descriptions[] = sprintf(__(
							"Send a message to all devices that are subscribed to '%s' or are subscribed to both topics '%s' and '%s'.",
							Plugin::DOMAIN
						), $topic0, $topic1, $topic2);
					}
				}

				?>
				<div class="components-base-control__field">
					<label class="components-base-control__label"
					       for="firebase-notifications__conditions">
						Topics <span
								id="firebase-notifications_conditions--valid"></span>
					</label>
					<?php
					$value = "";
					$readonly = "";
					if($tcount == 1){
						$value = $topics[0]->id;
						$readonly = "readonly";
					}
					?>
					<input class="components-text-control__input"
					       type="text"
					       id="firebase-notifications__conditions"
					       <?php echo $readonly; ?>
					       value="<?php echo $value; ?>"
					/>
					<?php
					if($tcount > 1){
						?>
						<p class="description">
							Topics: <?php echo implode( ", ", array_map( function ( $item ) {
								return $item->id;
							}, $topics ) ); ?>
						</p>
						<div class="firebase-notifications__examples">
							<div class="examples__header"><?php _e("Show examples", Plugin::DOMAIN); ?></div>
							<div class="examples__content">
								<?php
									foreach ($examples as $i => $example){
										echo "<p>";
										echo __("Example", Plugin::DOMAIN).($i+1).": ";
										echo "<span class='examples__code--wrapper'>";
											echo "<span class='examples__code'>$example</span>";
											echo "<span class='examples__copy'>"._x("Use", "post meta box condition examples", Plugin::DOMAIN)."</span>";
										echo "</span>";
										echo "<br/>";
										$description = $descriptions[$i];
										echo "<span class='description'>$description</span>";
										echo "</p>";
									}
								?>
							</div>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>

		</div>
		<?php

		if ( count( $topics ) ) {

			echo "<p>";
			submit_button( __("Send", Plugin::DOMAIN), "primary", "firebase-notifications-submit", false );
			printf( "<span class='is-loading'>%s</span>", __("Sending message", Plugin::DOMAIN));
			printf("<span class='result-display'> ✅ %s</span>", __("Message has been sent!", Plugin::DOMAIN));
			printf( "<span class='error-display'> 🚨 %s</span>", _x("Error.", "Send message post meta box response", Plugin::DOMAIN));
			echo "</p>";

		} else {
			echo "<p> 🚨" . __( 'Configuration missing. There are no topics defined', Plugin::DOMAIN ) . "</p>";
		}

		// history
		$messages = $this->plugin->database->getPostMessages( $post->ID );
		$count    = count( $messages );
		if ( $count > 0 ) {
			echo "<h3>" . __( "History", "post meta box", Plugin::DOMAIN ) . "</h3>";
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
		if ( $count > 1 ) {
			$url = $this->plugin->toolsPage->getUrl( $post->ID );
			echo "<p><a href='$url'>";
			printf(__("Show complete history of %d items."), $count);
			echo "</a></p>";
		}

	}

	/**
	 * @param Message $message
	 */
	public function message_created( $message ) {
		if ( isset( $message->payload["post_id"] ) ) {
			$post_id = intval( $message->payload["post_id"] );
			if ( $post_id > 0 ) {
				$this->plugin->database->addPostMessage( $message->id, $post_id );
			}
		}
	}

}