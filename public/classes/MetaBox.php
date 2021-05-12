<?php

namespace Palasthotel\FirebaseNotifications;


use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;

/**
 * @property Plugin plugin
 */
class MetaBox {

	/**
	 * @var bool
	 */
	private $wasPublished = false;

	/**
	 * MetaBox constructor.
	 *
	 * @param Plugin $plugin
	 */
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
			__( 'Firebase Notifications', Plugin::DOMAIN ),
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
			printf( "<p>%s</p>", __( "Post needs to be saved.", Plugin::DOMAIN ) );

			return;
		}

		$this->plugin->assets->enqueueMetaBoxStyle();
		$this->plugin->assets->enqueueMetaBoxScript( array(
			"restrictions" => apply_filters(
				Plugin::FILTER_META_BOX_RESTRICTIONS,
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
					"text"     => __( "%d chars. ", Plugin::DOMAIN ),
					"short"    => __( "Could be too short 🤔", Plugin::DOMAIN ),
					"good"     => __( "Seems to be a good length 👍", Plugin::DOMAIN ),
					"long"     => __( "Could be a little too long ⚠️", Plugin::DOMAIN ),
					"too_long" => __( "Will probably be too long 🚨️", Plugin::DOMAIN ),
				),

				"empty_conditions"      => __( "You need to choose at least one topic", Plugin::DOMAIN ),
				"limitation_conditions" => __( "You can use max of 4 logical operations", Plugin::DOMAIN ),
				"invalid"               => __( "Invalid", Plugin::DOMAIN ),
				"valid"                 => __( "Valid", Plugin::DOMAIN ),

				"submit" => array(
					"now"  => __( "Send", Plugin::DOMAIN ),
					"plan" => __( "Plan", Plugin::DOMAIN ),
				),

				"confirms" => array(
					"overwrite_conditions" => __( "This will overwrite your current topics condition. Proceed?", Plugin::DOMAIN ),
				),
				"errors"   => array(
					"title"      => __( "Give me a message title, please.", Plugin::DOMAIN ),
					"body"       => __( "Type some body content.", Plugin::DOMAIN ),
					"conditions" => __( "Please define your topic conditions.", Plugin::DOMAIN ),
					"platforms" => __( "At least one platform needs to be activated.", Plugin::DOMAIN ),
					"schedule"   => array(
						"invalid"     => __( "Please provide a valid schedule date that is at least a few minutes in the future.", Plugin::DOMAIN ),
						"in_the_past" => __( "Schedule date must be at least a few minutes in the future.", Plugin::DOMAIN ),
					)
				),
			),
			"topic_ids" => $this->plugin->topics->getTopicIds(),
			"payload"   => array(
				"post_id"   => $post->ID,
				"permalink" => get_permalink( $post ),
			),
		) );
		do_action( Plugin::ACTION_ENQUEUE_META_BOX_ENQUEUE_SCRIPT, Plugin::HANDLE_META_BOX_SCRIPT );
		$draftTitle = get_post_meta($post->ID, Plugin::POST_META_DRAFT_TITLE, true);
		$draftBody = get_post_meta($post->ID, Plugin::POST_META_DRAFT_BODY, true);
		$draftPlatforms = get_post_meta($post->ID, Plugin::POST_META_DRAFT_PLATFORMS, true);
		$draftTopics = get_post_meta($post->ID, Plugin::POST_META_DRAFT_TOPICS, true);
		$draftSchedule = get_post_meta($post->ID, Plugin::POST_META_DRAFT_SCHEDULE, true);

		if(!is_array($draftPlatforms)){
		    $draftPlatforms = ["android", "ios", "web"];
        }
		?>
        <div class="fn__wrapper">
            <div class="fn__base-control--field">
                <label class="fn__base-control--label"
                       for="firebase-notifications__title"
                ><?php _e( "Title", Plugin::DOMAIN ); ?></label>
                <input class="fn__base-control--input"
                       type="text"
                       id="firebase-notifications__title"
                       maxlength="190"
                       value="<?= !empty($draftTitle) ? $draftTitle : get_the_title(); ?>"
                       name="<?= Plugin::POST_META_DRAFT_TITLE; ?>"
                />
            </div>
            <div class="fn__base-control--field">
                <label class="fn__base-control--label"
                       for="firebase-notifications__body"><?php _e( "Body", Plugin::DOMAIN ); ?></label>
                <textarea class="fn__base-control--input"
                          id="firebase-notifications__body"
                          rows="4"
                          name="<?= Plugin::POST_META_DRAFT_BODY; ?>"
                ><?= !empty($draftBody) ? $draftBody : $post->post_excerpt; ?></textarea>
            </div>
            <div class="fn__base-control--field">
                <label class="fn__base-control--label"><?php _e( "To devices of platform", Plugin::DOMAIN ); ?></label>
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
                        $checked = in_array($id, $draftPlatforms) ? "checked='checked'" : "";
                        echo "<label>";
                        echo "<input type='checkbox' name='{$name}' {$checked} value='{$id}' data-platform />";
                        echo " $label</label>";
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
					$descriptions[] = sprintf( __(
						"Send a message to all devices that are subscribed to '%s' and are also subscribed to the topic '%s'.",
						Plugin::DOMAIN
					), $topic0, $topic1 );

					$examples[]     = $topics[0]->id . " OR " . $topics[1]->id;
					$descriptions[] = sprintf( __(
						"Send a message to all devices that are subscribed to '%s' or to '%s' or to both topics.",
						Plugin::DOMAIN
					), $topic0, $topic1 );

					if ( $tcount > 2 ) {
						$topic2         = $topics[2]->id;
						$examples[]     = "$topic0 AND ( $topic1 OR $topic2 )";
						$descriptions[] = sprintf( __(
							"Send a message to all devices that are subscribed to '%s' and are also subscribed to '%s' or to '%s' or to both topics.",
							Plugin::DOMAIN
						), $topic0, $topic1, $topic2 );
						$examples[]     = "$topic0 OR ( $topic1 AND $topic2 )";
						$descriptions[] = sprintf( __(
							"Send a message to all devices that are subscribed to '%s' or are subscribed to both topics '%s' and '%s'.",
							Plugin::DOMAIN
						), $topic0, $topic1, $topic2 );
					}
				}

				?>
                <div class="fn__base-control--field">
                    <label class="fn__base-control--label"
                           for="firebase-notifications__conditions">
						<?php _e( "To devices subscribed to topics condition", Plugin::DOMAIN ); ?>
                        <span id="firebase-notifications_conditions--valid">...</span>
                    </label>
					<?php
					$value    = !empty($draftTopics)? $draftTopics : "";
					$readonly = "";
					if ( $tcount == 1 ) {
						$value    = $topics[0]->id;
						$readonly = "readonly";
					}
					?>
                    <input class="fn__base-control--input"
                           type="text"
                           id="firebase-notifications__conditions"
						<?php echo $readonly; ?>
                           value="<?= $value; ?>"
                           name="<?= Plugin::POST_META_DRAFT_TOPICS; ?>"

                    />
                    <input type="text" id="firebase-notifications__conditions-parsed"
                           value=""
                           name="<?= Plugin::POST_META_DRAFT_TOPICS_PARSED; ?>"
                           />
					<?php
					if ( $tcount > 1 ) {
						?>
                        <p class="description">
							<?php echo implode( ", ", array_map( function ( $item ) {
								return "<span class='firebase-notifications__topic--copy'>" . $item->id . "</span>";
							}, $topics ) ); ?>
                        </p>
                        <div class="firebase-notifications__examples">
                            <div class="examples__header"><?php _e( "Show more examples", Plugin::DOMAIN ); ?></div>
                            <div class="examples__content">
								<?php
								foreach ( $examples as $i => $example ) {
									echo "<p>";
									echo __( "Example", Plugin::DOMAIN ) . ( $i + 1 ) . ": ";
									echo "<span class='examples__code--wrapper'>";
									echo "<span class='examples__code'>$example</span>";
									echo "<span class='examples__copy'>" . _x( "Use", "post meta box condition examples", Plugin::DOMAIN ) . "</span>";
									echo "</span>";
									echo "<br/>";
									$description = $descriptions[ $i ];
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
            <label class="fn__base-control--label"><?php _e( "Schedule", Plugin::DOMAIN ); ?></label>
            <div class="firebase--notifications__schedule">
	            <?php
                $options = ("publish" === $post->post_status) ?
	                [
		                [
			                "label" => __( "Now", Plugin::DOMAIN ),
			                "value" => "now",
		                ],
		                [
			                "label" => __( "Plan", Plugin::DOMAIN ),
			                "value" => "plan",
		                ],
	                ]
	                :
	                [
		                [
			                "label" => __( "Manually after publication", Plugin::DOMAIN ),
			                "value" => "manually",
		                ],
		                [
			                "label" => __( "On publication", Plugin::DOMAIN ),
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
                    $checked = $checkedValue === $value ? "checked='checked'": "";
                    ?>
                    <label><input type="radio"
                                  name="<?= Plugin::POST_META_DRAFT_SCHEDULE; ?>"
                                  <?= $checked ?>
                                  data-schedule
                                  value="<?= $value ?>"/> <?= $label ?></label>
                        <?php
                    if($value === "plan") {
	                    $futureDateExample = date( "Y-m-d H:i", time() + 60 * 60 * 24 );
	                    ?>
                        <label title="yyyy-mm-dd hh:ii for example <?php echo $futureDateExample; ?>">
                            <input type="datetime-local" data-firebase-schedule-datetime value=""
                                   placeholder="yyyy-mm-dd hh:ii"/><br/>
                            <span class="description">yyyy-mm-dd hh:ii for example "<?php echo $futureDateExample; ?>"</span>
                        </label>
	                    <?php
                    }
                }
                ?>
            </div>
        </div>
		<?php

		do_action( Plugin::ACTION_META_BOX_CUSTOM, $this );

		if( count( $topics ) <= 0 ) {

			echo "<p> 🚨" . __( 'Configuration missing. There are no topics defined', Plugin::DOMAIN ) . "</p>";

		} else if ( "publish" === $post->post_status ) {

			echo "<p>";
			submit_button( __( "Send", Plugin::DOMAIN ), "primary", "firebase-notifications-submit", false );
			printf( "<span class='is-loading'>%s</span>", __( "Sending message", Plugin::DOMAIN ) );
			printf( "<span class='result-display-sent'> ✅ %s</span>", __( "Message has been sent!", Plugin::DOMAIN ) );
			printf( "<span class='result-display-scheduled'> ✅ %s</span>", __( "Message has been scheduled!", Plugin::DOMAIN ) );
			printf( "<span class='error-display'> 🚨 %s</span>", _x( "Error.", "Send message post meta box response", Plugin::DOMAIN ) );
			echo "</p>";

		}

		$this->renderHistory($post);

	}

	public function renderHistory($post){
		// history
		$messages = $this->plugin->database->getPostMessages( $post->ID );
		$count    = count( $messages );
		if ( $count > 0 ) {
			$tz       = get_option( "timezone_string", "UTC" );
			$timezone = new \DateTimeZone( $tz );
			echo "<h3>" . __( "History", Plugin::DOMAIN ) . "</h3>";
			echo "<ul class='firebase-notifications__history'>";
			foreach ( $messages as $index => $msg ) {
				if ( $index >= 3 ) {
					break;
				}
				?>
                <li class="firebase-notifications__history--item" data-message-id="<?php echo $msg->id; ?>">
                    <div class="history-item__left">
                        <div class="history-item__title"><?php echo $msg->title; ?></div>
                        <div class="history-item__conditions"><span><?php echo $msg->conditionForDisplay(); ?></span>
                        </div>
						<?php if ( $msg->publish != null && $msg->sent == null ) {
							$publish = new \DateTime( $msg->publish );
							$publish->setTimezone( $timezone );

							$formatted = date_i18n( get_option( 'date_format' ) . " " . get_option( 'time_format' ), $publish->getTimestamp() + $publish->getOffset() );
							printf(
								"<div class='history-item__schedule'>⏱ %s</div>",
								$formatted
							);
						} ?>
                        <div class="history-item_more description">
                            <a href="<?php echo $this->plugin->toolsPage->getUrl( $post->ID ); ?>"><?php _e( "More info", Plugin::DOMAIN ); ?></a>
							<?php if ( $msg->publish != null && $msg->sent == null ) {
								echo " | ";
								?><a href='#'
                                     class='delete'><?php _e( "Delete scheduled message", Plugin::DOMAIN ); ?></a><?php
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
								$sent = new \DateTime( $msg->created );
								$sent->setTimezone( $timezone );
								$sent = date_i18n( get_option( 'date_format' ) . " " . get_option( 'time_format' ), $sent->getTimestamp() + $sent->getOffset() );
							}
							echo "💾 $created";
							if ( ! empty( $sent ) ) {
								echo "<br>✉️ $sent";
							}
							?><br><?php echo implode( ", ", $msg->plattforms ) ?></span>
                    </div>

                </li>
				<?php
			}
			echo "</ul>";
		}
		if ( $count > 1 ) {
			$url = $this->plugin->toolsPage->getUrl( $post->ID );
			echo "<p><a href='$url'>";
			printf(
				__( "Show complete history of %d items.", Plugin::DOMAIN ),
				$count
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
        ];
	    foreach ($saveFields as $field){
		    if(isset($_POST[$field])){
		        if(is_string($_POST[$field])){
			        update_post_meta(
				        $post_id,
				        $field,
				        sanitize_text_field($_POST[$field])
			        );
		        } else if(is_array($_POST[$field])){
			        $values = array_map('sanitize_text_field', $_POST[$field]);
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
		do_action(Plugin::ACTION_SAVE_MESSAGE, $message);
		$message_id = $this->plugin->database->add($message);
		if(!$message_id) {
			error_log("Could not save notification message");
			return;
		}
		do_action(Plugin::ACTION_SAVED_MESSAGE, $message);

		try{

			$result = $this->plugin->cloudMessagingApi->send($message);
			$message->result = $result;
			$success = $this->plugin->database->setSent( $message->id, $result);

			if(!$success){
				error_log("Firebase Service connection not working. Check the settings.");
			}
		} catch (\Exception $e){
			wp_send_json_error($e->getMessage());
		} catch ( MessagingException $e ) {
			wp_send_json_error($e->getMessage());
		} catch ( FirebaseException $e ) {
			wp_send_json_error($e->getMessage());
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