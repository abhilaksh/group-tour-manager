#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Navigate to app directory
cd /home/grouptourmanager/webapps/app-hegmann

# Pull latest changes from master branch
echo "📥 Pulling latest code from GitHub..."
git pull origin master

# Put application in maintenance mode
echo "🔧 Enabling maintenance mode..."
php artisan down || true

# Backend deployment
echo "📦 Installing backend dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Frontend deployment
echo "🎨 Building frontend..."
cd client
npm ci --production=false
npm run build
cd ..

# Database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Clear and optimize caches
echo "🧹 Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Restart queue workers
echo "🔄 Restarting queue workers..."
php artisan queue:restart

# Set correct permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R grouptourmanager:grouptourmanager storage bootstrap/cache

# Bring application back online
echo "✅ Disabling maintenance mode..."
php artisan up

echo "🎉 Deployment complete!"
