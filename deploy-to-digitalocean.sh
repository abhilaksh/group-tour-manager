#!/bin/bash

# Group Tour Manager - DigitalOcean Deployment Script
# Run this on a fresh Ubuntu 22.04 droplet

set -e

echo "🚀 Starting Group Tour Manager Deployment..."

# Update system
echo "📦 Updating system packages..."
sudo apt-get update
sudo apt-get upgrade -y

# Install required packages
echo "📦 Installing required packages..."
sudo apt-get install -y \
    nginx \
    mysql-server \
    php8.3-fpm \
    php8.3-mysql \
    php8.3-xml \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-tokenizer \
    php8.3-json \
    php8.3-cli \
    git \
    curl \
    unzip

# Install Composer
echo "📦 Installing Composer..."
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# Install Node.js 20
echo "📦 Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Setup MySQL
echo "🗄️ Setting up MySQL..."
sudo mysql <<EOF
CREATE DATABASE IF NOT EXISTS group_tour_manager;
CREATE USER IF NOT EXISTS 'gtm_user'@'localhost' IDENTIFIED BY 'gtm_password_$(openssl rand -hex 8)';
GRANT ALL PRIVILEGES ON group_tour_manager.* TO 'gtm_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Get database password
DB_PASSWORD=$(sudo mysql -e "SELECT authentication_string FROM mysql.user WHERE user='gtm_user' AND host='localhost';" | tail -n 1)

# Clone repository
echo "📥 Cloning repository..."
cd /var/www
sudo git clone https://github.com/abhilaksh/group-tour-manager.git
sudo chown -R www-data:www-data group-tour-manager
cd group-tour-manager

# Setup environment file
echo "⚙️ Creating environment file..."
cat > .env <<EOF
APP_NAME="Group Tour Manager"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://$(curl -s ifconfig.me)

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=group_tour_manager
DB_USERNAME=gtm_user
DB_PASSWORD=$DB_PASSWORD

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
EOF

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

# Generate app key
echo "🔑 Generating application key..."
sudo -u www-data php artisan key:generate --force

# Create storage link
sudo -u www-data php artisan storage:link

# Run migrations
echo "🗄️ Running database migrations..."
sudo -u www-data php artisan migrate --force --seed

# Optimize Laravel
echo "⚡ Optimizing application..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan optimize

# Set permissions
echo "🔒 Setting permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache

# Configure Nginx
echo "🌐 Configuring Nginx..."
sudo tee /etc/nginx/sites-available/group-tour-manager > /dev/null <<'NGINX'
server {
    listen 80;
    server_name _;
    root /var/www/group-tour-manager/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

sudo ln -sf /etc/nginx/sites-available/group-tour-manager /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx

# Create installation marker
echo "$(date)" > .installed

echo ""
echo "✅ Deployment Complete!"
echo ""
echo "🌐 Your application is available at: http://$(curl -s ifconfig.me)"
echo ""
echo "📝 Database Credentials:"
echo "   Database: group_tour_manager"
echo "   Username: gtm_user"
echo "   Password: $DB_PASSWORD"
echo ""
echo "⚠️  IMPORTANT: Change the database password and secure your server!"
echo ""
