#!/usr/bin/env bash

set -e

cd /var/www/html

DB_HOST="${DB_HOST:-tennis-postgres}"
DB_PORT="${DB_PORT:-5432}"

###############################################################################
# 1. Wait for the database to accept connections.
###############################################################################
echo "⏳ Waiting for database at ${DB_HOST}:${DB_PORT}..."
until (echo > "/dev/tcp/${DB_HOST}/${DB_PORT}") 2>/dev/null; do
    sleep 2
done
echo "✅ Database is up."

###############################################################################
# 2. Ensure dependencies + assets exist (first boot under a bind mount won't
#    carry the artifacts baked into the image).
###############################################################################
if [ ! -d vendor ]; then
    echo "📦 Installing composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

if ! grep -qE '^APP_KEY=.+' .env 2>/dev/null; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force --no-interaction
fi

if [ ! -d public/build ]; then
    echo "🛠  Building front-end assets..."
    npm ci
    npm run build
fi

###############################################################################
# 3. Database migrations (and optional demo seed).
###############################################################################
echo "🗄  Running migrations..."
php artisan migrate --force --no-interaction

if [ "${SEED_ON_START:-false}" = "true" ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed --force --no-interaction
fi

###############################################################################
# 4. Permissions + cache hygiene.
###############################################################################
# storage/ and bootstrap/cache/ must be writable by the web user (www-data).
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# Clear stale caches so .env/config edits take effect on restart.
php artisan optimize:clear || true

###############################################################################
# 5. mod_php requires the prefork MPM.
###############################################################################
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

###############################################################################
# 6. Run Apache + the Laravel scheduler side-by-side under supervisord.
###############################################################################
echo "🚀 Starting supervisor (apache + scheduler)..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
