=== Firebase Notifications ===
Contributors: palasthotel, edwardbock, janaeggebrecht
Tags: push notifications, firebase, cloud messaging
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 2.0.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Send push notifications to apps and browsers with Firebase Cloud Messaging.

== Description ==

Firebase Notifications sends push messages to topics in Firebase Cloud Messaging (FCM). Editors write a notification in a meta box on the post edit screen, send it right away or schedule it, and see the sent messages on a tools page. Visitors can subscribe to topics in the browser.

You need a Firebase project and its service account JSON, which you paste into the plugin settings.

The Firebase Admin SDK for PHP (kreait/firebase-php) is bundled with prefixed namespaces, so it does not conflict with other plugins that ship the same libraries.

== Changelog ==

= 2.0.0 =
**⚠ BREAKING CHANGES**
* requires PHP 8.1 or newer; the Firebase SDK is bundled with prefixed namespaces

**Features**
* update the Firebase SDK to 7.24 and bundle it with prefixed namespaces (7b65724)

**Bug Fixes**
* escape notification data in the admin, sanitize the payload (a10500a)
* pass the text domain to __() correctly and guard two files against direct access (35ea47f)
* require a nonce and the send permission for sending and deleting messages (7c50845)
* save push draft fields only from the meta box, by users who may send (1e147cf)
* show the history with a UTC offset time zone and the real send time (88233a9)
* subscribe visitors only to the topics the site offers (2c4c88f)

= 1.1.2 =
* Last release before the change log was kept here.
