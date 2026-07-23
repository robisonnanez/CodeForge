#!/bin/sh
set -eu

if [ "$(id -u)" -ne 0 ]; then
    echo "Run this backup command as root." >&2
    exit 1
fi

backup_root=/var/backups/codeforge
public_key=/etc/codeforge/backup-public.pem
lock=/var/lib/codeforge/maintenance.lock
stamp=$(date -u +%Y%m%dT%H%M%SZ)
target="$backup_root/$stamp"

test -s "$public_key"
install -d -o root -g codeforge -m 0750 "$backup_root"
test ! -e "$target"
install -d -o root -g codeforge -m 0700 "$target"

cleanup()
{
    rm -f -- "$lock"
    runuser -u codeforge-web -- /usr/bin/php /var/www/codeforge/current/artisan up --no-ansi >/dev/null 2>&1 || true
    systemctl start codeforge-queue codeforge-scheduler >/dev/null 2>&1 || true
}
trap cleanup EXIT INT TERM

install -o codeforge-web -g codeforge -m 0640 /dev/null "$lock"
runuser -u codeforge-web -- /usr/bin/php /var/www/codeforge/current/artisan down --retry=120 --no-ansi
systemctl stop codeforge-queue codeforge-scheduler

set -a
. /etc/codeforge/codeforge.env
set +a

PGPASSWORD="$DB_PASSWORD" pg_dump \
    --format=custom \
    --no-owner \
    --no-privileges \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --username="$DB_USERNAME" \
    --dbname="$DB_DATABASE" \
    --file="$target/postgresql.dump"
tar -C /srv/codeforge -czf "$target/repositories.tar.gz" repositories
pg_restore --list "$target/postgresql.dump" >/dev/null
tar -tzf "$target/repositories.tar.gz" >/dev/null

for artifact in postgresql.dump repositories.tar.gz; do
    openssl rand -out "$target/$artifact.key" 32
    openssl enc -aes-256-cbc -pbkdf2 -salt \
        -in "$target/$artifact" \
        -out "$target/$artifact.enc" \
        -pass "file:$target/$artifact.key"
    openssl pkeyutl -encrypt -pubin -inkey "$public_key" \
        -in "$target/$artifact.key" \
        -out "$target/$artifact.key.enc"
    rm -f -- "$target/$artifact" "$target/$artifact.key"
done

sha256sum "$target"/*.enc > "$target/SHA256SUMS"
chmod 0600 "$target"/*

find "$backup_root" -mindepth 1 -maxdepth 1 -type d -mtime +30 -exec rm -rf -- {} +
echo "Encrypted CodeForge backup created at $target"
