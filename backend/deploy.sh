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

# Identify the correct pool directory
POOL_DIR=""
if [ -d "/usr/local/etc/php-fpm.d" ]; then
    POOL_DIR="/usr/local/etc/php-fpm.d"
elif [ -d "/etc/php/8.4/fpm/pool.d" ]; then
    POOL_DIR="/etc/php/8.4/fpm/pool.d"
elif [ -d "/etc/php/fpm/pool.d" ]; then
    POOL_DIR="/etc/php/fpm/pool.d"
fi

if [ -n "$POOL_DIR" ]; then
    echo "✅ Found FPM Pool Directory: $POOL_DIR"
    echo "Writing force-user config..."
    # Create valid config file to force user/group
    printf "[www]\nuser = www-data\ngroup = www-data\n" > "$POOL_DIR/z-force-user.conf"
else
    echo "⚠️ CRITICAL: Could not find PHP-FPM pool directory. FPM might fail."
    # Fallback search as a last resort
    find / -name "www.conf" 2>/dev/null
fi

# 4. Start the main process (PHP-FPM + Nginx)
echo "✅ Deployment Tasks Complete. Starting Server..."
exec /init "$@"
