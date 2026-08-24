# ✅ FIX: Menu Update - Permission Error Resolved

**Date:** 23 Agustus 2026  
**Initial Commit:** 9ab3578  
**Enhanced Fix:** 24 Agustus 2026  
**Status:** ✅ Fixed & Pushed to GitHub

---

## 🐛 Problem

Ketika klik "Update" di menu, proses gagal dengan error:

```
Could not delete /var/www/billnet/vendor/webmozart/assert/src/HasAssert.php
Cannot create cache directory /var/www/.cache/composer/
```

**Root Cause:**
1. Composer tidak bisa delete files di `vendor/` karena permission issue
2. Composer tidak bisa write ke cache directory
3. Command mencoba uninstall dev dependencies padahal production menggunakan `--no-dev`

---

## ✅ Solution Applied

### **1. Added `--no-cache` Flag**
```php
$steps['composer'] = "{$composer} install --no-dev --optimize-autoloader --no-interaction --no-cache";
```

**Benefit:** Composer tidak perlu write ke cache directory, menghindari permission error.

### **2. Automatic Permission Fix**

**Windows:**
```php
$steps['permissions'] = 'icacls vendor /grant "Users:(OI)(CI)F" /T /C /Q >nul 2>&1 || echo "Permission fix attempted"';
```

**Linux:**
```php
$steps['permissions'] = 'chmod -R 775 vendor 2>/dev/null || echo "Permission fix attempted"';
```

**Benefit:** Otomatis fix permissions sebelum composer install, mencegah "Could not delete" error.

### **3. Better Composer Path Detection**

Added `findComposer()` method:
```php
protected function findComposer(): string
{
    $candidates = [
        base_path('composer.phar'),
        '/usr/local/bin/composer',
        'composer',
    ];
    
    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }
    
    return 'composer';
}
```

**Benefit:** Auto-detect composer di berbagai lokasi.

### **4. OS Detection**

```php
$isWindows = DIRECTORY_SEPARATOR === '\\';
```

**Benefit:** Command yang berbeda untuk Windows dan Linux, menghindari command incompatibility.

---

## 🎯 How It Works Now

### **Update Steps (After Fix):**

```
1. git → Pull latest code from GitHub
2. permissions → Fix vendor directory permissions ✨ NEW
3. composer → Install with --no-cache flag ✨ IMPROVED
4. migrate → Run database migrations
5. storage → Create storage link
6. npm → Build frontend assets (optional)
7. optimize → Cache config, routes, views
8. queue → Restart queue workers
9. services → Restart PHP-FPM & Supervisor (Linux only)
```

---

## 🧪 Testing

### **Local Testing (Windows):**
```bash
php artisan app:update --no-build
```

**Expected Result:**
- ✅ Git pull successful
- ✅ Permissions fixed automatically
- ✅ Composer install without cache errors
- ✅ Migrations run
- ✅ Optimization complete

### **Server Testing (Linux):**
```bash
sudo -u www-data php artisan app:update
```

**Expected Result:**
- ✅ Git pull successful
- ✅ chmod 775 vendor successful
- ✅ Composer install without permission errors
- ✅ All steps complete

---

## 📋 What Changed in Code

**File:** `app/Console/Commands/AppUpdateCommand.php`

**Changes:**
1. ✅ Added `--no-cache` flag to composer command
2. ✅ Added automatic permission fix step (Windows & Linux)
3. ✅ Added `findComposer()` method
4. ✅ Added OS detection (`$isWindows`)
5. ✅ Conditional commands based on OS
6. ✅ Improved error messages for composer failures

**Lines Changed:** 42 insertions, 9 deletions

---

## 🚀 How To Update Now

### **Method 1: Via Browser (Recommended)**

1. Your changes are already on GitHub (commit 9ab3578)
2. Go to: `https://yourdomain.com/update`
3. Click "Update Sekarang"
4. The new fixed version will be pulled automatically
5. Update should complete successfully ✅

