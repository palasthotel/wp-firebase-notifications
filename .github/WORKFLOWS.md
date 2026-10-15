# CI/CD Workflows

The four workflows in `.github/workflows/` call the shared ones in
[palasthotel/github-workflows](https://github.com/palasthotel/github-workflows). How
they work, every input and what to do when a deploy fails is described there, in
[docs/wp-plugin.md](https://github.com/palasthotel/github-workflows/blob/main/docs/wp-plugin.md).

What is specific to this plugin:

| | |
|---|---|
| wordpress.org slug | `palasthotel-push-notifications-for-firebase` (plugin name "Palasthotel Push Notifications for Firebase"). It is also the text domain - every gettext call has it as a literal, Plugin Check requires that - and the npm `name` in `package.json`. It stands in `pr.yml`, `wordpress-svn-release.yml` and the `pack` script in `package.json`; change them together. Until the plugin is approved on wordpress.org the SVN steps of `wordpress-svn-release.yml` fail; the zip is attached to the GitHub release before them |
| prefixes | The rename changed only the name, the text domain and the slug. Hook names (`firebase_notifications_*`), option names (`_firebase_notifications_*`), post meta keys (`firebase_notification_*`), the AJAX actions (`firebase-notifications_*`), the REST namespace `firebase-notifications/v1`, the database tables and the PHP namespace stay as they were, so existing sites keep their settings, their history and their custom filters |
| main file | `public/Plugin.php` - kept as it is: WordPress identifies a plugin by directory and main file, and existing installs have it as `plugin-firebase-notifications/Plugin.php`. The zip unpacks to a directory named after the slug, so a site switching to the zip either keeps the directory name or activates the plugin again |
| version file | `package.json` (`release-type: node`); release-please starts after `1.1.2` (`bootstrap-sha`), the old tags have no `v` |
| build step | `bash bin/prefix-dependencies.sh` in `pr.yml` and the deploy - see below |
| `required-files` | both autoloaders, the prefixed SDK, the JS/CSS the plugin enqueues and the theme template |
| `php -l` | 8.1 to 8.5 - `Requires PHP: 8.1` |
| Plugin Check | on, errors fail the PR. Plugin Check ignores `vendor/`, so the bundled SDK in `vendor/prefixed/` is not checked. Each remaining `phpcs:ignore` gives its reason (table names from `$wpdb->prefix`, hook names from the prefixed constants in `Plugin`, public endpoints without a nonce). A file with a PHP syntax error passes Plugin Check silently - the `php -l` job catches that |

## Bundled, prefixed Firebase SDK

The plugin ships kreait/firebase-php and its dependencies (guzzle, google/auth,
google/cloud-*, ...) itself. So that they cannot collide with another plugin's copy of
the same libraries, [Strauss](https://github.com/BrianHenryIE/strauss) rewrites their
namespaces to `Palasthotel\FirebaseNotifications\Vendor\...` (global classes get the
prefix `Palasthotel_FirebaseNotifications_Vendor_`, global functions
`palasthotel_firebasenotifications_vendor_`). The plugin code only uses the prefixed names.

1. `bin/prefix-dependencies.sh` (the `build-command`) runs `composer install` in
   `public/` - kreait/firebase-php is a **dev** dependency there - and the pinned,
   checksum-verified Strauss phar, which writes the prefixed copy and its own
   autoloader to `public/vendor/prefixed/`.
2. The pack copies `public/` and runs `composer install --no-dev` and
   `composer dump-autoload --no-dev --optimize` in the payload. `--no-dev` removes the
   unprefixed SDK again; `vendor/prefixed/` is not a composer package and stays.
   `vendor/autoload.php` is left with the plugin's own classes only.
3. `public/Plugin.php` loads `vendor/prefixed/autoload.php` and `vendor/autoload.php`.

Locally: run `bin/prefix-dependencies.sh` once after a checkout and after every change
to `public/composer.json` or `public/composer.lock`. The unprefixed dev copy in
`public/vendor/` is harmless, the plugin never uses it.

A Dependabot composer update only changes `public/composer.lock`; the PR checks prefix
the new versions, so a library that Strauss cannot handle fails there.

Since 2.0.0 the repository root has no `composer.json` anymore, so the plugin can no
longer be installed with composer from the git repository. Install the release zip.
