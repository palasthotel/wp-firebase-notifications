<?php
get_header();
?>
	<div>
		<label>
			<input
					type="checkbox"
					data-firebase-notifications-active
			/> Activate notifications.
		</label>
		<hr />
		<?php
		$topics = firebase_notifications_get_topics();
		foreach ($topics as $topic){
			?>
			<label>
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
<?php

get_sidebar();
get_footer();