### **Method 2: Via SSH**

```bash
ssh user@your-server
cd /var/www/billnet

# Pull the fix
sudo -u www-data git pull origin main

# Test the update command
sudo -u www-data php artisan app:update --no-build
```

---

## 🔍 Troubleshooting

### **If Update Still Fails:**

#### **Check 1: Vendor Permissions**
```bash
# Linux
ls -la /var/www/billnet/vendor | head -10
sudo chown -R www-data:www-data /var/www/billnet/vendor
sudo chmod -R 775 /var/www/billnet/vendor

# Windows
icacls D:\DEVELOPMENT\project1\vendor
```

#### **Check 2: Composer Cache**
```bash
# Clear composer cache
composer clear-cache

# Or set home directory
export COMPOSER_HOME=/var/www/billnet/.composer
```

#### **Check 3: Run Manually**
```bash
cd /var/www/billnet

# Step by step
git fetch origin main
git reset --hard origin/main
chmod -R 775 vendor  # Linux
composer install --no-dev --no-cache --no-interaction
php artisan migrate --force
php artisan optimize
```

#### **Check 4: Disk Space**
```bash
df -h
```

If disk is full, clear logs:
```bash
sudo find /var/www/billnet/storage/logs -name "*.log" -type f -mtime +7 -delete
```

---

## ✅ Verification

After update completes, verify:

```bash
# Check application status
php artisan about

# Check routes
php artisan route:list --name=repair-tasks

# Check database
php artisan tinker --execute="dd(App\Models\RepairTask::count());"

# Check logs
tail -50 /var/www/billnet/storage/logs/laravel.log
```

---

## ✅ UPDATE 2: Enhanced Fix (24 Agustus 2026)

### Perubahan Tambahan:

#### **1. Auto-unlock Mechanism**
Lock file otomatis dihapus jika stuck > 30 menit:
```php
if (file_exists($lock)) {
    $lockAge = now()->timestamp - (int) file_get_contents($lock);
    if ($lockAge > 1800) { // 30 menit
        @unlink($lock);
        @unlink($statusPath);
        $this->warn('⚠️ Lock file stuck dihapus (> 30 menit). Update dilanjutkan.');
    }
}
```

**Benefit:** User tidak perlu manual cleanup jika update stuck.

---

#### **2. Ownership Fix (Linux)**
Otomatis set ownership ke `www-data` sebelum composer:
```php
$basePath = base_path();
$steps['ownership'] = "chown -R www-data:www-data {$basePath} 2>/dev/null || true";
```

**Benefit:** File ownership diperbaiki secara otomatis saat update dijalankan sebagai root.

---

#### **3. Enhanced Vendor Cleanup**
Hapus packages bermasalah sebelum composer install:
```php
$steps['vendor-cleanup'] = 'rm -rf vendor/webmozart vendor/phpunit vendor/pestphp vendor/sebastian vendor/theseer vendor/mockery 2>/dev/null || true';
```

**Benefit:** Packages yang sering cause permission error dihapus dulu, composer install fresh.

---

#### **4. Composer Home Environment**
Set `COMPOSER_HOME` ke direktori lokal aplikasi:
```php
$composerHome = storage_path('app/.composer');
$steps['composer'] = "mkdir -p {$composerHome} && COMPOSER_HOME={$composerHome} {$composer} install --no-dev --optimize-autoloader --no-interaction --no-cache 2>&1";
```

**Benefit:** Composer tidak coba write ke `/var/www/.cache/composer/` yang restricted.

---

#### **5. Emergency Unlock Command**
Command baru untuk manual unlock:
```bash
php artisan app:update-unlock
```

**Benefit:** Admin bisa unlock via SSH tanpa manual delete files.

---

