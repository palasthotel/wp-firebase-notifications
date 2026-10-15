# Palasthotel Push Notifications for Firebase (WordPress plugin)

Send push notifications from the WordPress post editor to Android, iOS and web apps via
[Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging) (FCM)
topics. Editors write a notification in a meta box, send it right away, schedule it or
let it go out on publication, and see the history per post and under Tools.

On wordpress.org: `palasthotel-push-notifications-for-firebase`. The user documentation
(setup, payload format, hooks, external services) is in [public/readme.txt](public/readme.txt).
This file is for developers.

## How it works

- **Topics.** Apps and browsers subscribe to FCM topics. The plugin sends to a
  *condition* of topics, e.g. `news AND (sport OR culture)`, which becomes
  `'news' in topics && ('sport' in topics || 'culture' in topics)`. The topics are
  managed under Settings > Writing (option `_firebase_notifications_topics`) and pass
  through the filter `firebase_notifications_topics`.
- **Sending.** The meta box posts to the AJAX action `firebase-notifications_send`
  (nonce, `publish_posts` or the filter `firebase_notifications_current_user_can_send_message`).
  The message is stored in `{prefix}firebase_notification_messages` (and linked to the
  post in `{prefix}firebase_notification_messages_from_posts`), then sent with the
  bundled Firebase Admin SDK. Scheduled messages go out with the hourly WP-Cron event
  `firebase_notifications_send_messaged_schedule`; "On publication" sends while the post
  is saved. With `WP_DEBUG` on, nothing is sent.
- **Web push (optional).** With a web app configuration the plugin serves
  `/firebase-messaging-sw.js`, registers `window.FirebaseNotifications` and offers a
  subscription page at `/__app/notifications/` (template
  `firebase-notifications-settings.php`, overridable in the theme). The browser
  subscribes through the public AJAX actions `firebase-notifications_subscribe`,
  `_unsubscribe` and `_topics`, which only accept the topics the site offers.

## The message an app receives

Built in `Message::getCloudMessageArray()`, filtered by `firebase_notifications_message`:

```json
{
  "condition": "'news' in topics && ('sport' in topics || 'culture' in topics)",
  "data": { "post_id": "42", "permalink": "https://example.com/an-article/" },
  "notification": { "title": "…", "body": "…" },
  "android": { "priority": "normal", "notification": { "title": "…", "body": "…" } },
  "apns": {
    "headers": { "apns-priority": "10" },
    "payload": {
      "notification": { "title": "…", "body": "…" },
      "aps": { "alert": { "title": "…", "body": "…" } }
    }
  },
  "webpush": { "notification": { "title": "…", "body": "…" } }
}
```

- `data` is the payload from `firebase_notifications_message_payload`. FCM requires
  string values, so `post_id` arrives as a string.
- `android`, `apns` and `webpush` are only there for the platforms the editor chose. The
  top-level `notification` is only there when all three are chosen.

An app reads `post_id` or `permalink` from `data` to open the article.

## App bridges

`window.FirebaseNotifications` (in `public/js/api-frontend.js`) uses whichever
interface the page runs in:

| Interface | Provided by | Methods |
|---|---|---|
| `window.AndroidAppSubscriptions` | the Android app's WebView | `isNotificationsEnabled()`, `setNotificationsEnabled(bool)`, `subscribe(topic)`, `unsubscribe(topic)`, `isSubscribed(topic)` |
| `window.iOSNotifications` | the iOS app's WebView | `getNotificationsStatus()` (Promise: `open`, `allowed`, `denied`), `requestNotificationsPermission()`, `openSettingsPage()`, `subscribe(topic)`, `unsubscribe(topic)`, `isSubscribed(topic)` |
| `window.FirebaseMessagingWebapp` | the plugin, with a web app configuration | web push in the browser |

## Configuration

| | |
|---|---|
| Service account | `define( 'FIREBASE_NOTIFICATIONS_SERVICE_ACCOUNT', '/path/to/service-account.json' );` in `wp-config.php` (a path or the JSON itself). If defined, it wins over the option `_firebase_notifications_config_json`, and the settings page never prints it |
| Topics | Settings > Writing, plus the filter `firebase_notifications_topics`. Without stored topics the plugin offers `default` |
| Post types | Settings > Writing (option `_firebase_notifications_post_types`, default `post`) |
| REST | `GET /wp-json/firebase-notifications/v1/count?since=<timestamp>` is public. Restrict it with `firebase_notifications_rest_count_permission` |

```php
// only logged-in editors may read the count
add_filter( 'firebase_notifications_rest_count_permission', function () {
	return current_user_can( 'edit_posts' );
} );

// open the article in the app with tracking
add_filter( 'firebase_notifications_message_payload', function ( array $payload ) {
	$payload['permalink'] = add_query_arg( 'utm_source', 'push', $payload['permalink'] );
	return $payload;
} );

// forward errors from sending on publication and from the scheduler
add_action( 'firebase_notifications_error', function ( string $message ) {
	my_monitoring_log( $message );
} );
```

All hooks are listed in [public/readme.txt](public/readme.txt#hooks). Names are kept from
version 1.x: the prefix `firebase_notifications_` stays, although the plugin was
renamed in 2.0.0.

## Repository layout

| Path | |
|---|---|
| `public/` | the plugin as it ships to wordpress.org, main file `Plugin.php` |
| `public/vendor/prefixed/` | the Firebase SDK with prefixed namespaces, written by `bin/prefix-dependencies.sh`, not in git |
| `bin/prefix-dependencies.sh` | installs the SDK and prefixes it with Strauss, the CI build step |
| `firebase-notifications.php` | development wrapper that loads `public/`, never shipped |
| `tests/js/` | manual browser checks for the app bridges and the condition parser, not shipped |
| `.github/` | workflows, see [.github/WORKFLOWS.md](.github/WORKFLOWS.md) |

See [CONTRIBUTING.md](CONTRIBUTING.md) for the local setup and commit conventions.

## Planned

The browser side still uses jQuery and the Firebase JavaScript SDK 8 (namespaced API).
Replacing them is planned for a later release.

## License

[GPL-3.0-or-later](LICENSE). Versions up to 1.1.2 named GPLv2 in their plugin header.
