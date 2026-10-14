<?php

/**
 * Plugin Name:       Firebase Notifications - DEV
 * Plugin URI:        https://github.com/palasthotel/wp-firebase-notifications
 * Description:       Development wrapper. Loads the plugin from public/, which is what ships. Do not deploy this file.
 * Version:           0.0.0-dev
 * Requires at least: 5.3
 * Requires PHP:      8.1
 * Author:            Palasthotel <webmaster@palasthotel.de>
 * Author URI:        https://palasthotel.de
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       firebase-notifications
 */

defined( 'ABSPATH' ) || exit;

// The version above is deliberately not a real one and nothing syncs it. This file never
// ships, so its version means nothing, and the release's version check does not read it.

include dirname( __FILE__ ) . "/public/Plugin.php";
