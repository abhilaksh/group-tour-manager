# RunCloud Deployment Script

Copy the script below exactly as-is into your RunCloud deployment script field:

```bash
set -e

echo "🚀 Starting deployment..."

# Git merge (required by RunCloud)
git merge origin/master

# Put application in maintenance mode
echo "🔧 Enabling maintenance mode..."
{lsphp83} artisan down || true

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
{lsphp83} artisan migrate --force

# Clear and optimize caches
echo "🧹 Optimizing application..."
{lsphp83} artisan config:cache
{lsphp83} artisan route:cache
{lsphp83} artisan view:cache
{lsphp83} artisan optimize

# Restart queue workers
echo "🔄 Restarting queue workers..."
{lsphp83} artisan queue:restart

# Set correct permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage bootstrap/cache

# Bring application back online
echo "✅ Disabling maintenance mode..."
{lsphp83} artisan up

echo "🎉 Deployment complete!"
```

## Instructions

1. Copy everything inside the code block above
2. Paste into RunCloud → Your App → Git → Deployment Script
3. Check "Enable deployment script" ✓
4. Click Save
