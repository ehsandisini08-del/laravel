#!/bin/bash

# Billnet Deployment Script
# Usage: ./deploy.sh

set -e

echo "=========================================="
echo "🚀 Billnet Deployment Script"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as www-data
if [ "$USER" != "www-data" ]; then
    echo -e "${YELLOW}Warning: This script should be run as www-data user${NC}"
    echo "Usage: sudo -u www-data ./deploy.sh"
    exit 1
fi

# Project directory
PROJECT_DIR="/var/www/billnet"

# Change to project directory
cd $PROJECT_DIR

echo "📂 Project Directory: $PROJECT_DIR"
echo ""

# Step 1: Git Pull
echo -e "${GREEN}[1/10]${NC} Pulling latest code from GitHub..."
git fetch origin main
git reset --hard origin/main
echo "✓ Git pull completed"
echo ""

# Step 2: Fix Permissions (if needed)
echo -e "${GREEN}[2/10]${NC} Fixing vendor permissions..."
if [ -d "vendor" ]; then
    chmod -R 775 vendor 2>/dev/null || echo "Permission fix attempted"
fi
echo "✓ Permissions fixed"
echo ""

# Step 3: Composer Install
echo -e "${GREEN}[3/10]${NC} Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "✓ Composer install completed"
echo ""

# Step 4: Database Migration
echo -e "${GREEN}[4/10]${NC} Running database migrations..."
php artisan migrate --force
echo "✓ Migrations completed"
echo ""

# Step 5: Storage Link
echo -e "${GREEN}[5/10]${NC} Creating storage link..."
php artisan storage:link
echo "✓ Storage link created"
echo ""

# Step 6: Clear Cache
echo -e "${GREEN}[6/10]${NC} Clearing cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "✓ Cache cleared"
echo ""

# Step 7: Optimize
echo -e "${GREEN}[7/10]${NC} Optimizing application..."
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✓ Optimization completed"
echo ""

# Step 8: NPM Build (optional)
if [ "$1" != "--no-build" ]; then
    echo -e "${GREEN}[8/10]${NC} Building frontend assets..."
    if command -v npm &> /dev/null; then
        npm install --include=dev --no-audit --no-fund
        npm run build
        echo "✓ Frontend build completed"
    else
        echo -e "${YELLOW}⚠ NPM not found, skipping frontend build${NC}"
    fi
else
    echo -e "${YELLOW}[8/10] Skipping frontend build (--no-build flag)${NC}"
fi
echo ""

# Step 9: Queue Restart
echo -e "${GREEN}[9/10]${NC} Restarting queue workers..."
php artisan queue:restart
echo "✓ Queue restarted"
echo ""

# Step 10: Services Restart
echo -e "${GREEN}[10/10]${NC} Restarting services..."
sudo systemctl restart php8.4-fpm 2>/dev/null || echo "⚠ Could not restart PHP-FPM (may require sudo)"
sudo supervisorctl restart billnet-queue:* 2>/dev/null || echo "⚠ Could not restart supervisor (may require sudo)"
echo "✓ Services restart attempted"
echo ""

echo "=========================================="
echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Check application: https://yourdomain.com"
echo "  2. Monitor logs: tail -f storage/logs/laravel.log"
echo "  3. Check queue: sudo supervisorctl status billnet-queue:*"
echo ""
