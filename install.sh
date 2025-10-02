#!/bin/bash
set -e

echo "🎬 Starting fresh installation of Group Tour Manager..."
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from example..."
    cp .env.example .env
    echo ""
    echo "⚠️  IMPORTANT: Please configure your .env file with:"
    echo "   - Database credentials (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)"
    echo "   - APP_URL (your domain)"
    echo "   - AWS credentials (if using S3/SES)"
    echo "   - Any other environment-specific settings"
    echo ""
    read -p "Press Enter once you've configured .env, or Ctrl+C to exit..."
fi

# Install backend dependencies
echo "📦 Installing backend dependencies..."
composer install --no-interaction

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate --force

# Create storage symlink
echo "🔗 Creating storage symlink..."
php artisan storage:link

# Run database migrations
echo "🗄️ Setting up database..."
read -p "Run migrations? This will create all database tables. (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]
then
    php artisan migrate --force

    read -p "Run seeders? This will populate initial data. (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]
    then
        php artisan db:seed
    fi
fi

# Install frontend dependencies and build
echo "🎨 Setting up frontend..."
cd client
npm install
npm run build
cd ..

# Optimize Laravel
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set correct permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R grouptourmanager:grouptourmanager storage bootstrap/cache

echo ""
echo "✅ Installation complete!"
echo ""
echo "📋 Next steps:"
echo "   1. Verify your .env configuration"
echo "   2. Set up your web server to point to the 'public' directory"
echo "   3. Configure your GitHub webhook in RunCloud"
echo "   4. Set up supervisor for queue workers (if needed)"
echo ""
