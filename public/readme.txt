=== Palasthotel Push Notifications for Firebase ===
Contributors: palasthotel, edwardbock, janaeggebrecht
Tags: push notifications, firebase, cloud messaging, fcm, app
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.1.2
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Send push notifications from the post editor to Android, iOS and web apps via Firebase Cloud Messaging topics.

== Description ==

Palasthotel Push Notifications for Firebase sends push notifications to the topics of a Firebase Cloud Messaging (FCM) project. Your Android and iOS apps - and browsers, if you use the web push support - subscribe to topics, and editors notify them right from the post editor.

* A meta box on the post edit screen: title, text, platforms (Android, iOS, web) and the topics as a condition, e.g. `news AND (sport OR culture)`
* Send right away, schedule for later, or send automatically when the post is published
* The history of every post and a list of all notifications under Tools, with the answer from FCM
* Topics are managed under Settings > Writing; developers can change them with a filter
* The service account can be stored in `wp-config.php` instead of the database
* Optional web push: a service worker and a JavaScript API, so visitors can subscribe to topics in the browser
* Filters and actions for the payload, the message, the permissions and errors

The Firebase Admin SDK for PHP is bundled with prefixed namespaces, so it does not collide with other plugins that ship the same libraries.

Developer documentation: [github.com/palasthotel/wp-firebase-notifications](https://github.com/palasthotel/wp-firebase-notifications)

= The message your app receives =

Notifications go to an FCM condition built from the topics, e.g. `'news' in topics && ('sport' in topics || 'culture' in topics)`. Every message has a `data` payload with string values:

* `post_id` - the ID of the post the notification was sent from
* `permalink` - its URL

plus whatever the filter `firebase_notifications_message_payload` adds. Depending on the platforms chosen, the message contains:

* `android`: `priority` "normal" and `notification` with `title` and `body`
* `apns`: the header `apns-priority` "10" and a `payload` with `aps.alert.title`/`aps.alert.body` and `notification.title`/`notification.body`
* `webpush`: `notification` with `title` and `body`
* with all three platforms also a top-level `notification` with `title` and `body`

The filter `firebase_notifications_message` receives this array before it is sent, e.g. to raise the Android priority.

= Hooks =

Filters:

* `firebase_notifications_topics` - the topics offered, an array of objects with `id` and `name`, by default the topics from the settings
* `firebase_notifications_message_payload` - the data payload of a post's notification
* `firebase_notifications_message` - the complete FCM message array
* `firebase_notifications_current_user_can_send_message` - whether the current user may send, by default `current_user_can( 'publish_posts' )`
* `firebase_notifications_meta_box_restrictions` - the length hints for title and text in the meta box
* `firebase_notifications_settings_url` - the path of the subscription page, by default `__app/notifications`
* `firebase_notifications_rest_count_permission` - whether a request may read the number of sent notifications, by default `true` (public)

Actions:

* `firebase_notifications_save_message` and `firebase_notifications_saved_message` - before and after a message is saved
* `firebase_notifications_message_sent` - after a message was sent, with its ID and the result from FCM
* `firebase_notifications_error` - an error that could not be shown to a user, e.g. while sending on publication or by the scheduler
* `firebase_notifications_meta_box_custom` - to add fields to the meta box
* `firebase_notification_meta_box_enqueue_script` - when the meta box script is enqueued

= REST API =

`GET /wp-json/firebase-notifications/v1/count?since=<unix timestamp>` returns the number of notifications sent since then (default: since midnight). It is public; restrict it with the filter `firebase_notifications_rest_count_permission`.

== External services ==

This plugin connects to Firebase Cloud Messaging and the Google authentication service, both provided by Google. Nothing is sent before an administrator configures a service account.

* When an editor sends or schedules a notification, or a post with "On publication" is published, the plugin sends the notification's title, text, the topic condition and the data payload (by default the post ID and URL) to Firebase Cloud Messaging (fcm.googleapis.com). To authorize this, it requests an access token from Google (oauth2.googleapis.com) with the service account.
* With web push configured, a visitor's browser gets an FCM registration token from Firebase. When the visitor subscribes to or unsubscribes from a topic, or the page checks the subscriptions, the plugin sends this token and the topic to Firebase Cloud Messaging (iid.googleapis.com). The browser itself loads the Firebase JavaScript SDK bundled with the plugin and talks to Firebase directly.

Firebase terms of service: https://firebase.google.com/terms
Google privacy policy: https://policies.google.com/privacy

== Installation ==

1. Install the plugin from Plugins > Add New or upload the ZIP, then activate it.
1. In the [Firebase console](https://console.firebase.google.com/) create a project, or use the project of your app. The Firebase Cloud Messaging API (V1) has to be enabled for it (Project settings > Cloud Messaging).
1. Under Project settings > Service accounts generate a new private key. This downloads the service account JSON file.
1. Give the plugin the service account. Recommended: store the file outside the web root and add this line to `wp-config.php`:
   `define( 'FIREBASE_NOTIFICATIONS_SERVICE_ACCOUNT', '/path/outside/webroot/service-account.json' );`
   The constant may also hold the JSON itself. Alternatively, paste the JSON under Settings > Writing > Google Services; it is then stored in the database.
1. Under Settings > Writing choose the post types with the meta box and enter the topics your apps subscribe to.
1. For web push, also paste the web app configuration (Project settings > Your apps > Web app > SDK configuration) and choose a notification icon.

Editors with the capability `publish_posts` find the meta box below the editor.

== Frequently Asked Questions ==

= Which topics can I use? =

FCM topic names: letters, digits and `- _ . ~ %`, up to 900 characters. Your apps have to subscribe to the same topic names. A condition may combine up to five topics with AND and OR, with one level of brackets.

= Are notifications sent while WP_DEBUG is on? =

No. With `WP_DEBUG` on, the plugin builds and saves the message and records "No message was sent" as the answer instead of sending it, so a development site never notifies real devices.

= How do scheduled notifications get sent? =

By WP-Cron, hourly. On sites with a system cron, make sure it calls `wp-cron.php` or `wp cron event run --due-now`.

= Can I keep the private key out of the database? =

Yes, with the constant `FIREBASE_NOTIFICATIONS_SERVICE_ACCOUNT` in `wp-config.php`, see Installation. If it is defined, it takes precedence over the settings, and the settings only show the project ID.

= I updated from version 1.x - do I lose my settings? =

No. Options, hooks, post meta and the history keep their names. Without topics in the settings the plugin offers the topic "default" as before, and a `firebase_notifications_topics` filter in your code still applies.

== Changelog ==

= 1.1.2 =
* Last release before the change log was kept here.
