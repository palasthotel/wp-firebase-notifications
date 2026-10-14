<?php

namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



class MetaBox {

	/**
	 * @var bool
	 */
	private $wasPublished = false;

	const NONCE_ACTION = "firebase_notifications_meta_box";
	const NONCE_NAME = "firebase_notifications_meta_box_nonce";
    
    public Plugin $plugin;
    
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( "save_post", [$this, 'save_post']);
		add_action( "transition_post_status", [$this, 'transition_post_status'], 10 , 2);
		add_action( Plugin::ACTION_SAVED_MESSAGE, array( $this, 'saved_message' ) );
	}

	/**
	 *  register meta box
	 */
	public function add_meta_box() {
		if ( ! $this->plugin->permissions->canSendMessages() ) {
			return;
		}
		add_meta_box(
			Plugin::DOMAIN . '-meta-box',
			__( 'Firebase Notifications', 'palasthotel-push-notifications-for-firebase' ),
			array( $this, 'render' ),
			$this->plugin->settings->getActivatedPostTypes()
		);
	}

	/**
	 * @param \WP_Post $post
	 *
	 * @throws \Exception
	 */
	public function render( $post ) {

		// post needs to be published first
		if ( "auto-draft" === $post->post_status ) {
			printf( "<p>%s</p>", esc_html__( "Post needs to be saved.", 'palasthotel-push-notifications-for-firebase' ) );

			return;
		}

		$this->plugin->assets->enqueueMetaBoxStyle();
		$this->plugin->assets->enqueueMetaBoxScript( array(
			"restrictions" => apply_filters(
				Plugin::FILTER_META_BOX_RESTRICTIONS, // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant
				array(
					"title" => array(
						"short"    => 8,
						"long"     => 40,
						"too_long" => 60,
					),
					"text"  => array(
						"short"    => 20,
						"long"     => 70,
						"too_long" => 100,
					),
				)
			),

			"i18n"      => array(

				"countable" => array(
					/* translators: %d: number of characters */
					"text"     => __( "%d chars. ", 'palasthotel-push-notifications-for-firebase' ),
					"short"    => __( "Could be too short 🤔", 'palasthotel-push-notifications-for-firebase' ),
					"good"     => __( "Seems to be a good length 👍", 'palasthotel-push-notifications-for-firebase' ),
					"long"     => __( "Could be a little too long ⚠️", 'palasthotel-push-notifications-for-firebase' ),
					"too_long" => __( "Will probably be too long 🚨️", 'palasthotel-push-notifications-for-firebase' ),
				),

				"empty_conditions"      => __( "You need to choose at least one topic", 'palasthotel-push-notifications-for-firebase' ),
				"limitation_conditions" => __( "You can use max of 4 logical operations", 'palasthotel-push-notifications-for-firebase' ),
				"invalid"               => __( "Invalid", 'palasthotel-push-notifications-for-firebase' ),
				"valid"                 => __( "Valid", 'palasthotel-push-notifications-for-firebase' ),

				"submit" => array(
					"now"  => __( "Send", 'palasthotel-push-notifications-for-firebase' ),
					"plan" => __( "Plan", 'palasthotel-push-notifications-for-firebase' ),
				),

				"confirms" => array(
					"overwrite_conditions" => __( "This will overwrite your current topics condition. Proceed?", 'palasthotel-push-notifications-for-firebase' ),
				),
				"errors"   => array(
					"title"      => __( "Give me a message title, please.", 'palasthotel-push-notifications-for-firebase' ),
					"body"       => __( "Type some body content.", 'palasthotel-push-notifications-for-firebase' ),
					"conditions" => __( "Please define your topic conditions.", 'palasthotel-push-notifications-for-firebase' ),
					"platforms" => __( "At least one platform needs to be activated.", 'palasthotel-push-notifications-for-firebase' ),
					"schedule"   => array(
						"invalid"     => __( "Please provide a valid schedule date that is at least a few minutes in the future.", 'palasthotel-push-notifications-for-firebase' ),
						"in_the_past" => __( "Schedule date must be at least a few minutes in the future.", 'palasthotel-push-notifications-for-firebase' ),
					)
				),
			),
			"topic_ids" => $this->plugin->topics->getTopicIds(),
			"payload"   => apply_filters(Plugin::FILTER_MESSAGE_PAYLOAD, array( // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant
				"post_id"   => $post->ID,
				"permalink" => get_permalink( $post ),
			)),
		) );
		do_action( Plugin::ACTION_ENQUEUE_META_BOX_ENQUEUE_SCRIPT, Plugin::HANDLE_META_BOX_SCRIPT ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant
		$draftTitle = get_post_meta($post->ID, Plugin::POST_META_DRAFT_TITLE, true);
		$draftBody = get_post_meta($post->ID, Plugin::POST_META_DRAFT_BODY, true);
		$draftPlatforms = get_post_meta($post->ID, Plugin::POST_META_DRAFT_PLATFORMS, true);
		$draftTopics = get_post_meta($post->ID, Plugin::POST_META_DRAFT_TOPICS, true);
		$draftSchedule = get_post_meta($post->ID, Plugin::POST_META_DRAFT_SCHEDULE, true);

		if(!is_array($draftPlatforms)){
		    $draftPlatforms = ["android", "ios", "web"];
        }
		?>
        <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
        <div class="fn__wrapper">
            <div class="fn__base-control--field">
                <label class="fn__base-control--label"
                       for="firebase-notifications__title"
                ><?php esc_html_e( "Title", 'palasthotel-push-notifications-for-firebase' ); ?></label>
                <input class="fn__base-control--input"
                       type="text"
                       id="firebase-notifications__title"
                       maxlength="190"
                       value="<?php echo esc_attr( !empty($draftTitle) ? $draftTitle : get_the_title() ); ?>"
                       name="<?php echo esc_attr( Plugin::POST_META_DRAFT_TITLE ); ?>"
                />
            </div>
            <div class="fn__base-control--field">
                <label class="fn__base-control--label"
                       for="firebase-notifications__body"><?php esc_html_e( "Body", 'palasthotel-push-notifications-for-firebase' ); ?></label>
                <textarea class="fn__base-control--input"
                          id="firebase-notifications__body"
                          rows="4"
                          name="<?php echo esc_attr( Plugin::POST_META_DRAFT_BODY ); ?>"
                ><?php echo esc_textarea( !empty($draftBody) ? $draftBody : $post->post_excerpt ); ?></textarea>
            </div>
            <div class="fn__base-control--field">
                <label class="fn__base-control--label"><?php esc_html_e( "To devices of platform", 'palasthotel-push-notifications-for-firebase' ); ?></label>
                <p class="firebase--notifications__plafforms">
                    <?php
                    $platforms = [
                        [
                            "label" => "Android",
                            "id" => "android",
                        ],
	                    [
		                    "label" => "iOS",
		                    "id" => "ios",
	                    ],
	                    [
		                    "label" => "Web",
		                    "id" => "web",
	                    ]
                    ];
                    foreach ($platforms as $platform){
                        $label = $platform["label"];
                        $id = $platform["id"];
                        $name = Plugin::POST_META_DRAFT_PLATFORMS."[]";
                        echo "<label>";
                        echo "<input type='checkbox' name='" . esc_attr( $name ) . "' " . checked( in_array( $id, $draftPlatforms, true ), true, false ) . " value='" . esc_attr( $id ) . "' data-platform />";
                        echo " " . esc_html( $label ) . "</label>";
                    }
                    ?>
                </p>
            </div>

			<?php
			$topics = $this->plugin->topics->getTopics();
			$tcount = count( $topics );
			if ( $tcount > 0 ) {

				$examples     = array();
				$descriptions = array();

				if ( $tcount > 1 ) {
					$topic0 = $topics[0]->id;
					$topic1 = $topics[1]->id;

					$examples[]     = $topics[0]->id . " AND " . $topics[1]->id;
					$descriptions[] = sprintf(
						/* translators: 1: topic id, 2: another topic id */
						__(
						'Send a message to all devices that are subscribed to \'%1$s\' and are also subscribed to the topic \'%2$s\'.',
						'palasthotel-push-notifications-for-firebase'
					), $topic0, $topic1 );

					$examples[]     = $topics[0]->id . " OR " . $topics[1]->id;
					$descriptions[] = sprintf(
						/* translators: 1: topic id, 2: another topic id */
						__(
						'Send a message to all devices that are subscribed to \'%1$s\' or to \'%2$s\' or to both topics.',
						'palasthotel-push-notifications-for-firebase'
					), $topic0, $topic1 );

					if ( $tcount > 2 ) {
						$topic2         = $topics[2]->id;
						$examples[]     = "$topic0 AND ( $topic1 OR $topic2 )";
						$descriptions[] = sprintf(
							/* translators: 1: topic id, 2: and 3: other topic ids */
							__(
							'Send a message to all devices that are subscribed to \'%1$s\' and are also subscribed to \'%2$s\' or to \'%3$s\' or to both topics.',
							'palasthotel-push-notifications-for-firebase'
						), $topic0, $topic1, $topic2 );
						$examples[]     = "$topic0 OR ( $topic1 AND $topic2 )";
						$descriptions[] = sprintf(
							/* translators: 1: topic id, 2: and 3: other topic ids */
							__(
							'Send a message to all devices that are subscribed to \'%1$s\' or are subscribed to both topics \'%2$s\' and \'%3$s\'.',
							'palasthotel-push-notifications-for-firebase'
						), $topic0, $topic1, $topic2 );
					}
				}

				?>
                <div class="fn__base-control--field">
                    <label class="fn__base-control--label"
                           for="firebase-notifications__conditions">
						<?php esc_html_e( "To devices subscribed to topics condition", 'palasthotel-push-notifications-for-firebase' ); ?>
                        <span id="firebase-notifications_conditions--valid">...</span>
                    </label>
					<?php
					$value    = !empty($draftTopics)? $draftTopics : "";
					$readonly = false;
					if ( $tcount == 1 ) {
						$value    = $topics[0]->id;
						$readonly = true;
					}
					?>
                    <input class="fn__base-control--input"
                           type="text"
                           id="firebase-notifications__conditions"
						<?php echo $readonly ? 'readonly' : ''; ?>
                           value="<?php echo esc_attr( $value ); ?>"
                           name="<?php echo esc_attr( Plugin::POST_META_DRAFT_TOPICS ); ?>"

                    />
                    <input type="text" id="firebase-notifications__conditions-parsed"
                           value=""
                           name="<?php echo esc_attr( Plugin::POST_META_DRAFT_TOPICS_PARSED ); ?>"
                           />
					<?php
					if ( $tcount > 1 ) {
						?>
                        <p class="description">
							<?php
							foreach ( $topics as $i => $item ) {
								echo ( $i > 0 ? ", " : "" ) . "<span class='firebase-notifications__topic--copy'>" . esc_html( $item->id ) . "</span>";
							}
							?>
                        </p>
                        <div class="firebase-notifications__examples">
                            <div class="examples__header"><?php esc_html_e( "Show more examples", 'palasthotel-push-notifications-for-firebase' ); ?></div>
                            <div class="examples__content">
								<?php
								foreach ( $examples as $i => $example ) {
									echo "<p>";
									echo esc_html__( "Example", 'palasthotel-push-notifications-for-firebase' ) . (int) ( $i + 1 ) . ": ";
									echo "<span class='examples__code--wrapper'>";
									echo "<span class='examples__code'>" . esc_html( $example ) . "</span>";
									echo "<span class='examples__copy'>" . esc_html_x( "Use", "post meta box condition examples", 'palasthotel-push-notifications-for-firebase' ) . "</span>";
									echo "</span>";
									echo "<br/>";
									$description = $descriptions[ $i ];
									echo "<span class='description'>" . esc_html( $description ) . "</span>";
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
            <label class="fn__base-control--label"><?php esc_html_e( "Schedule", 'palasthotel-push-notifications-for-firebase' ); ?></label>
            <div class="firebase--notifications__schedule">
	            <?php
                $options = ("publish" === $post->post_status) ?
	                [
		                [
			                "label" => __( "Now", 'palasthotel-push-notifications-for-firebase' ),
			                "value" => "now",
		                ],
		                [
			                "label" => __( "Plan", 'palasthotel-push-notifications-for-firebase' ),
			                "value" => "plan",
		                ],
	                ]
	                :
	                [
		                [
			                "label" => __( "Manually after publication", 'palasthotel-push-notifications-for-firebase' ),
			                "value" => "manually",
		                ],
		                [
			                "label" => __( "On publication", 'palasthotel-push-notifications-for-firebase' ),
			                "value" => "on_publish",
		                ],
                    ];
                $checkedValue = "";
                foreach ($options as $option){
                    if($draftSchedule === $option["value"]){
                        $checkedValue = $option["value"];
                    }
                }
                if(empty($checkedValue)){
                    $checkedValue = $options[0]["value"];
                }

                foreach ($options as $option){
                    $label = $option["label"];
                    $value = $option["value"];
                    ?>
                    <label><input type="radio"
                                  name="<?php echo esc_attr( Plugin::POST_META_DRAFT_SCHEDULE ); ?>"
                                  <?php checked( $checkedValue, $value ); ?>
                                  data-schedule
                                  value="<?php echo esc_attr( $value ); ?>"/> <?php echo esc_html( $label ); ?></label>
                        <?php
                    if($value === "plan") {
	                    $futureDateExample = wp_date( "Y-m-d H:i", time() + DAY_IN_SECONDS );
	                    ?>
                        <label title="yyyy-mm-dd hh:ii for example <?php echo esc_attr( $futureDateExample ); ?>">
                            <input type="datetime-local" data-firebase-schedule-datetime value=""
                                   placeholder="yyyy-mm-dd hh:ii"/><br/>
                            <span class="description">yyyy-mm-dd hh:ii for example "<?php echo esc_html( $futureDateExample ); ?>"</span>
                        </label>
	                    <?php
                    }
                }
                ?>
            </div>
        </div>
		<?php

		do_action( Plugin::ACTION_META_BOX_CUSTOM, $this ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant

		if( count( $topics ) <= 0 ) {

			echo "<p> 🚨" . esc_html__( 'Configuration missing. There are no topics defined', 'palasthotel-push-notifications-for-firebase' ) . "</p>";

		} else if ( "publish" === $post->post_status ) {

			echo "<p>";
			submit_button( __( "Send", 'palasthotel-push-notifications-for-firebase' ), "primary", "firebase-notifications-submit", false );
			printf( "<span class='is-loading'>%s</span>", esc_html__( "Sending message", 'palasthotel-push-notifications-for-firebase' ) );
			printf( "<span class='result-display-sent'> ✅ %s</span>", esc_html__( "Message has been sent!", 'palasthotel-push-notifications-for-firebase' ) );
			printf( "<span class='result-display-scheduled'> ✅ %s</span>", esc_html__( "Message has been scheduled!", 'palasthotel-push-notifications-for-firebase' ) );
			printf( "<span class='error-display'> 🚨 %s</span>", esc_html_x( "Error.", "Send message post meta box response", 'palasthotel-push-notifications-for-firebase' ) );
			echo "</p>";

		}

		$this->renderHistory($post);

	}

	public function renderHistory($post){
		// history
		$messages = $this->plugin->database->getPostMessages( $post->ID );
		$count    = count( $messages );
		if ( $count > 0 ) {
			$timezone = wp_timezone();
			echo "<h3>" . esc_html__( "History", 'palasthotel-push-notifications-for-firebase' ) . "</h3>";
			echo "<ul class='firebase-notifications__history'>";
			foreach ( $messages as $index => $msg ) {
				if ( $index >= 3 ) {
					break;
				}
				?>
                <li class="firebase-notifications__history--item" data-message-id="<?php echo intval( $msg->id ); ?>">
                    <div class="history-item__left">
                        <div class="history-item__title"><?php echo esc_html( $msg->title ); ?></div>
                        <div class="history-item__conditions"><span><?php echo esc_html( $msg->conditionForDisplay() ); ?></span>
                        </div>
						<?php if ( $msg->publish != null && $msg->sent == null ) {
							$publish = new \DateTime( $msg->publish );
							$publish->setTimezone( $timezone );

							$formatted = date_i18n( get_option( 'date_format' ) . " " . get_option( 'time_format' ), $publish->getTimestamp() + $publish->getOffset() );
							printf(
								"<div class='history-item__schedule'>⏱ %s</div>",
								esc_html( $formatted )
							);
						} ?>
                        <div class="history-item_more description">
                            <a href="<?php echo esc_url( $this->plugin->toolsPage->getUrl( $post->ID ) ); ?>"><?php esc_html_e( "More info", 'palasthotel-push-notifications-for-firebase' ); ?></a>
							<?php if ( $msg->publish != null && $msg->sent == null ) {
								echo " | ";
								?><a href='#'
                                     class='delete'><?php esc_html_e( "Delete scheduled message", 'palasthotel-push-notifications-for-firebase' ); ?></a><?php
							}
							?>
                        </div>
                    </div>
                    <div class="history-item__right">
						<span class="history-item__date"><?php
							$created = new \DateTime( $msg->created );
							$created->setTimezone( $timezone );
							$created = date_i18n( get_option( 'date_format' ) . " " . get_option( 'time_format' ), $created->getTimestamp() + $created->getOffset() );
							$sent    = "";
							if ( $msg->sent ) {
								$sent = new \DateTime( $msg->sent );
								$sent->setTimezone( $timezone );
								$sent = date_i18n( get_option( 'date_format' ) . " " . get_option( 'time_format' ), $sent->getTimestamp() + $sent->getOffset() );
							}
							echo "💾 " . esc_html( $created );
							if ( ! empty( $sent ) ) {
								echo "<br>✉️ " . esc_html( $sent );
							}
							?><br><?php echo esc_html( implode( ", ", $msg->plattforms ) ); ?></span>
                    </div>

                </li>
				<?php
			}
			echo "</ul>";
		}
		if ( $count > 1 ) {
			$url = $this->plugin->toolsPage->getUrl( $post->ID );
			echo "<p><a href='" . esc_url( $url ) . "'>";
			echo esc_html(
				sprintf(
					/* translators: %d: number of sent and scheduled messages */
					__( "Show complete history of %d items.", 'palasthotel-push-notifications-for-firebase' ),
					$count
				)
			);
			echo "</a></p>";
		}
	}

	public function save_post($post_id){
	    $saveFields = [
		    Plugin::POST_META_DRAFT_TITLE,
		    Plugin::POST_META_DRAFT_BODY,
		    Plugin::POST_META_DRAFT_TOPICS,
            Plugin::POST_META_DRAFT_TOPICS_PARSED,
		    Plugin::POST_META_DRAFT_SCHEDULE,
            Plugin::POST_META_DRAFT_PLATFORMS,
        ];
	    // only from this meta box, and only by someone who may send messages
	    $fromMetaBox = isset( $_POST[ self::NONCE_NAME ] )
	        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
	        && current_user_can( 'edit_post', $post_id )
	        && $this->plugin->permissions->canSendMessages();
	    foreach ( $fromMetaBox ? $saveFields : [] as $field){
		    if(isset($_POST[$field])){
		        if(is_string($_POST[$field])){
			        update_post_meta(
				        $post_id,
				        $field,
				        wp_slash( sanitize_text_field( wp_unslash( $_POST[$field] ) ) )
			        );
		        } else if(is_array($_POST[$field])){
			        $values = wp_slash( array_map( 'sanitize_text_field', wp_unslash( $_POST[$field] ) ) );
			        update_post_meta(
				        $post_id,
				        $field,
				        $values
			        );
		        }
		    }
	    }

	    if($this->wasPublished){
	        $post = get_post($post_id);
	        $this->sendDraft($post);
	    }
	}

	public function transition_post_status( $new_status, $old_status ) {
		if ( "publish" !== $new_status ) {
			return;
		}
		if ( ! in_array( $old_status, [ "new", "pending", "draft", "auto-draft", "future" ] ) ) {
			return;
		}

		$this->wasPublished = true;
	}

	public function sendDraft($post){
		$schedule = get_post_meta($post->ID, Plugin::POST_META_DRAFT_SCHEDULE, true);
		if("on_publish" !== $schedule){
			return;
		}

		$title = get_post_meta($post->ID, Plugin::POST_META_DRAFT_TITLE, true);
		$body = get_post_meta($post->ID, Plugin::POST_META_DRAFT_BODY, true);
		$platforms = get_post_meta($post->ID, Plugin::POST_META_DRAFT_PLATFORMS, true);
		$topics = get_post_meta($post->ID, Plugin::POST_META_DRAFT_TOPICS_PARSED, true);
		if(empty($title) || empty($body) || empty($platforms) || empty($topics)){
			return;
		}

		$conditionsArr = json_decode($topics);
		if(!Validation::isValidConditions($conditionsArr)){
			return;
		}
		$conditionsArr = Validation::sanitizeConditions($conditionsArr);
		$payload = array(
			"post_id"   => $post->ID,
			"permalink" => get_permalink( $post ),
		);

		$message = Message::build($platforms,$conditionsArr, $title, $body, $payload);
		do_action(Plugin::ACTION_SAVE_MESSAGE, $message); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant
		$message_id = $this->plugin->database->add($message);
		if(!$message_id) {
			Log::error("Could not save the notification for post {$post->ID}.");
			return;
		}
		do_action(Plugin::ACTION_SAVED_MESSAGE, $message); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefixed hook name from a constant

		// This runs while the post is saved, in the editor's REST request or a form
		// submit: a failure must not replace that response. The message stays in
		// the history as not sent.
		try{

			$result = $this->plugin->cloudMessagingApi->send($message);
			$message->result = $result;
			$success = $this->plugin->database->setSent( $message->id, $result);

			if(!$success){
				Log::error("Could not mark notification {$message->id} as sent.");
			}
		} catch ( \Throwable $e ) {
			Log::error("Could not send notification {$message->id} on publication: " . $e->getMessage());
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