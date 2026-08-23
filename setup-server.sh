#!/bin/bash

# Billnet Initial Server Setup Script
# Usage: sudo ./setup-server.sh

set -e

echo "=========================================="
echo "🔧 Billnet Server Setup Script"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root (use sudo)"
    exit 1
fi

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
DOMAIN="yourdomain.com"
EMAIL="admin@yourdomain.com"
PROJECT_DIR="/var/www/billnet"
PHP_VERSION="8.4"

echo "Configuration:"
echo "  Domain: $DOMAIN"
echo "  Email: $EMAIL"
echo "  PHP Version: $PHP_VERSION"
echo "  Project Directory: $PROJECT_DIR"
echo ""
read -p "Press Enter to continue or Ctrl+C to abort..."
echo ""

# Step 1: Update System
echo -e "${GREEN}[1/12]${NC} Updating system packages..."
apt update && apt upgrade -y
echo "✓ System updated"
echo ""

# Step 2: Install PHP
echo -e "${GREEN}[2/12]${NC} Installing PHP $PHP_VERSION..."
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-common \
    php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-curl php${PHP_VERSION}-zip php${PHP_VERSION}-gd \
    php${PHP_VERSION}-sqlite3 php${PHP_VERSION}-mysql php${PHP_VERSION}-intl \
    php${PHP_VERSION}-redis php${PHP_VERSION}-opcache
echo "✓ PHP installed"
echo ""

# Step 3: Install Composer
echo -e "${GREEN}[3/12]${NC} Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi
echo "✓ Composer installed"
composer --version
echo ""

# Step 4: Install Node.js & NPM
echo -e "${GREEN}[4/12]${NC} Installing Node.js..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install -y nodejs
fi
echo "✓ Node.js installed"
node --version
npm --version
echo ""

# Step 5: Install Nginx
echo -e "${GREEN}[5/12]${NC} Installing Nginx..."
apt install -y nginx
systemctl enable nginx
systemctl start nginx
echo "✓ Nginx installed"
echo ""

# Step 6: Install Git
echo -e "${GREEN}[6/12]${NC} Installing Git..."
apt install -y git
echo "✓ Git installed"
git --version
echo ""

# Step 7: Install Supervisor
echo -e "${GREEN}[7/12]${NC} Installing Supervisor..."
apt install -y supervisor
systemctl enable supervisor
systemctl start supervisor
echo "✓ Supervisor installed"
echo ""

# Step 8: Install UFW Firewall
echo -e "${GREEN}[8/12]${NC} Configuring firewall..."
apt install -y ufw
ufw --force enable
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw status
echo "✓ Firewall configured"
echo ""

# Step 9: Clone Repository
echo -e "${GREEN}[9/12]${NC} Cloning repository..."
if [ ! -d "$PROJECT_DIR" ]; then
    read -p "Enter GitHub repository URL: " REPO_URL
    git clone $REPO_URL $PROJECT_DIR
    chown -R www-data:www-data $PROJECT_DIR
    echo "✓ Repository cloned"
else
    echo "⚠ Directory already exists: $PROJECT_DIR"
fi
echo ""

# Step 10: Setup .env
echo -e "${GREEN}[10/12]${NC} Setting up environment file..."
cd $PROJECT_DIR
if [ ! -f ".env" ]; then
    sudo -u www-data cp .env.example .env
    sudo -u www-data php artisan key:generate
    echo "✓ Environment file created"
    echo ""
    echo -e "${YELLOW}Important: Edit .env file with your configuration!${NC}"
    echo "  nano $PROJECT_DIR/.env"
else
    echo "⚠ .env already exists"
fi
echo ""

# Step 11: Install Dependencies
echo -e "${GREEN}[11/12]${NC} Installing application dependencies..."
cd $PROJECT_DIR
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
sudo -u www-data npm install --include=dev
sudo -u www-data npm run build
echo "✓ Dependencies installed"
echo ""

# Step 12: Setup Permissions
echo -e "${GREEN}[12/12]${NC} Setting permissions..."
chown -R www-data:www-data $PROJECT_DIR
find $PROJECT_DIR -type d -exec chmod 755 {} \;
find $PROJECT_DIR -type f -exec chmod 644 {} \;
chmod -R 775 $PROJECT_DIR/storage
chmod -R 775 $PROJECT_DIR/bootstrap/cache
chmod +x $PROJECT_DIR/artisan
chmod +x $PROJECT_DIR/deploy.sh
echo "✓ Permissions set"
echo ""

# Done
echo "=========================================="
echo -e "${GREEN}✅ Server setup completed!${NC}"
echo "=========================================="
echo ""
echo "Next manual steps:"
echo ""
echo "1. Configure database in .env:"
echo "   nano $PROJECT_DIR/.env"
echo ""
echo "2. Run migrations:"
echo "   cd $PROJECT_DIR"
echo "   sudo -u www-data php artisan migrate --force"
echo "   sudo -u www-data php artisan storage:link"
echo ""
echo "3. Create admin user:"
echo "   sudo -u www-data php artisan tinker"
echo "   >>> \$user = App\\Models\\User::create(['name' => 'Admin', 'email' => 'admin@domain.com', 'password' => bcrypt('password'), 'role' => 'developer']);"
echo ""
echo "4. Configure Nginx:"
echo "   nano /etc/nginx/sites-available/billnet"
echo "   (See DEPLOYMENT.md for config)"
echo "   ln -s /etc/nginx/sites-available/billnet /etc/nginx/sites-enabled/"
echo "   nginx -t && systemctl reload nginx"
echo ""
echo "5. Setup SSL Certificate:"
echo "   apt install certbot python3-certbot-nginx"
echo "   certbot --nginx -d $DOMAIN"
echo ""
echo "6. Setup Supervisor for queue:"
echo "   nano /etc/supervisor/conf.d/billnet-queue.conf"
echo "   (See DEPLOYMENT.md for config)"
echo "   supervisorctl reread && supervisorctl update"
echo ""
echo "7. Setup Cron for scheduler:"
echo "   crontab -e -u www-data"
echo "   * * * * * cd $PROJECT_DIR && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "8. Upload Firebase credentials (if using push notifications):"
echo "   scp firebase-credentials.json user@server:$PROJECT_DIR/"
echo "   chown www-data:www-data $PROJECT_DIR/firebase-credentials.json"
echo "   chmod 600 $PROJECT_DIR/firebase-credentials.json"
echo ""
echo "Full deployment guide: $PROJECT_DIR/DEPLOYMENT.md"
echo ""
