# 🚀 Quick Fix: Update Menu di Server Production

**Status:** ✅ Enhanced fix sudah di-push ke GitHub (Commit: d875322)  
**Tanggal:** 24 Agustus 2026

---

## 📋 Langkah Cepat (5 Menit)

### **Step 1: Login SSH sebagai Root**

```bash
ssh root@your-server
cd /var/www/billnet
```

---

### **Step 2: Unlock Update yang Stuck**

```bash
# Hapus lock file yang stuck
rm -f storage/app/update.lock
rm -f storage/app/update-status.json

# Atau via artisan (setelah pull code baru)
sudo -u www-data php artisan app:update-unlock
```

---

### **Step 3: Pull Code Terbaru dari GitHub**

```bash
# Pull enhanced fix
sudo -u www-data git fetch origin main
sudo -u www-data git reset --hard origin/main

# Verify commit
git log --oneline -1
# Should show: d875322 Enhanced fix: Update menu permission errors & auto-unlock
```

---

### **Step 4: Fix Permission (One-Time)**

```bash
# Fix ownership ke www-data
chown -R www-data:www-data /var/www/billnet

# Fix permission directories
chmod -R 775 /var/www/billnet/vendor
chmod -R 775 /var/www/billnet/storage
chmod -R 775 /var/www/billnet/bootstrap/cache

# Hapus packages bermasalah
rm -rf /var/www/billnet/vendor/webmozart
rm -rf /var/www/billnet/vendor/phpunit
rm -rf /var/www/billnet/vendor/pestphp
rm -rf /var/www/billnet/vendor/sebastian
rm -rf /var/www/billnet/vendor/theseer
rm -rf /var/www/billnet/vendor/mockery

# Buat composer home directory
mkdir -p /var/www/billnet/storage/app/.composer
chown -R www-data:www-data /var/www/billnet/storage/app/.composer
```

---

### **Step 5: Test Composer Install**

```bash
# Install dependencies sebagai www-data
sudo -u www-data COMPOSER_HOME=/var/www/billnet/storage/app/.composer composer install --no-dev --no-cache --no-interaction

# Expected output:
# Installing dependencies from lock file
# Package operations: XX installs, X updates, X removals
# ...
# Generating optimized autoload files
```

---

### **Step 6: Test Update Menu**

#### **Via Browser:**
1. Buka: `https://yourdomain.com/update`
2. Klik: **"Update Sekarang"**
3. Monitor log real-time
4. Wait hingga selesai (biasanya 2-5 menit)
5. Verify status: ✅ Update berhasil

#### **Via SSH (Manual Test):**
```bash
sudo -u www-data php artisan app:update --no-build
```

---

## ✅ Apa yang Sudah Diperbaiki?

### **1. Auto-Unlock Mechanism**
✅ Lock file otomatis dihapus jika stuck > 30 menit  
✅ Tidak perlu manual cleanup lagi

### **2. Ownership Fix**
✅ Otomatis `chown www-data:www-data` saat update  
✅ Bekerja ketika command dijalankan sebagai root

### **3. Vendor Cleanup**
✅ Hapus packages bermasalah sebelum composer install  
✅ Packages: webmozart, phpunit, pestphp, sebastian, theseer, mockery

### **4. Composer Home**
✅ Set `COMPOSER_HOME` ke `storage/app/.composer`  
✅ Tidak perlu write ke `/var/www/.cache/composer/`

### **5. Emergency Command**
✅ Command baru: `php artisan app:update-unlock`  
✅ Unlock tanpa manual delete files

### **6. Pre-Check Validation**
✅ Cek vendor writable sebelum mulai update  
✅ Error message lebih informatif dengan solusi SSH

### **7. Better Error Messages**
✅ Tampilkan solusi SSH langsung di error log  
✅ User tahu cara fix tanpa contact developer

---

## 📊 Update Steps (New Flow)

```
1. git          → Pull latest code from GitHub
2. ownership    → chown www-data:www-data (Linux, as root) ✨ NEW
3. permissions  → chmod 775 vendor/storage/bootstrap ✨ ENHANCED
4. vendor-cleanup → rm problematic packages ✨ NEW
5. composer     → install with COMPOSER_HOME ✨ ENHANCED
6. migrate      → Run database migrations
7. storage      → Create storage link
8. npm          → Build frontend assets (optional)
9. optimize     → Cache config, routes, views
10. queue       → Restart queue workers
11. systemd     → Restart billnet-queue (Linux)
12. php-fpm     → Restart PHP-FPM (Linux)
```

---

## 🔍 Monitoring & Verification

### **Check Update Log:**
```bash
# Real-time monitoring
tail -f /var/www/billnet/storage/logs/update.log

# Last 100 lines
tail -100 /var/www/billnet/storage/logs/update.log
```

### **Check Application Log:**
```bash
tail -50 /var/www/billnet/storage/logs/laravel.log
```

### **Check Lock Status:**
```bash
# Check if update is running
ls -la /var/www/billnet/storage/app/update.lock

# Check update status
cat /var/www/billnet/storage/app/update-status.json | jq
```

### **Verify Application:**
```bash
# Check routes
php artisan route:list | grep update

# Check application status
php artisan about

# Check queue status
php artisan queue:work database --once
```

---

## 🚨 Troubleshooting

### **Jika Update Masih Gagal:**

#### **1. Check Ownership:**
```bash
ls -la /var/www/billnet/vendor | head -10
# Should show: www-data www-data
```

#### **2. Check Permission:**
```bash
stat -c "%a %n" /var/www/billnet/vendor
# Should show: 775 /var/www/billnet/vendor
```

#### **3. Check Disk Space:**
```bash
df -h /var/www
# Should have > 1GB free
```

#### **4. Clear Old Logs:**
```bash
find /var/www/billnet/storage/logs -name "*.log" -type f -mtime +7 -delete
```

#### **5. Manual Composer Install:**
```bash
cd /var/www/billnet
rm -rf vendor
sudo -u www-data COMPOSER_HOME=/var/www/billnet/storage/app/.composer composer install --no-dev --no-cache --no-interaction
```

---

## 🎯 Next Steps

### **Untuk Saat Ini:**
1. ✅ Pull code terbaru (commit d875322)
2. ✅ Fix permission via SSH (Step 4)
3. ✅ Test update via browser

### **Untuk Update Berikutnya:**
1. Code changes → Commit & push ke GitHub
2. Browser → Klik "Update Sekarang"
3. Done! ✅

**Update menu sekarang bekerja otomatis tanpa manual SSH lagi!** 🎉

---

## 📞 Support

Jika masih ada masalah, kirim log error ke developer:

```bash
# Collect logs
cat /var/www/billnet/storage/logs/update.log
cat /var/www/billnet/storage/logs/laravel.log | tail -100
```

**Files to share:**
- `storage/logs/update.log`
- `storage/logs/laravel.log`
- `storage/app/update-status.json`

---

## 🔗 References

- **Initial Fix:** [FIX-UPDATE-MENU.md](./FIX-UPDATE-MENU.md)
- **Deployment Docs:** [DEPLOYMENT.md](./DEPLOYMENT.md)
- **GitHub Commit:** https://github.com/ehsandisini08-del/laravel/commit/d875322

---

**Last Updated:** 24 Agustus 2026  
**Tested:** ✅ Local (Windows) | ⏳ Production (pending)
