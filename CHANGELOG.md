# Changelog

## [2.0.0](https://github.com/palasthotel/wp-firebase-notifications/compare/v1.1.2...v2.0.0) (2026-10-28)


### ⚠ BREAKING CHANGES

* requires PHP 8.1 or newer; the Firebase SDK is bundled with prefixed namespaces

### Features

* let sites restrict the REST count endpoint ([d9170ea](https://github.com/palasthotel/wp-firebase-notifications/commit/d9170eaa99e16085c902850048afde4688567abe))
* manage the topics in the settings ([6811020](https://github.com/palasthotel/wp-firebase-notifications/commit/681102030725d26ed0573c84ec60beaf6e2a5ddd))
* read the service account from wp-config.php ([878b2d6](https://github.com/palasthotel/wp-firebase-notifications/commit/878b2d6e5d7216e0185a309f9aba7d6f811c492c))
* rename the plugin to Palasthotel Push Notifications for Firebase ([b169605](https://github.com/palasthotel/wp-firebase-notifications/commit/b16960541e8e746c0b449c9f28a59d39d068a20c))
* update the Firebase SDK to 7.24 and bundle it with prefixed namespaces ([7b65724](https://github.com/palasthotel/wp-firebase-notifications/commit/7b657245d016055e72948f8c95c462b0c610cf6a))


### Bug Fixes

* answer the public subscription requests with an error instead of a fatal error ([6505bac](https://github.com/palasthotel/wp-firebase-notifications/commit/6505bacea86824c47b59ed94a882efcba3d70d95))
* apply the payload filter to notifications sent on publication ([3345781](https://github.com/palasthotel/wp-firebase-notifications/commit/33457817419ee84b45bfd62386b6df03088ed7ef))
* avoid a PHP 8.1+ deprecation for messages that were not sent yet ([5c59ad1](https://github.com/palasthotel/wp-firebase-notifications/commit/5c59ad114de4d3796763acbc70f68132b5d552cb))
* declare the properties of Message, avoiding PHP 8.2+ dynamic property deprecations ([1258fb9](https://github.com/palasthotel/wp-firebase-notifications/commit/1258fb968ad92ad9bd5f0f43e6b03696f16fae91))
* escape notification data in the admin, sanitize the payload ([a10500a](https://github.com/palasthotel/wp-firebase-notifications/commit/a10500a04f26a3267c66dd8f8d043cd524663cef))
* keep saving the post when sending its notification on publication fails ([96e9c36](https://github.com/palasthotel/wp-firebase-notifications/commit/96e9c36b891d0498fe5a00aa6ba6830373a3696c))
* pass the text domain to __() correctly and guard two files against direct access ([35ea47f](https://github.com/palasthotel/wp-firebase-notifications/commit/35ea47f2eaad6c043b41126504b60e9a37a1a10b))
* require a nonce and the send permission for sending and deleting messages ([7c50845](https://github.com/palasthotel/wp-firebase-notifications/commit/7c508450c713900ce69b28d8129fdf4c9b05be48))
* sanitize the settings when they are saved and escape the notification icon field ([14efc16](https://github.com/palasthotel/wp-firebase-notifications/commit/14efc168caed748267926598561367ef3724bd45))
* save push draft fields only from the meta box, by users who may send ([1e147cf](https://github.com/palasthotel/wp-firebase-notifications/commit/1e147cfd627bac7f918f7eb328b2f16981082a00))
* show the history with a UTC offset time zone and the real send time ([88233a9](https://github.com/palasthotel/wp-firebase-notifications/commit/88233a96461bb264e85ab36d70c13041857d9945))
* stop loading a missing test script on the notification settings page ([5feca1d](https://github.com/palasthotel/wp-firebase-notifications/commit/5feca1d7d9c6d9ad39eb5f96c3ad52683d74676a))
* subscribe visitors only to the topics the site offers ([2c4c88f](https://github.com/palasthotel/wp-firebase-notifications/commit/2c4c88f319d9afa821d6e094a70872614fdb3098))
* validate topic conditions on the server, brackets included ([3c6ed92](https://github.com/palasthotel/wp-firebase-notifications/commit/3c6ed92f2c4e97afda3e06afb21b06f964740b7e))
