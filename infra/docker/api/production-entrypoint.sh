#!/bin/sh
# Production container start for the Laravel API.
#
# This entrypoint prepares runtime directories and warms the caches that depend
# on environment values. It deliberately never runs migrations, seeders, the
# guarded initial import or the administrative bootstrap: those stay explicit
# operator actions (see docs/DEPLOYMENT.md).
set -eu

fail() {
    echo "portfolio-api: $1" >&2
    exit 1
}

[ -n "${APP_KEY:-}" ] || fail "APP_KEY is not set; refusing to start."

# A production container must never render debug output to a visitor.
case "${APP_ENV:-production}:${APP_DEBUG:-false}" in
    production:true | production:1 | production:on | production:On | production:TRUE | production:True)
        fail "APP_DEBUG must not be enabled when APP_ENV=production; refusing to start."
        ;;
esac

# storage/app/private and storage/app/public are persistent volumes; the rest
# is regenerable container-local state. Both need to stay writable by the
# Apache/PHP process across restores and recreates.
mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Configuration and views are cached here rather than at build time because
# they depend on runtime environment values. Routes are deliberately not
# cached: routes/web.php and routes/api.php each register a closure action,
# which Laravel cannot serialize.
php artisan config:clear
php artisan config:cache
php artisan view:cache

chown -R www-data:www-data bootstrap/cache

exec "$@"
