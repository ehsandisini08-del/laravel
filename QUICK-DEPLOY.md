# 🚀 Quick Deployment Guide

## Untuk Server Baru (First Time Setup)

### 1. Upload Scripts ke Server

```bash
# Dari local, upload scripts ke server
scp setup-server.sh deploy.sh user@your-server-ip:~/
```

### 2. Jalankan Setup Script

```bash
# SSH ke server
ssh user@your-server-ip

# Jalankan setup script
sudo bash setup-server.sh
```

Script ini akan otomatis install:
- ✅ PHP 8.4 + Extensions
- ✅ Composer
- ✅ Node.js & NPM  
- ✅ Nginx
- ✅ Git
- ✅ Supervisor
- ✅ UFW Firewall
- ✅ Clone repository
- ✅ Install dependencies

### 3. Konfigurasi Manual (Setelah Setup Script)

#### A. Edit .env
```bash
cd /var/www/billnet
sudo nano .env
```

Minimal config yang harus diubah:
```ini
APP_URL=https://yourdomain.com
DB_DATABASE=/var/www/billnet/database/database.sqlite
SESSION_DOMAIN=yourdomain.com
```

#### B. Run Migration & Create Admin
```bash
cd /var/www/billnet

# Create database
sudo -u www-data touch database/database.sqlite
sudo -u www-data chmod 664 database/database.sqlite

# Run migration
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan storage:link

# Create admin user
sudo -u www-data php artisan tinker
```

Di tinker console:
```php
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@yourdomain.com',
    'password' => bcrypt('YourStrongPassword123!'),
    'role' => 'developer'
]);
exit;
```

#### C. Setup Nginx
```bash
sudo nano /etc/nginx/sites-available/billnet
```

Paste config dari `DEPLOYMENT.md` section 6, lalu:
```bash
sudo ln -s /etc/nginx/sites-available/billnet /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### D. Setup SSL
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

#### E. Setup Queue Worker
```bash
sudo nano /etc/supervisor/conf.d/billnet-queue.conf
```

Paste config dari `DEPLOYMENT.md` section 8, lalu:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start billnet-queue:*
```

#### F. Setup Cron
```bash
sudo crontab -e -u www-data
```

Add line:
```cron
* * * * * cd /var/www/billnet && php artisan schedule:run >> /dev/null 2>&1
```

#### G. Optimize & Test
```bash
cd /var/www/billnet
sudo -u www-data php artisan optimize
```

Akses: `https://yourdomain.com`

---

## Untuk Update Aplikasi (Setelah Setup)

### Metode 1: Via Dashboard (Recommended)

1. **Commit & Push dari Local:**
   ```bash
   git add .
   git commit -m "Update fitur XYZ"
   git push origin main
   ```

2. **Update via Browser:**
   - Login sebagai Developer
   - Buka: `https://yourdomain.com/update`
   - Klik "Update Sekarang"
   - Pantau progress real-time

### Metode 2: Via Command Line

```bash
# SSH ke server
ssh user@your-server-ip

# Run deploy script
cd /var/www/billnet
sudo -u www-data ./deploy.sh

# Atau tanpa npm build (lebih cepat)
sudo -u www-data ./deploy.sh --no-build
```

### Metode 3: Via Artisan Command

```bash
cd /var/www/billnet
sudo -u www-data php artisan app:update

# Atau skip npm build
sudo -u www-data php artisan app:update --no-build
```

---

## Troubleshooting

### Issue: Permission Denied

```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/billnet
sudo chmod -R 775 /var/www/billnet/storage
sudo chmod -R 775 /var/www/billnet/bootstrap/cache
```

### Issue: 500 Error

```bash
# Check logs
tail -100 /var/www/billnet/storage/logs/laravel.log

# Clear cache
cd /var/www/billnet
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize
```

### Issue: Queue Not Working

```bash
# Check status
sudo supervisorctl status billnet-queue:*

# Restart
sudo supervisorctl restart billnet-queue:*
```

### Issue: Git Pull Failed

```bash
# Check git status
cd /var/www/billnet
sudo -u www-data git status
sudo -u www-data git remote -v

# Force reset
sudo -u www-data git fetch origin main
sudo -u www-data git reset --hard origin/main
```

---

## Monitoring

### Check Logs
```bash
# Application log
tail -f /var/www/billnet/storage/logs/laravel.log

# Nginx access log
tail -f /var/log/nginx/billnet-access.log

# Nginx error log
tail -f /var/log/nginx/billnet-error.log

# Queue worker log
tail -f /var/www/billnet/storage/logs/queue-worker.log
```

### Check Services
```bash
# Nginx
sudo systemctl status nginx

# PHP-FPM
sudo systemctl status php8.4-fpm

# Supervisor
sudo supervisorctl status

# Queue workers
sudo supervisorctl status billnet-queue:*
```

### Performance
```bash
# Disk space
df -h

# Memory usage
free -h

# Process list
htop
```

---

## Backup

### Manual Backup
```bash
# Backup database
cp /var/www/billnet/database/database.sqlite ~/backup-$(date +%Y%m%d).sqlite

# Backup .env
cp /var/www/billnet/.env ~/backup-env-$(date +%Y%m%d).txt

# Backup uploads
tar -czf ~/backup-storage-$(date +%Y%m%d).tar.gz /var/www/billnet/storage/app/public
```

### Automatic Backup (Already setup via cron - see DEPLOYMENT.md section 14)
```bash
# Check backup files
ls -lh /var/backups/billnet/
```

---

## Rollback

Jika update bermasalah:

```bash
cd /var/www/billnet

# Lihat commit history
sudo -u www-data git log --oneline -10

# Rollback ke commit tertentu
sudo -u www-data git reset --hard COMMIT_HASH

# Reinstall dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Rollback migration (jika ada)
sudo -u www-data php artisan migrate:rollback

# Clear & optimize
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize

# Restart services
sudo systemctl restart php8.4-fpm
sudo supervisorctl restart billnet-queue:*
```

---

## Security Checklist

- ✅ APP_DEBUG=false di .env
- ✅ Strong APP_KEY (auto-generated)
- ✅ SSL Certificate (HTTPS)
- ✅ Firewall aktif (port 22, 80, 443 only)
- ✅ Strong passwords untuk database & admin
- ✅ File permissions correct (www-data:www-data)
- ✅ .env tidak di-commit ke Git
- ✅ Regular security updates: `sudo apt update && sudo apt upgrade`

---

## Support

Jika ada masalah:

1. Check logs (see Monitoring section)
2. Check DEPLOYMENT.md untuk detail lengkap
3. Check GitHub Issues
4. Contact developer

---

## Workflow Summary

**Development:**
```
Edit Code → Test Local → Commit → Push to GitHub
```

**Production:**
```
Click "Update" Button → Wait → Done! ✨
```

**atau**

```bash
ssh server → ./deploy.sh → Done! ✨
```

---

**Happy Deploying! 🚀**
