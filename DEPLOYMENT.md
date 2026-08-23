# Panduan Deployment ke Server Production

## 1. Requirements Server

### Software yang Diperlukan:
- **PHP 8.4** (atau minimal 8.3)
- **Composer**
- **Node.js & NPM** (untuk build frontend)
- **Git**
- **Web Server:** Nginx atau Apache
- **Database:** SQLite (sudah included) atau MySQL/PostgreSQL
- **Supervisor** (untuk queue worker)
- **SSL Certificate** (Let's Encrypt recommended)

### Extensions PHP Required:
```bash
# Install PHP extensions
sudo apt install -y php8.4-cli php8.4-fpm php8.4-mbstring php8.4-xml \
  php8.4-bcmath php8.4-curl php8.4-zip php8.4-gd php8.4-sqlite3 \
  php8.4-mysql php8.4-intl php8.4-redis
```

---

## 2. Clone Repository

```bash
# Masuk ke directory web server
cd /var/www

# Clone repository dari GitHub
sudo git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git billnet
cd billnet

# Set ownership ke www-data
sudo chown -R www-data:www-data /var/www/billnet
```

---

## 3. Setup Environment

```bash
# Copy .env.example ke .env
sudo -u www-data cp .env.example .env

# Edit .env file
sudo nano .env
```

### **Konfigurasi .env untuk Production:**

```ini
APP_NAME=Billnet
APP_ENV=production
APP_KEY=    # Will be generated
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=sqlite
# DB_DATABASE=/var/www/billnet/database/database.sqlite

# Atau jika pakai MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=billnet
# DB_USERNAME=billnet_user
# DB_PASSWORD=secure_password

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database
CACHE_PREFIX=billnet_

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=yourdomain.com

# Firebase Cloud Messaging (untuk push notifications)
FCM_CREDENTIALS=/var/www/billnet/firebase-credentials.json

# Mail Configuration (untuk production)
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 4. Install Dependencies & Setup

```bash
# Generate APP_KEY
sudo -u www-data php artisan key:generate

# Install Composer dependencies (production)
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

# Create database (jika pakai SQLite)
sudo -u www-data touch database/database.sqlite
sudo -u www-data chmod 664 database/database.sqlite

# Run migrations
sudo -u www-data php artisan migrate --force

# Create storage link
sudo -u www-data php artisan storage:link

# Install NPM dependencies & build assets
sudo -u www-data npm install --include=dev
sudo -u www-data npm run build

# Optimize Laravel
sudo -u www-data php artisan optimize
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

---

## 5. Set File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/billnet

# Set directory permissions
sudo find /var/www/billnet -type d -exec chmod 755 {} \;
sudo find /var/www/billnet -type f -exec chmod 644 {} \;

# Set writable directories
sudo chmod -R 775 /var/www/billnet/storage
sudo chmod -R 775 /var/www/billnet/bootstrap/cache

# Set executable for artisan
sudo chmod +x /var/www/billnet/artisan
```

---

## 6. Nginx Configuration

```bash
# Create Nginx config
sudo nano /etc/nginx/sites-available/billnet
```

### **Nginx Config:**

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/billnet/public;
    index index.php;

    # SSL Configuration (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Max upload size
    client_max_body_size 10M;

    # Logging
    access_log /var/log/nginx/billnet-access.log;
    error_log /var/log/nginx/billnet-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml|webp|woff|woff2|ttf|svg|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/billnet /etc/nginx/sites-enabled/

# Test Nginx config
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## 7. SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Generate SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (sudah otomatis via cron)
sudo certbot renew --dry-run
```

---

## 8. Setup Queue Worker (Supervisor)

```bash
# Install Supervisor
sudo apt install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/billnet-queue.conf
```

### **Supervisor Config:**

```ini
[program:billnet-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/billnet/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/billnet/storage/logs/queue-worker.log
stopwaitsecs=3600
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start billnet-queue:*

# Check status
sudo supervisorctl status billnet-queue:*
```

---

## 9. Setup Cron Job (Scheduler)

```bash
# Edit crontab
sudo crontab -e -u www-data
```

### **Add this line:**

```cron
* * * * * cd /var/www/billnet && php artisan schedule:run >> /dev/null 2>&1
```

---

## 10. Firebase Cloud Messaging Setup (untuk Push Notifications)

```bash
# Upload firebase-credentials.json ke server
# Bisa via SCP atau Git (jangan commit ke public repo!)

# Set permissions
sudo chown www-data:www-data /var/www/billnet/firebase-credentials.json
sudo chmod 600 /var/www/billnet/firebase-credentials.json

# Test FCM
sudo -u www-data php artisan tinker --execute="dd(config('firebase.projects.app.credentials'));"
```

---

## 11. Create Admin User

```bash
# Via tinker
sudo -u www-data php artisan tinker
```

```php
// Di tinker console:
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@yourdomain.com';
$user->password = bcrypt('password123'); // Ganti dengan password kuat!
$user->role = 'developer';
$user->save();
exit;
```

---

## 12. Test Aplikasi

### **Checklist Testing:**

1. ✅ **Akses website:** `https://yourdomain.com`
2. ✅ **Login:** admin@yourdomain.com / password123
3. ✅ **Dashboard:** Cek apakah loading dengan baik
4. ✅ **Database:** Cek customers, packages, dll
5. ✅ **Queue:** `sudo supervisorctl status billnet-queue:*`
6. ✅ **Logs:** `tail -f /var/www/billnet/storage/logs/laravel.log`
7. ✅ **Cron:** Tunggu 1 menit, cek `storage/logs/laravel.log`
8. ✅ **Push Notifications:** Test buat tugas perbaikan
9. ✅ **Update Menu:** Test menu update dari dashboard

---

## 13. Menggunakan Menu Update

Setelah setup awal selesai, untuk update selanjutnya:

### **Dari Local:**

```bash
# Edit code
# Test di local
# Commit & push ke GitHub

git add .
git commit -m "Update fitur XYZ"
git push origin main
```

### **Di Server (via Browser):**

1. Login sebagai Developer
2. Buka: `https://yourdomain.com/update`
3. Klik "Update Sekarang"
4. Pantau progress real-time
5. Done! ✨

### **Di Server (via CLI) - Alternatif:**

```bash
cd /var/www/billnet
sudo -u www-data php artisan app:update
```

---

## 14. Backup Database (Penting!)

```bash
# Setup automatic backup (cron)
sudo nano /etc/cron.daily/billnet-backup
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/billnet"
mkdir -p $BACKUP_DIR

# Backup database (SQLite)
cp /var/www/billnet/database/database.sqlite $BACKUP_DIR/database_$DATE.sqlite

# Backup .env
cp /var/www/billnet/.env $BACKUP_DIR/env_$DATE.txt

# Backup storage (uploads)
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/billnet/storage/app/public

# Keep only last 30 days
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

```bash
# Set executable
sudo chmod +x /etc/cron.daily/billnet-backup
```

---

## 15. Security Checklist

- ✅ **APP_DEBUG=false** di production
- ✅ **Strong APP_KEY** (auto-generated)
- ✅ **SSL Certificate** aktif (HTTPS)
- ✅ **File permissions** correct (www-data:www-data)
- ✅ **Firewall** aktif (UFW): port 22, 80, 443
- ✅ **Disable directory listing** di Nginx/Apache
- ✅ **Hide .env file** (sudah di .gitignore)
- ✅ **Strong database password**
- ✅ **Regular security updates:** `sudo apt update && sudo apt upgrade`
- ✅ **Fail2ban** untuk protect SSH
- ✅ **Rate limiting** di Laravel (sudah ada)
- ✅ **CSRF protection** (default Laravel)
- ✅ **SQL Injection protection** (Eloquent ORM)

---

## 16. Monitoring & Maintenance

### **Log Monitoring:**

```bash
# Laravel log
tail -f /var/www/billnet/storage/logs/laravel.log

# Nginx access log
tail -f /var/log/nginx/billnet-access.log

# Nginx error log
tail -f /var/log/nginx/billnet-error.log

# Queue worker log
tail -f /var/www/billnet/storage/logs/queue-worker.log
```

### **Performance Monitoring:**

```bash
# Check disk space
df -h

# Check memory usage
free -h

# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Check Nginx status
sudo systemctl status nginx

# Check queue workers
sudo supervisorctl status billnet-queue:*
```

### **Regular Maintenance:**

```bash
# Clear logs (jika terlalu besar)
sudo -u www-data php artisan log:clear

# Clear cache
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Optimize
sudo -u www-data php artisan optimize

# Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
sudo supervisorctl restart billnet-queue:*
```

---

## 17. Troubleshooting Common Issues

### **Issue: 500 Internal Server Error**

```bash
# Check logs
tail -100 /var/www/billnet/storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data /var/www/billnet
sudo chmod -R 775 /var/www/billnet/storage
sudo chmod -R 775 /var/www/billnet/bootstrap/cache
```

### **Issue: Queue not working**

```bash
# Check supervisor
sudo supervisorctl status billnet-queue:*

# Restart queue
sudo supervisorctl restart billnet-queue:*

# Check queue table
sudo -u www-data php artisan queue:work --once
```

### **Issue: Update menu error**

```bash
# Check git
cd /var/www/billnet
git status
git remote -v

# Check permissions
sudo chown -R www-data:www-data /var/www/billnet

# Manual update
sudo -u www-data php artisan app:update
```

---

## 18. Rollback (Jika Update Bermasalah)

```bash
# Rollback ke commit sebelumnya
cd /var/www/billnet
sudo -u www-data git log --oneline -5
sudo -u www-data git reset --hard COMMIT_HASH

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Rollback migration (jika ada migration baru)
sudo -u www-data php artisan migrate:rollback

# Clear cache & optimize
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize
```

---

## 🎉 Selesai!

Aplikasi Anda sudah live di production dan siap digunakan!

**Akses:** https://yourdomain.com

**Next Update:** Tinggal commit & push, lalu klik tombol Update di dashboard! 🚀