#### **6. Pre-check Validation**
Validasi vendor directory writable sebelum mulai update:
```php
$vendorPath = base_path('vendor');
if (file_exists($vendorPath) && ! is_writable($vendorPath)) {
    return redirect()->route('update.index')
        ->with('error', 'Vendor directory tidak writable. Jalankan via SSH: sudo chown -R www-data:www-data /var/www/billnet');
}
```

**Benefit:** Deteksi masalah lebih awal, tidak waste waktu.

---

#### **7. Better Error Messages**
Error messages sekarang menampilkan solusi SSH langsung:
```php
if ($label === 'composer' && str_contains($output, 'Could not delete')) {
    $this->error('❌ COMPOSER ERROR: Tidak bisa delete files di vendor/');
    $this->warn('🔧 SOLUSI CEPAT via SSH:');
    $this->line('   ssh root@your-server');
    $this->line('   cd /var/www/billnet');
    $this->line('   chown -R www-data:www-data .');
    $this->line('   chmod -R 775 vendor storage');
    $this->line('   rm -rf vendor/webmozart vendor/phpunit vendor/pestphp');
    $this->line('   sudo -u www-data composer install --no-dev --no-cache');
}
```

**Benefit:** User langsung tahu cara fix tanpa harus contact developer.

---

### Testing Steps (Updated):

#### **Step 1: Unlock Update Stuck**
```bash
# Via Artisan
php artisan app:update-unlock

# Atau manual via SSH
ssh root@your-server
rm /var/www/billnet/storage/app/update.lock
rm /var/www/billnet/storage/app/update-status.json
```

#### **Step 2: Fix Permission (One-time)**
```bash
ssh root@your-server
cd /var/www/billnet

# Fix ownership & permission
chown -R www-data:www-data .
chmod -R 775 vendor storage bootstrap/cache

# Cleanup problematic packages
rm -rf vendor/webmozart vendor/phpunit vendor/pestphp vendor/sebastian

# Test composer install
sudo -u www-data composer install --no-dev --no-cache --no-interaction
```

#### **Step 3: Test Update via Browser**
1. Buka: `https://yourdomain.com/update`
2. Klik: "Update Sekarang"
3. Monitor log real-time
4. Verify success

#### **Step 4: Monitor Logs**
```bash
# Watch update log
tail -f /var/www/billnet/storage/logs/update.log

# Check application log
tail -50 /var/www/billnet/storage/logs/laravel.log
```

---

### What Changed in Code:

**Files Modified:**
1. ✅ `app/Console/Commands/AppUpdateCommand.php`
   - Auto-unlock mechanism (line 19-23)
   - Enhanced error messages (line 57-73)
   - Ownership fix step (line 106)
   - Vendor cleanup step (line 108)
   - Composer home environment (line 111-113)

2. ✅ `app/Console/Commands/AppUpdateUnlockCommand.php` (NEW FILE)
   - Emergency unlock command

3. ✅ `app/Http/Controllers/UpdateController.php`
   - Pre-check vendor writable (line 42-45)

4. ✅ `FIX-UPDATE-MENU.md` (THIS FILE)
   - Updated documentation with enhanced fixes

---

## 📝 Summary

**Problem:** Composer permission errors during update  
**Initial Solution (23 Aug):** Added `--no-cache` flag + automatic permission fix  
**Enhanced Solution (24 Aug):** Auto-unlock + ownership fix + vendor cleanup + composer home + emergency command  
**Status:** ✅ Fixed and ready for testing  
**Next Step:** 
1. Unlock stuck update: `php artisan app:update-unlock`
2. Fix permission via SSH (one-time)
3. Test update menu di browser

**Initial Commit:** https://github.com/ehsandisini08-del/laravel/commit/9ab3578

---

**Sekarang menu Update seharusnya bekerja tanpa error!** 🎉

Jika masih ada masalah, hubungi developer dengan log error lengkap dari:
- `/var/www/billnet/storage/logs/update.log`
- `/var/www/billnet/storage/logs/laravel.log`
