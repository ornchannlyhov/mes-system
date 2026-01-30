#!/bin/sh

# Fail on any error
set -e

echo "🚀 Deploy Script Started"

# 1. Run Migrations
echo "📦 Running Migrations..."
php artisan migrate --force

# 2. Clear/Cache Configs
echo "🧹 Optimizing Cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Start the main process (PHP-FPM + Nginx)
echo "✅ Deployment Tasks Complete. Starting Server..."
exec /init "$@"
