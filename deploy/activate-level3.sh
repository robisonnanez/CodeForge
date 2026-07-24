#!/bin/sh
set -eu

if [ "$(id -u)" -ne 0 ]; then
    echo "Run this activation as root." >&2
    exit 1
fi

source_dir=${1:-}
commit=${2:-}

test -n "$source_dir"
test -n "$commit"
source_dir=$(readlink -f "$source_dir")
test -f "$source_dir/artisan"
git -C "$source_dir" cat-file -e "$commit^{commit}"

case "$source_dir" in
    /home/robison/projects/CodeForge) ;;
    *)
        echo "Unexpected source directory: $source_dir" >&2
        exit 1
        ;;
esac

env_existed=0
test -f /etc/codeforge/codeforge.env && env_existed=1

"$source_dir/deploy/install.sh"

if [ "$env_existed" -eq 0 ]; then
    test -f "$source_dir/.env"
    install -o root -g codeforge -m 0640 "$source_dir/.env" /etc/codeforge/codeforge.env
fi

set_env()
{
    key=$1
    value=$2
    target=/etc/codeforge/codeforge.env
    temporary=$(mktemp /etc/codeforge/codeforge.env.XXXXXX)

    awk -v key="$key" -v value="$value" '
        BEGIN { replaced = 0 }
        index($0, key "=") == 1 {
            if (! replaced) {
                print key "=" value
                replaced = 1
            }
            next
        }
        { print }
        END {
            if (! replaced) {
                print key "=" value
            }
        }
    ' "$target" > "$temporary"

    chown root:codeforge "$temporary"
    chmod 0640 "$temporary"
    mv -f "$temporary" "$target"
}

set_env APP_NAME CodeForge
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL http://localhost:8020
set_env LOG_LEVEL warning
set_env SESSION_DRIVER redis
set_env SESSION_ENCRYPT true
set_env SESSION_SECURE_COOKIE false
set_env SESSION_HTTP_ONLY true
set_env SESSION_SAME_SITE lax
set_env SESSION_DOMAIN null
set_env CACHE_STORE redis
set_env QUEUE_CONNECTION redis
set_env REDIS_HOST 127.0.0.1
set_env REDIS_PORT 6379
set_env CODEFORGE_REGISTRATION_ENABLED false
set_env CODEFORGE_DEMO_FEATURES_ENABLED false
set_env CODEFORGE_REPOSITORIES_ROOT /srv/codeforge/repositories
set_env CODEFORGE_REPOSITORIES_TRASH_ROOT /srv/codeforge/trash
set_env CODEFORGE_SSH_HOST localhost
set_env CODEFORGE_SSH_PORT 2230
set_env CODEFORGE_SSH_USER git
set_env CODEFORGE_HTTP_CLONE_ENABLED true
set_env CODEFORGE_MAINTENANCE_LOCK_PATH /run/codeforge/maintenance.lock
set_env CODEFORGE_HOOK_SOCKET_PATH /run/codeforge/hook-ingest.sock
set_env CODEFORGE_POST_RECEIVE_HOOK /usr/local/bin/codeforge-post-receive

legacy_root="$source_dir/storage/app/codeforge/repositories"
if [ -d "$legacy_root" ]; then
    find "$legacy_root" -mindepth 1 -maxdepth 1 -type d -name '*.git' -print | while IFS= read -r repository; do
        name=$(basename "$repository")
        destination="/srv/codeforge/repositories/$name"

        case "$name" in
            ????????-????-????-????-????????????.git) ;;
            *)
                echo "Refusing unexpected repository directory: $repository" >&2
                exit 1
                ;;
        esac

        if [ ! -e "$destination" ]; then
            cp -a "$repository" "$destination"
        fi

        git --git-dir="$destination" fsck --no-dangling
        chown -R git:codeforge "$destination"
        chmod -R g+rwX,o-rwx "$destination"
        find "$destination" -type d -exec chmod g+s {} +
    done
fi

"$source_dir/deploy/release.sh" "$source_dir" "$commit"

systemctl is-active --quiet apache2
systemctl is-active --quiet php8.4-fpm
systemctl is-active --quiet postgresql
systemctl is-active --quiet redis-server
systemctl is-active --quiet codeforge-queue
systemctl is-active --quiet codeforge-scheduler
systemctl is-active --quiet codeforge-hook-ingest

curl --fail --silent --show-error \
    --cacert /etc/codeforge/tls/codeforge.crt \
    --resolve codeforge.local:443:127.0.0.1 \
    https://codeforge.local/healthz >/dev/null

echo "CodeForge Level 3 activation completed for commit $commit"
