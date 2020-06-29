<?php
get_header();
?>
    <style>
        body.firebase-notifications__is-not-supported [data-firebase-notifications]{
            display: none;
        }
        [data-firebase-notifications-is-not-supported]{
            display: none;
        }
        body.firebase-notifications__is-not-supported [data-firebase-notifications-is-not-supported]{
            display: block;
        }
    </style>
	<div data-firebase-notifications>
		<label data-firebase-notifications-global>
			<input
					type="checkbox"
					data-firebase-notifications-active
			/> Activate notifications.
		</label>

		<div data-firebase-notifications-global>
			<a href="#" data-firebase-notifications-link>Notification settings.</a>
		</div>

		<hr />

		<?php
		$topics = firebase_notifications_get_topics();
		foreach ($topics as $topic){
			?>
			<label data-firebase-notifications-wrapper-of="<?php echo $topic->id; ?>">
				<input type="checkbox"
				       checked
				       data-firebase-notifications-topic="<?php echo $topic->id; ?>"
				>
				<?php echo $topic->name; ?>
			</label>
			<?php
		}
		?>
	</div>
    <div data-firebase-notifications-is-not-supported>
        <p>Your browser is not compatible with our browser notifications.</p>
    </div>
<?php

get_sidebar();
get_footer();
