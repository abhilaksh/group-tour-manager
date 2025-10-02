#!/bin/bash

set -e

echo "Starting Group Tour Manager..."

# Wait for database to be ready
echo "Waiting for database..."
sleep 5

# Run migrations
echo "Running migrations..."
php artisan migrate --force || echo "Migration failed, continuing..."

# Start the server
echo "Starting web server..."
php artisan serve --host=0.0.0.0 --port=8080
