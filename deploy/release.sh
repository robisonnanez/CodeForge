#!/bin/sh
set -eu

if [ "$(id -u)" -ne 0 ]; then
    echo "Run this release command as root." >&2
    exit 1
fi

source_dir=${1:-}
commit=${2:-}

test -n "$source_dir"
test -n "$commit"
git -C "$source_dir" cat-file -e "$commit^{commit}"
commit=$(git -C "$source_dir" rev-parse --verify "$commit^{commit}")

case "$commit" in
    *[!0-9a-f]*)
        echo "The release commit contains invalid characters." >&2
        exit 1
        ;;
esac

if [ "${#commit}" -ne 40 ]; then
    echo "The release commit must resolve to a full SHA-1." >&2
    exit 1
fi

release="/var/www/codeforge/releases/$commit"
staging="/var/www/codeforge/releases/.${commit}.staging.$$"

if [ -e "$release" ]; then
    current=$(readlink -f /var/www/codeforge/current 2>/dev/null || true)

    if [ "$current" = "$release" ]; then
        echo "CodeForge commit $commit is already deployed."
        exit 0
    fi

    mv "$release" "${release}.incomplete.$(date -u +%Y%m%dT%H%M%SZ)"
fi

cleanup()
{
    rm -rf -- "$staging"
}

trap cleanup EXIT HUP INT TERM
install -d -o root -g codeforge -m 0750 "$staging"
git -C "$source_dir" archive "$commit" | tar -x -C "$staging"

cd "$staging"
composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader
PATH="/opt/codeforge/node/bin:$PATH" npm ci --no-audit --no-fund

ui_theme=$(sed -n 's/^VITE_UI_THEME=//p' /etc/codeforge/codeforge.env | tail -n 1)
case "$ui_theme" in
    atlantis|laravel) ;;
    *)
        echo "VITE_UI_THEME must be either atlantis or laravel." >&2
        exit 1
        ;;
esac

VITE_UI_THEME="$ui_theme" VITE_APP_NAME=CodeForge PATH="/opt/codeforge/node/bin:$PATH" npm run build
rm -rf -- node_modules

ln -s /etc/codeforge/codeforge.env "$staging/.env"
rm -rf -- "$staging/storage" "$staging/bootstrap/cache"
ln -s /var/lib/codeforge/storage "$staging/storage"
ln -s /var/lib/codeforge/bootstrap-cache "$staging/bootstrap/cache"
chown -R root:codeforge "$staging"
find "$staging" -type d -exec chmod 0750 {} +
find "$staging" -type f -exec chmod 0640 {} +
chmod 0750 "$staging/artisan"
mv "$staging" "$release"
trap - EXIT HUP INT TERM

previous=$(readlink -f /var/www/codeforge/current 2>/dev/null || true)
ln -sfn "$release" /var/www/codeforge/current.next
mv -Tf /var/www/codeforge/current.next /var/www/codeforge/current

runuser -u codeforge-web -- /usr/bin/php "$release/artisan" migrate --force
runuser -u codeforge-web -- /usr/bin/php "$release/artisan" optimize
systemctl restart php8.4-fpm codeforge-queue codeforge-scheduler codeforge-hook-ingest

if ! curl --fail --silent --show-error http://127.0.0.1:8020/up >/dev/null; then
    if [ -n "$previous" ] && [ -d "$previous" ]; then
        ln -sfn "$previous" /var/www/codeforge/current.rollback
        mv -Tf /var/www/codeforge/current.rollback /var/www/codeforge/current
        systemctl restart php8.4-fpm codeforge-queue codeforge-scheduler codeforge-hook-ingest
    fi
    echo "Smoke test failed; release symlink rolled back." >&2
    exit 1
fi

echo "Deployed CodeForge commit $commit"
