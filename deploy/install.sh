#!/bin/sh
set -eu

if [ "$(id -u)" -ne 0 ]; then
    echo "Run this installer as root." >&2
    exit 1
fi

project_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
test -f "$project_root/artisan"
test -f "$project_root/deploy/apache/codeforge.conf"

if [ ! -x /opt/codeforge/node/bin/node ] || [ ! -x /opt/codeforge/node/bin/npm ]; then
    source_node=$(find /home/robison/.nvm/versions/node -mindepth 3 -maxdepth 3 -type f -path '*/bin/node' -perm -u+x 2>/dev/null | sort -V | tail -n 1)
    test -n "$source_node"
    source_node_root=$(CDPATH= cd -- "$(dirname -- "$source_node")/.." && pwd)
    test -f "$source_node_root/lib/node_modules/npm/bin/npm-cli.js"

    install -d -o root -g root -m 0755 /opt/codeforge/node/bin /opt/codeforge/node/lib/node_modules
    install -o root -g root -m 0755 "$source_node" /opt/codeforge/node/bin/node
    cp -a "$source_node_root/lib/node_modules/npm" /opt/codeforge/node/lib/node_modules/npm
    chown -R root:root /opt/codeforge/node
    ln -sfn ../lib/node_modules/npm/bin/npm-cli.js /opt/codeforge/node/bin/npm
    ln -sfn ../lib/node_modules/npm/bin/npx-cli.js /opt/codeforge/node/bin/npx
fi

getent group codeforge >/dev/null || groupadd --system codeforge
usermod -a -G codeforge www-data
id codeforge-web >/dev/null 2>&1 || useradd --system --gid codeforge --home-dir /var/lib/codeforge --shell /usr/sbin/nologin codeforge-web
id git >/dev/null 2>&1 || useradd --system --gid codeforge --home-dir /var/lib/codeforge-git --create-home --shell /bin/sh git

install -d -o root -g codeforge -m 0750 /etc/codeforge /etc/codeforge/tls
install -d -o root -g codeforge -m 0750 /var/www/codeforge/releases
install -d -o codeforge-web -g codeforge -m 0750 /var/lib/codeforge/storage /var/lib/codeforge/bootstrap-cache
install -d -o codeforge-web -g codeforge -m 0750 \
    /var/lib/codeforge/storage/app \
    /var/lib/codeforge/storage/app/private \
    /var/lib/codeforge/storage/app/public \
    /var/lib/codeforge/storage/framework \
    /var/lib/codeforge/storage/framework/cache \
    /var/lib/codeforge/storage/framework/cache/data \
    /var/lib/codeforge/storage/framework/sessions \
    /var/lib/codeforge/storage/framework/testing \
    /var/lib/codeforge/storage/framework/views \
    /var/lib/codeforge/storage/logs
install -d -o git -g codeforge -m 2770 /srv/codeforge/repositories /srv/codeforge/trash
install -d -o codeforge-web -g codeforge -m 0750 /var/log/codeforge

if [ ! -f /etc/codeforge/codeforge.env ]; then
    install -o root -g codeforge -m 0640 "$project_root/deploy/codeforge.env.example" /etc/codeforge/codeforge.env
    echo "Populate /etc/codeforge/codeforge.env before deploying." >&2
fi

if [ ! -f /etc/codeforge/tls/codeforge.key ]; then
    openssl req -x509 -newkey rsa:3072 -sha256 -nodes -days 825 \
        -subj "/CN=codeforge.local" \
        -addext "subjectAltName=DNS:codeforge.local" \
        -keyout /etc/codeforge/tls/codeforge.key \
        -out /etc/codeforge/tls/codeforge.crt
    chmod 0640 /etc/codeforge/tls/codeforge.key
    chown root:codeforge /etc/codeforge/tls/codeforge.key
fi

install -o root -g root -m 0755 "$project_root/deploy/bin/codeforge-authorized-key" /usr/local/bin/codeforge-authorized-key
install -o root -g root -m 0755 "$project_root/deploy/bin/codeforge-git-shell" /usr/local/bin/codeforge-git-shell
install -o root -g root -m 0755 "$project_root/deploy/bin/codeforge-post-receive" /usr/local/bin/codeforge-post-receive
install -o root -g root -m 0750 "$project_root/deploy/backup.sh" /usr/local/sbin/codeforge-backup
install -o root -g root -m 0644 "$project_root/deploy/ssh/99-codeforge.conf" /etc/ssh/sshd_config.d/99-codeforge.conf
install -o root -g root -m 0644 "$project_root/deploy/apache/codeforge.conf" /etc/apache2/sites-available/codeforge.conf
install -o root -g root -m 0644 "$project_root/deploy/php-fpm/codeforge.conf" /etc/php/8.4/fpm/pool.d/codeforge.conf
install -o root -g root -m 0644 "$project_root/deploy/systemd/codeforge-queue.service" /etc/systemd/system/codeforge-queue.service
install -o root -g root -m 0644 "$project_root/deploy/systemd/codeforge-scheduler.service" /etc/systemd/system/codeforge-scheduler.service
install -o root -g root -m 0644 "$project_root/deploy/systemd/codeforge-hook-ingest.service" /etc/systemd/system/codeforge-hook-ingest.service
install -o root -g root -m 0644 "$project_root/deploy/systemd/codeforge-backup.service" /etc/systemd/system/codeforge-backup.service
install -o root -g root -m 0644 "$project_root/deploy/systemd/codeforge-backup.timer" /etc/systemd/system/codeforge-backup.timer

packages=
php -m | grep -qi '^redis$' || packages="$packages php8.4-redis"
command -v avahi-daemon >/dev/null 2>&1 || packages="$packages avahi-daemon"
if [ -n "$packages" ]; then
    apt-get update
    DEBIAN_FRONTEND=noninteractive apt-get install -y $packages
fi
sed -i 's/^#host-name=.*/host-name=codeforge/' /etc/avahi/avahi-daemon.conf
grep -q '^host-name=codeforge$' /etc/avahi/avahi-daemon.conf
systemctl enable --now avahi-daemon

a2enmod ssl headers rewrite proxy_fcgi
a2ensite codeforge
sshd -t
apache2ctl configtest
php-fpm8.4 -t
systemctl daemon-reload
systemctl restart php8.4-fpm
systemctl reload ssh
systemctl reload apache2
systemctl enable codeforge-queue codeforge-scheduler codeforge-hook-ingest
systemctl enable --now codeforge-backup.timer

echo "CodeForge host prerequisites installed."
