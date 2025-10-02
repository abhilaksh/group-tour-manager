# Group Tour Manager - Installation Guide

## Quick Deploy to DigitalOcean Droplet

### Step 1: Create a Droplet
1. Go to https://cloud.digitalocean.com/droplets/new
2. Choose **Ubuntu 22.04 LTS**
3. Select **Basic** plan ($6/month)
4. Choose your preferred region
5. Add SSH key or use password
6. Click **Create Droplet**

### Step 2: SSH into your droplet
```bash
ssh root@YOUR_DROPLET_IP
```

### Step 3: Copy and paste this entire script:

```bash
#!/bin/bash
set -e
echo "🚀 Starting Group Tour Manager Deployment..."

# Update system
echo "📦 Updating system packages..."
export DEBIAN_FRONTEND=noninteractive
sudo apt-get update -qq
sudo apt-get upgrade -y -qq

# Install PHP repository
echo "📦 Adding PHP repository..."
sudo apt-get install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -qq

# Install required packages
echo "📦 Installing required packages..."
sudo apt-get install -y -qq \
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
    php8.3-cli \
    git \
    curl \
    unzip

# Install Composer
echo "📦 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20
echo "📦 Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y -qq nodejs

# Generate random password
DB_PASSWORD=$(openssl rand -base64 16)

# Setup MySQL
echo "🗄️ Setting up MySQL..."
sudo mysql <<EOF
CREATE DATABASE IF NOT EXISTS group_tour_manager;
CREATE USER IF NOT EXISTS 'gtm_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON group_tour_manager.* TO 'gtm_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Clone repository
echo "📥 Cloning repository..."
sudo rm -rf /var/www/group-tour-manager
cd /var/www
sudo git clone https://github.com/abhilaksh/group-tour-manager.git
cd group-tour-manager

# Setup environment file
echo "⚙️ Creating environment file..."
sudo tee .env > /dev/null <<EOF
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
echo "📦 Installing backend dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction -q

# Generate app key
echo "🔑 Generating application key..."
php artisan key:generate --force

# Create storage link
php artisan storage:link

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Optimize Laravel
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "🔒 Setting permissions..."
sudo chown -R www-data:www-data /var/www/group-tour-manager
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
        fastcgi_hide_header X-Powered-By;
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
sudo systemctl restart php8.3-fpm

# Enable services on boot
sudo systemctl enable nginx
sudo systemctl enable php8.3-fpm
sudo systemctl enable mysql

# Create installation marker
echo "$(date)" > .installed

# Get server IP
SERVER_IP=$(curl -s ifconfig.me)

echo ""
echo "════════════════════════════════════════"
echo "✅ DEPLOYMENT COMPLETE!"
echo "════════════════════════════════════════"
echo ""
echo "🌐 Your application is live at:"
echo "   http://$SERVER_IP"
echo ""
echo "📝 Database Credentials:"
echo "   Database: group_tour_manager"
echo "   Username: gtm_user"
echo "   Password: $DB_PASSWORD"
echo ""
echo "⚠️  NEXT STEPS:"
echo "   1. Point your domain to: $SERVER_IP"
echo "   2. Install SSL certificate (certbot)"
echo "   3. Change database password for production"
echo ""
echo "════════════════════════════════════════"
```

That's it! The script will run and give you a live URL in about 5 minutes.
