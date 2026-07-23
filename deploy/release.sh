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

release="/var/www/codeforge/releases/$commit"
test ! -e "$release"
install -d -o root -g codeforge -m 0750 "$release"
git -C "$source_dir" archive "$commit" | tar -x -C "$release"

cd "$release"
composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader
npm ci --no-audit --no-fund
npm run build
rm -rf -- node_modules

ln -s /etc/codeforge/codeforge.env "$release/.env"
rm -rf -- "$release/storage" "$release/bootstrap/cache"
ln -s /var/lib/codeforge/storage "$release/storage"
ln -s /var/lib/codeforge/bootstrap-cache "$release/bootstrap/cache"
chown -R root:codeforge "$release"
find "$release" -type d -exec chmod 0750 {} +
find "$release" -type f -exec chmod 0640 {} +
chmod 0750 "$release/artisan"

previous=$(readlink -f /var/www/codeforge/current 2>/dev/null || true)
ln -sfn "$release" /var/www/codeforge/current.next
mv -Tf /var/www/codeforge/current.next /var/www/codeforge/current

runuser -u codeforge-web -- /usr/bin/php "$release/artisan" migrate --force
runuser -u codeforge-web -- /usr/bin/php "$release/artisan" optimize
systemctl restart php8.4-fpm codeforge-queue codeforge-scheduler

if ! curl --fail --silent --show-error --cacert /etc/codeforge/tls/codeforge.crt https://codeforge.local/up >/dev/null; then
    if [ -n "$previous" ] && [ -d "$previous" ]; then
        ln -sfn "$previous" /var/www/codeforge/current.rollback
        mv -Tf /var/www/codeforge/current.rollback /var/www/codeforge/current
        systemctl restart php8.4-fpm codeforge-queue codeforge-scheduler
    fi
    echo "Smoke test failed; release symlink rolled back." >&2
    exit 1
fi

echo "Deployed CodeForge commit $commit"
