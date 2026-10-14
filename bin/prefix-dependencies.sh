#!/usr/bin/env bash
# Installs the Firebase SDK and writes a copy of it with prefixed namespaces
# (Palasthotel\FirebaseNotifications\Vendor\...) to public/vendor/prefixed/, using
# Strauss (https://github.com/BrianHenryIE/strauss). That copy, with its own
# autoloader, is what the plugin loads and what ships.
#
# kreait/firebase-php is a dev dependency in public/composer.json on purpose: the pack
# (github-workflows wp-plugin/bin/pack.sh) runs "composer install --no-dev" in the
# payload, which removes the unprefixed SDK again and leaves vendor/prefixed/ alone,
# so the release contains exactly one copy of the SDK - the prefixed one.
#
# The build-command in .github/workflows/*.yml runs this; run it locally after a
# checkout and after changing public/composer.json or public/composer.lock.
set -euo pipefail

STRAUSS_VERSION="0.31.1"
STRAUSS_SHA256="17dc2baf27b9a3f52834e3db729c8365d8915a80e37a087d253cf705387aa887"

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CACHE="$ROOT/bin/.cache"
PHAR="$CACHE/strauss-$STRAUSS_VERSION.phar"

sha256() {
  if command -v sha256sum >/dev/null; then sha256sum "$1" | cut -d' ' -f1
  else shasum -a 256 "$1" | cut -d' ' -f1
  fi
}

if [[ ! -f "$PHAR" ]]; then
  echo "Downloading Strauss $STRAUSS_VERSION ..."
  mkdir -p "$CACHE"
  curl -fsSL -o "$PHAR.download" \
    "https://github.com/BrianHenryIE/strauss/releases/download/$STRAUSS_VERSION/strauss.phar"
  if [[ "$(sha256 "$PHAR.download")" != "$STRAUSS_SHA256" ]]; then
    rm -f "$PHAR.download"
    echo "ERROR: the Strauss phar does not match the pinned checksum" >&2
    exit 1
  fi
  mv "$PHAR.download" "$PHAR"
fi

cd "$ROOT/public"
echo "Installing composer dependencies with dev ..."
composer install --no-interaction --no-progress
echo "Prefixing the Firebase SDK into vendor/prefixed/ ..."
rm -rf vendor/prefixed
# Strauss needs more than the 128 MB memory_limit of a stock php.ini
php -d memory_limit=-1 "$PHAR"
