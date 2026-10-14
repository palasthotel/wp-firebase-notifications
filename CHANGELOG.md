# Changelog

## [2.0.0](https://github.com/palasthotel/wp-firebase-notifications/compare/v1.1.2...v2.0.0) (2026-10-14)


### ⚠ BREAKING CHANGES

* requires PHP 8.1 or newer; the Firebase SDK is bundled with prefixed namespaces

### Features

* update the Firebase SDK to 7.24 and bundle it with prefixed namespaces ([7b65724](https://github.com/palasthotel/wp-firebase-notifications/commit/7b657245d016055e72948f8c95c462b0c610cf6a))


### Bug Fixes

* escape notification data in the admin, sanitize the payload ([a10500a](https://github.com/palasthotel/wp-firebase-notifications/commit/a10500a04f26a3267c66dd8f8d043cd524663cef))
* pass the text domain to __() correctly and guard two files against direct access ([35ea47f](https://github.com/palasthotel/wp-firebase-notifications/commit/35ea47f2eaad6c043b41126504b60e9a37a1a10b))
* require a nonce and the send permission for sending and deleting messages ([7c50845](https://github.com/palasthotel/wp-firebase-notifications/commit/7c508450c713900ce69b28d8129fdf4c9b05be48))
* save push draft fields only from the meta box, by users who may send ([1e147cf](https://github.com/palasthotel/wp-firebase-notifications/commit/1e147cfd627bac7f918f7eb328b2f16981082a00))
* show the history with a UTC offset time zone and the real send time ([88233a9](https://github.com/palasthotel/wp-firebase-notifications/commit/88233a96461bb264e85ab36d70c13041857d9945))
* subscribe visitors only to the topics the site offers ([2c4c88f](https://github.com/palasthotel/wp-firebase-notifications/commit/2c4c88f319d9afa821d6e094a70872614fdb3098))
