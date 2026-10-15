# Contributing

## Branching

`main` is the default branch and always reflects what is released (or about to be
released). Work on a feature branch and open a pull request against `main`.

## Commit messages

Releases and the changelog are generated from the commit history, so commit messages
follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>[optional scope][!]: <description>

[optional body]

[optional footer]
```

| Type | Effect on the version | Appears in changelog |
|---|---|---|
| `fix:` | patch (1.2.3 → 1.2.4) | yes, "Bug Fixes" |
| `feat:` | minor (1.2.3 → 1.3.0) | yes, "Features" |
| `feat!:` or `BREAKING CHANGE:` footer | major (1.2.3 → 2.0.0) | yes, highlighted |
| `docs:`, `refactor:`, `chore:`, `deps:`, `style:`, `test:`, `ci:` | none | no |

A pull request that should trigger a release needs at least one `fix:` or `feat:`
commit. When squash-merging, make sure the squash commit message itself is a
conventional commit — that is the message release-please reads.

### Which changes get `fix:` or `feat:`

Only changes that matter to someone using the plugin. `fix:` and `feat:` decide the
version *and* write the line that ends up in the changelog on the wordpress.org
plugin page, so the question to ask before committing is whether a user of the plugin
would care about that line.

Everything else takes a type that releases nothing — workflows and CI, release
tooling, repository documentation, internal refactoring, and anything touching files
that are not shipped. As a rule of thumb, a change confined to files outside
`public/` is almost never a `fix:`.

That includes hardening. Blocking direct access to a file that is not part of the
download is `chore:`, not `fix:` — nothing changes for anyone who installed the
plugin.

## Repository layout

See [README.md](README.md#repository-layout).

## Local setup

```sh
bash bin/prefix-dependencies.sh   # → public/vendor/ incl. the prefixed SDK in public/vendor/prefixed/
npx wp-env start                  # http://localhost:8888, admin / password
```

Run `bin/prefix-dependencies.sh` again after every change to `public/composer.json` or
`public/composer.lock`. It needs PHP and composer; see
[.github/WORKFLOWS.md](.github/WORKFLOWS.md#bundled-prefixed-firebase-sdk) for what it does.

`npm run pack` stages the payload in `build/palasthotel-push-notifications-for-firebase/`
and zips it - the same payload the release deploys. It runs the shared script from
[palasthotel/github-workflows](https://github.com/palasthotel/github-workflows), which has
to be checked out next to this repository, and needs `composer`.

The main file `public/Plugin.php` must keep its name. WordPress identifies an installed
plugin by `<directory>/<main file>` and stores that pair in `active_plugins`; renaming it
deactivates the plugin on every site at the next update.

Hook, option and post meta names keep the prefix `firebase_notifications_` from
version 1.x - sites depend on them. New hooks use the same prefix. The text domain is
`palasthotel-push-notifications-for-firebase`, always as a literal string.

For testing, use synthetic data only: a throwaway service account with a self-made key,
fictional posts and topics. Never put a real service account into the repository, an
issue or a log.

## Versions

Never edit version numbers by hand. `package.json`, `CHANGELOG.md`, `public/Plugin.php`
and the `Stable tag:` in `public/readme.txt` are all maintained by the release pipeline -
see [.github/WORKFLOWS.md](.github/WORKFLOWS.md).

Content changes to `public/readme.txt` (description, FAQ, tested-up-to) are done by hand;
just leave `Stable tag:` and the `== Changelog ==` entries alone.

`Tested up to:` is only in `public/readme.txt`, as major.minor (e.g. `7.1`), never in the
plugin header.

## Translations

The plugin ships a German translation in `public/languages/`, which WordPress 6.7+ loads
through the `Text Domain` and `Domain Path` headers. When you add or change strings,
regenerate the template and update the German file:

```sh
wp i18n make-pot public public/languages/palasthotel-push-notifications-for-firebase.pot --exclude=vendor
msgmerge -U public/languages/palasthotel-push-notifications-for-firebase-de_DE.po public/languages/palasthotel-push-notifications-for-firebase.pot
msgfmt -o public/languages/palasthotel-push-notifications-for-firebase-de_DE.mo public/languages/palasthotel-push-notifications-for-firebase-de_DE.po
```

Once the plugin has a German language pack on translate.wordpress.org, it takes
precedence, and the bundled files can go.

## Checks

Every PR runs `php -l` against PHP 8.1 to 8.5, builds and packs the plugin, asserts that
the payload holds every file the plugin loads and none of the repository-only ones,
runs the wordpress.org Plugin Check on the packed payload - an error fails the PR - and
checks the version carriers agree.
