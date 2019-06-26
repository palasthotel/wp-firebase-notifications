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
		add_action( Plugin::ACTION_SAVED_MESSAGE, array( $this, 'saved_message' ) );
	}

	/**
	 *  register meta box
	 */
	public function add_meta_box() {
		if(!$this->plugin->permissions->canSendMessages()){
			return;
		}
		add_meta_box(
			Plugin::DOMAIN . '-meta-box',
			__( 'Firebase Notifications', Plugin::DOMAIN ),
			array( $this, 'render' ),
			'post'
		);
	}

	/**
	 * @param \WP_Post $post
	 *
	 * @throws \Exception
	 */
	public function render( $post ) {

		// post needs to be published first
		if( "publish" != $post->post_status){
			printf("<p>%s</p>",__("Post needs to be published.", Plugin::DOMAIN));
			return;
		}

		wp_enqueue_style(
			Plugin::DOMAIN . "-meta-box",
			$this->plugin->url . "/css/meta-box.css",
			array(),
			filemtime($this->plugin->path."/css/meta-box.css")
		);
		$this->plugin->ajax->enqueueApiJs();
		$meta_box_script_handle = Plugin::DOMAIN . "-meta-box";
		wp_enqueue_script(
			$meta_box_script_handle,
			$this->plugin->url . "/js/meta-box.js",
			array( "jquery", $this->plugin->ajax->api_handle ),
			filemtime($this->plugin->path."/js/meta-box.js"),
			true
		);
		wp_localize_script(
			$meta_box_script_handle,
			"FirebaseNotifications_MetaBox",
			array(

				"restrictions" => apply_filters(
					Plugin::FILTER_META_BOX_RESTRICTIONS,
					array(
						"title" => array(
							"short" => 8,
							"long" => 40,
							"too_long" => 60,
						),
						"text" => array(
							"short" => 20,
							"long" => 70,
							"too_long" => 100,
						),
					)
				),

				"i18n" => array(

					"countable" => array(
						"text" => __("%d chars. ", Plugin::DOMAIN),
						"short" => __("Could be too short 🤔", Plugin::DOMAIN),
						"good" => __("Seems to be a good length 👍", Plugin::DOMAIN),
						"long" => __("Could be a little too long ⚠️", Plugin::DOMAIN),
						"too_long" => __("Will probably be too long 🚨️", Plugin::DOMAIN),
					),

					"empty_conditions" => __("You need to choose at least one topic", Plugin::DOMAIN),
					"limitation_conditions" => __("You can use max of 4 logical operations", Plugin::DOMAIN),
					"invalid" => __( "Invalid", Plugin::DOMAIN ),
					"valid" => __( "Valid", Plugin::DOMAIN ),

					"submit" => array(
						"now" => __("Send", Plugin::DOMAIN),
						"plan" => __("Plan", Plugin::DOMAIN),
					),

					"confirms" => array(
						"overwrite_conditions"	=> __("This will overwrite your current topics condition. Proceed?", Plugin::DOMAIN),
					),
					"errors" => array(
						"title" => __("Give me a message title, please.", Plugin::DOMAIN),
						"body" => __("Type some body content.", Plugin::DOMAIN),
						"conditions" => __("Please define your topic conditions.", Plugin::DOMAIN),
						"plattforms" => __("At least one plattform needs to be activated.", Plugin::DOMAIN),
						"schedule" => array(
							"invalid" =>  __("Please provide a valid schedule date that is at least one hour in the future.", Plugin::DOMAIN),
							"in_the_past" => __("Schedule date must be at least one hour in the future.", Plugin::DOMAIN),
						)
					),
				),
				"topic_ids" => $this->plugin->topics->getTopicIds(),
				"payload"   => array(
					"post_id"   => $post->ID,
					"permalink" => get_permalink( $post ),
				),
			)
		);
		do_action(Plugin::ACTION_ENQUEUE_META_BOX_ENQUEUE_SCRIPT,$meta_box_script_handle);
		?>
		<div class="fn__wrapper">
			<div class="fn__base-control--field">
				<label class="fn__base-control--label"
				       for="firebase-notifications__title"
				><?php _e("Title", Plugin::DOMAIN); ?></label>
				<input class="fn__base-control--input"
				       type="text"
				       id="firebase-notifications__title"
				       maxlength="190"
				       value="<?php the_title(); ?>"
				/>
			</div>
			<div class="fn__base-control--field">
				<label class="fn__base-control--label"
				       for="firebase-notifications__body"><?php _e("Body", Plugin::DOMAIN); ?></label>
				<textarea class="fn__base-control--input"
				          id="firebase-notifications__body"
				          rows="4"
				><?php echo $post->post_excerpt; ?></textarea>
			</div>
			<div class="fn__base-control--field">
				<label class="fn__base-control--label"><?php _e("To all devices of plattform", Plugin::DOMAIN); ?></label>
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
				<div class="fn__base-control--field">
					<label class="fn__base-control--label"
					       for="firebase-notifications__conditions">
						<?php _e("To all devices subscribed to topics condition", Plugin::DOMAIN);?>
						<span id="firebase-notifications_conditions--valid">...</span>
					</label>
					<?php
					$value = "";
					$readonly = "";
					if($tcount == 1){
						$value = $topics[0]->id;
						$readonly = "readonly";
					}
					?>
					<input class="fn__base-control--input"
					       type="text"
					       id="firebase-notifications__conditions"
					       <?php echo $readonly; ?>
					       value="<?php echo $value; ?>"
					/>
					<?php
					if($tcount > 1){
						?>
						<p class="description">
							<?php echo implode( ", ", array_map( function ( $item ) {
								return "<span class='firebase-notifications__topic--copy'>".$item->id."</span>";
							}, $topics ) ); ?>
						</p>
						<div class="firebase-notifications__examples">
							<div class="examples__header"><?php _e("Show more examples", Plugin::DOMAIN); ?></div>
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
		<div class="fn__base-control--field">
			<label class="fn__base-control--label"><?php _e("Schedule", Plugin::DOMAIN); ?></label>
			<p class="firebase--notifications__schedule">
				<label><input type="radio" name="firebase_schedule" checked value="now" /> <?php _e("Now", Plugin::DOMAIN); ?></label>
				<label><input type="radio" name="firebase_schedule" value="plan" /> <?php _e("Plan", Plugin::DOMAIN); ?></label>
				<label><input type="datetime-local" name="firebase_schedule_datetime" value=""/></label>

			</p>
		</div>
		<?php

		do_action(Plugin::ACTION_META_BOX_CUSTOM, $this);

		if ( count( $topics ) ) {

			echo "<p>";
			submit_button( __("Send", Plugin::DOMAIN), "primary", "firebase-notifications-submit", false );
			printf( "<span class='is-loading'>%s</span>", __("Sending message", Plugin::DOMAIN));
			printf("<span class='result-display-sent'> ✅ %s</span>", __("Message has been sent!", Plugin::DOMAIN));
			printf("<span class='result-display-scheduled'> ✅ %s</span>", __("Message has been scheduled!", Plugin::DOMAIN));
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
						<?php if($msg->publish != null && $msg->sent == null){
							$formatted = date_i18n(get_option('date_format')." ".get_option('time_format'), strtotime($msg->publish));
							echo "<div class='history-item__schedule'>⏱ $formatted</div>";
						} ?>
					</div>
					<div class="history-item__right">
						<span class="history-item__date"><?php
							$created = date_i18n(get_option('date_format')." ".get_option('time_format'), strtotime($msg->created));
							$sent = "";
							if($msg->sent){
								$sent = date_i18n(get_option('date_format')." ".get_option('time_format'), strtotime($msg->sent));
							}
							echo "💾 $created";
							if(!empty($sent)){
								echo "<br>✉️ $sent";
							}
							?><br><?php echo implode(", ", $msg->plattforms) ?></span>
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
	public function saved_message( $message ) {
		if ( isset( $message->payload["post_id"] ) ) {
			$post_id = intval( $message->payload["post_id"] );
			if ( $post_id > 0 ) {
				$this->plugin->database->addPostMessage( $message->id, $post_id );
			}
		}
	}

}