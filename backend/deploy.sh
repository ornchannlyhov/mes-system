#!/bin/sh

# Fail on any error
set -e

echo "🚀 Deploy Script Started"

# 1. Run Migrations
echo "📦 Running Migrations..."
php artisan migrate --force
rm -rf public/storage
php artisan storage:link
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache public/storage



# 2. Clear/Cache Configs
echo "🧹 Optimizing Cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Fix PHP-FPM User (Runtime Patch)
echo "🔧 Patching PHP-FPM Configuration..."
# Find www.conf wherever it is (silencing permission errors)
FPM_CONF=$(find /etc /usr -name www.conf 2>/dev/null | head -n 1)

if [ -n "$FPM_CONF" ]; then
    echo "Found config at: $FPM_CONF"
    # Append user/group if not present/active
    echo "user = www-data" >> "$FPM_CONF"
    echo "group = www-data" >> "$FPM_CONF"
else
    echo "⚠️ Warning: www.conf not found. PHP-FPM might fail."
fi

# 4. Start the main process (PHP-FPM + Nginx)
echo "✅ Deployment Tasks Complete. Starting Server..."
exec /init "$@"
