#!/bin/bash

set -e

echo "Starting Group Tour Manager..."

# Create .env file from environment variables if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << ENVFILE
APP_NAME="${APP_NAME:-Group Tour Manager}"
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-laravel}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
ENVFILE
fi

# Clear any cached config
php artisan config:clear || true

# Start the server
echo "Starting web server on 0.0.0.0:8080..."
exec php artisan serve --host=0.0.0.0 --port=8080 --no-reload
