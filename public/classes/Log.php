<?php

namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reports errors that happen without a request that could show them, e.g. when a
 * notification is sent on publication or by the scheduler.
 */
class Log {

	/**
	 * Fires the action firebase_notifications_error, so that a site can forward the
	 * error to its monitoring, and writes it to the debug log if WP_DEBUG_LOG is on.
	 *
	 * @param string $message
	 */
	public static function error( string $message ): void {
		/**
		 * An error the plugin could not report to a user.
		 *
		 * @param string $message
		 */
		do_action( 'firebase_notifications_error', $message );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- only with WP_DEBUG_LOG on, reports failed sends
			error_log( 'Palasthotel Push Notifications for Firebase: ' . $message );
		}
	}
}
