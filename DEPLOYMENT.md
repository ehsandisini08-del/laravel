# Panduan Deploy ke Production Server

Panduan ini menjelaskan cara meng-upload project ini (Laravel 13 + wa-gateway WhatsApp) ke server production, file/folder apa saja yang harus diupload, dan langkah-langkah konfigurasi yang diperlukan agar aplikasi berjalan dengan benar.

> Project ini memiliki 2 komponen yang harus berjalan bersamaan:
> 1. **Aplikasi Laravel** (web utama, billing MikroTik, dll)
> 2. **wa-gateway** (service Node.js untuk WhatsApp Gateway / Baileys)

---

## 1. Persyaratan Server

Sebelum mulai, pastikan server sudah terinstall:

| Komponen | Versi Minimum | Keterangan |
|---|---|---|
| PHP | 8.3 (disarankan 8.4) | Sesuai `composer.json` |
| Composer | 2.x | Manager dependensi PHP |
| Node.js | 20+ | Untuk build aset & wa-gateway |
| npm | 10+ | Manager dependensi Node.js |
| Nginx atau Apache | - | Web server |
| Supervisor | - | Menjalankan queue worker & wa-gateway |
| Cron | - | Menjalankan scheduler Laravel |
| SQLite | - | Project ini memakai SQLite (`database/database.sqlite`) |

**Ekstensi PHP yang dibutuhkan:** `sqlite3`, `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo` (umumnya sudah aktif di instalasi PHP default).

**Catatan:** karena project ini menggunakan **SQLite**, kamu TIDAK perlu install MySQL/MariaDB di server.

---

## 2. File/Folder yang Harus Diupload

Kamu **tidak** perlu mengupload semua isi project. Beberapa folder besar (`vendor`, `node_modules`) dan file sensitif (`.env`) tidak boleh diupload — cukup install ulang di server.

### 2.1 Yang HARUS diupload

```
project1/
├── app/                    # Kode aplikasi (controllers, models, jobs, dll)
├── bootstrap/              # Bootstrapping aplikasi
├── config/                 # File konfigurasi
├── database/               # Migrations, seeders, factories
│   └── database.sqlite     # (OPSIONAL) copy data DB lokal jika mau dibawa
├── public/
│   ├── index.php           # Front controller
│   ├── .htaccess           # Konfigurasi Apache (jika pakai Apache)
│   └── favicon.ico, logo, dll.  # (kecuali public/build & public/storage)
├── resources/              # Views (Blade), CSS/JS sumber
├── routes/                 # Definisi route
├── storage/                # KERANGKA saja (lihat catatan di bawah)
├── tests/                  # (Opsional) test suite
├── wa-gateway/
│   ├── src/                # Kode gateway
│   ├── package.json
│   └── package-lock.json
├── .env.example            # Template konfigurasi
├── artisan                 # CLI Laravel
├── composer.json
├── composer.lock
├── package.json
├── package-lock.json
├── postcss.config.js
├── tailwind.config.js
└── vite.config.js
```

### 2.2 Yang TIDAK boleh diupload

| File/Folder | Alasan |
|---|---|
| `.env` | Berisi secret & konfigurasi lokal. Dibuat ulang di server. |
| `vendor/` | Dependensi PHP. Install dengan `composer install`. |
| `node_modules/` | Dependensi Node.js. Install dengan `npm ci`. |
| `database/database.sqlite` | Data lokal (kecuali memang ingin memindahkan data). |
| `storage/logs/*` | Log runtime. |
| `storage/framework/cache/*`, `sessions/*`, `views/*` | Cache runtime. |
| `public/build/` | Aset hasil build Vite. Buat ulang dengan `npm run build`. |
| `public/storage` | Symlink. Buat ulang dengan `php artisan storage:link`. |
| `wa-gateway/node_modules/` | Install ulang di server. |
| `wa-gateway/sessions/` | Sesi WhatsApp. (Lihat Catatan Backup di bagian 7.) |
| `.git/` | (Jika diupload manual) |
| `storage/*.key` | Kunci enkripsi aplikasi. |

> **Catatan storage/**: cukup upload folder kosong berikut agar struktur tetap ada:
> `storage/app/private`, `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views`, `storage/logs`. Cara aman: buat dengan `mkdir -p` di server, atau upload folder `.gitignore` dari tiap direktori tersebut.

---

## 3. Metode Upload

### 3.1 Metode A — Upload File (SFTP/scp)

Gunakan FileZilla, WinSCP, atau scp. Kecualikan folder besar agar upload cepat:

```bash
# Contoh scp (jalankan dari folder project)
# Folder yang TIDAK diupload otomatis dikecualikan dengan rsync:
rsync -avz --exclude '.env' \
  --exclude 'vendor' \
  --exclude 'node_modules' \
  --exclude 'storage/logs' \
  --exclude 'storage/framework/cache/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  --exclude 'public/build' \
  --exclude 'public/storage' \
  --exclude 'wa-gateway/node_modules' \
  --exclude 'wa-gateway/sessions' \
  --exclude '.git' \
  ./ user@server:/var/www/project1/
```

### 3.2 Metode B — Git (disarankan)

Jika project sudah di-push ke GitHub/GitLab, clone di server:

```bash
cd /var/www
git clone https://github.com/USERNAME/project1.git
cd project1
```

Keuntungan: lebih mudah update ke versi terbaru (`git pull`) dan tidak perlu upload ulang semua file.

---

## 4. Deploy Aplikasi Laravel

Login SSH ke server, lalu ikuti langkah-langkah ini dari folder project (`/var/www/project1`):

### 4.1 Setup `.env`

```bash
cp .env.example .env
nano .env
```

Ubah nilai penting berikut:

```ini
APP_NAME="Nama Aplikasi"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://domain-anda.com

LOG_LEVEL=error

DB_CONNECTION=sqlite
# DB_DATABASE=/var/www/project1/database/database.sqlite   # (opsional, path absolut)

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# WhatsApp Gateway — sesuaikan dengan konfigurasi wa-gateway (bagian 6)
BAILEYS_GATEWAY_URL=http://127.0.0.1:3001
BAILEYS_GATEWAY_TOKEN=GANTI-DENGAN-API_TOKEN-wa-gateway
BAILEYS_WEBHOOK_SECRET=whsec_baileys_2026
```

> **WAJIB**: `APP_DEBUG=false` dan `APP_ENV=production` untuk keamanan. Isi `APP_KEY` dengan perintah di bawah.

Lalu generate key:

```bash
php artisan key:generate
```

### 4.2 Install dependensi PHP

```bash
composer install --no-dev --optimize-autoloader
```

### 4.3 Build aset frontend

```bash
npm ci
npm run build
```

> Jika Vite error saat runtime ("Unable to locate file in Vite manifest"), artinya build di atas belum berhasil dijalankan.

### 4.4 Setup database

```bash
# Buat file SQLite jika belum ada
touch database/database.sqlite
chmod 664 database/database.sqlite

# Jalankan migrasi
php artisan migrate --force

# (Opsional) jika ada seeder untuk data awal:
# php artisan db:seed --force
```

### 4.5 Storage link

```bash
php artisan storage:link
```

### 4.6 Cache config & route

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4.7 Izin folder

Web server dan PHP perlu hak tulis di folder berikut:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /var/www/project1   # sesuaikan user web server (www-data / nginx)
```

---

## 5. Konfigurasi Web Server

### 5.1 Nginx

Arahkan root ke folder `public/` (JANGAN ke folder project), misal file `/etc/nginx/sites-available/project1`:

```nginx
server {
    listen 80;
    server_name domain-anda.com;
    root /var/www/project1/public;

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
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan:

```bash
ln -s /etc/nginx/sites-available/project1 /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

> Gunakan `certbot --nginx` untuk memasang SSL gratis (HTTPS).

### 5.2 Apache

Pastikan mod_rewrite aktif, lalu buat `.htaccess` virtual host dengan DocumentRoot mengarah ke `public/`. Project sudah menyertakan `public/.htaccess` bawaan Laravel yang cukup.

---

## 6. Cron & Queue Worker

Aplikasi ini memakai **scheduler** (invoice bulanan, sync MikroTik tiap 5 menit, dll.) dan **queue** (pengiriman WhatsApp, job billing). Keduanya WAJIB dijalankan.

### 6.1 Scheduler (cron)

```bash
crontab -e
```

Tambahkan baris ini (dengan user yang punya akses ke folder project):

```cron
* * * * * cd /var/www/project1 && php artisan schedule:run >> /dev/null 2>&1
```

> Scheduler mencakup: `mikrotik:sync` (tiap 5 menit), `logs:cleanup` (harian), generate invoice bulanan, update invoice jatuh tempo, dan disable customer otomatis.

### 6.2 Queue worker (Supervisor)

Buat file konfigurasi `/etc/supervisor/conf.d/project1-worker.conf`:

```ini
[program:project1-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/project1/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/project1/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Lalu muat & mulai:

```bash
supervisorctl reread
supervisorctl update
supervisorctl status
```

---

## 7. Deploy wa-gateway (WhatsApp Node.js)

### 7.1 Install dependensi

```bash
cd /var/www/project1/wa-gateway
npm ci
```

### 7.2 Konfigurasi

```bash
cp .env.example .env 2>/dev/null || true
nano .env
```

Contoh isi `.env` wa-gateway:

```ini
PORT=3001
API_TOKEN=GANTI-DENGAN-TOKEN-RAHASIA
WEBHOOK_URL=https://domain-anda.com/webhooks/whatsapp
WEBHOOK_SECRET=whsec_baileys_2026
SESSION_DIR=./sessions
LOG_LEVEL=info
```

Yang perlu diperhatikan:
- `WEBHOOK_URL` harus **URL publik** aplikasi Laravel (bukan `localhost:8000`).
- `API_TOKEN` di sini harus **sama** dengan `BAILEYS_GATEWAY_TOKEN` di `.env` Laravel.
- `WEBHOOK_SECRET` harus **sama** dengan `BAILEYS_WEBHOOK_SECRET` di `.env` Laravel.

### 7.2.1 Cara Membuat API Token

`API_TOKEN` **tidak dibuat dengan perintah khusus** — cukup string acak rahasia yang kamu buat sendiri. Token ini dipakai gateway untuk memastikan hanya aplikasi Laravel (atau yang memegang token) yang bisa mengirim perintah ke gateway.

**Langkah-langkah:**

1. **Generate string acak** (pilih salah satu):

   - Linux/macOS:
     ```bash
     openssl rand -hex 32
     ```
   - Windows PowerShell:
     ```powershell
     [System.Guid]::NewGuid().ToString("N")
     ```
   - PHP (cara lain):
     ```bash
     php -r "echo bin2hex(random_bytes(32));"
     ```

   Hasilnya contoh: `5f4dcc3b5aa765d61d8327deb882cf99b1a4d8d8e8a3f0e9c9f1d4e8a3f2b1c0` (token Anda akan berbeda).

2. **Pasang nilai tersebut** di `API_TOKEN` pada `wa-gateway/.env`.

3. **Pasang nilai yang SAMA** di `BAILEYS_GATEWAY_TOKEN` pada `.env` Laravel (root project).

4. **Input juga nilai yang sama** di halaman aplikasi **Settings → WhatsApp → API Token** (nilai disimpan ke database).

5. **Restart agar aktif:**
   ```bash
   supervisorctl restart wa-gateway
   php artisan config:cache   # di folder project Laravel
   ```

**Kriteria token yang baik:**
- Panjang minimal **32 karakter** (disarankan 64 karakter hex).
- Acak, jangan pakai kata-kata mudah ditebak.
- **Jangan gunakan token yang sama** untuk semua instalasi — buat token unik per server.
- Simpan token di tempat aman; jika bocor, ganti token di ketiga tempat di atas lalu restart.

### 7.3 Jalankan dengan Supervisor

Aplikasi ini harus berjalan terus-menerus. Buat `/etc/supervisor/conf.d/wa-gateway.conf`:

```ini
[program:wa-gateway]
process_name=%(program_name)s
command=node /var/www/project1/wa-gateway/src/index.js
directory=/var/www/project1/wa-gateway
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/project1/wa-gateway/gateway.log
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl status
```

Verifikasi: `curl http://127.0.0.1:3001/health` harus mengembalikan `{"status":"ok",...}`.

### 7.4 Scan QR WhatsApp

Masuk ke halaman WhatsApp di aplikasi, scan QR untuk menghubungkan nomor WA. Folder `sessions/` akan menyimpan sesi login — **backup folder ini** agar tidak perlu scan ulang setelah deploy/restart.

---

## 8. Verifikasi

1. Buka `https://domain-anda.com/up` → harus menampilkan **OK** (health check Laravel).
2. Login ke dashboard → cek halaman Dashboard, Routers, Customers.
3. Cek status queue: `php artisan queue:monitor` (harusnya tidak ada failed jobs).
4. Cek log queue: `supervisorctl status` → semua program `RUNNING`.
5. Kirim pesan WA percobaan → pastikan gateway aktif.
6. Cek log Laravel: `tail -f storage/logs/laravel.log`.

---

## 9. Checklist Pra-Deploy

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` sudah digenerate
- [ ] `APP_URL` diisi domain asli
- [ ] `composer install --no-dev --optimize-autoloader` berhasil
- [ ] `npm run build` berhasil (folder `public/build` terisi)
- [ ] `php artisan migrate --force` selesai tanpa error
- [ ] `php artisan storage:link` sudah dibuat
- [ ] `config:cache`, `route:cache`, `view:cache` dijalankan
- [ ] Folder `storage/` & `bootstrap/cache/` writable
- [ ] Nginx root mengarah ke `public/`
- [ ] Cron `schedule:run` terpasang
- [ ] Supervisor menjalankan `queue:work` & `wa-gateway`
- [ ] `BAILEYS_GATEWAY_TOKEN` & `BAILEYS_WEBHOOK_SECRET` sama di kedua `.env`
- [ ] HTTPS/SSL terpasang

---

## 10. Update ke Versi Baru

```bash
cd /var/www/project1

# Ambil kode terbaru (jika pakai git)
git pull

# Install dependensi baru (jika composer.json berubah)
composer install --no-dev --optimize-autoloader

# Rebuild aset (jika ada perubahan frontend)
npm ci
npm run build

# Jalankan migrasi baru
php artisan migrate --force

# Bersihkan & bangun ulang cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Jalankan `php artisan down` sebelum update dan `php artisan up` setelahnya bila aplikasi menangani banyak pengguna.

---

## 11. Troubleshooting Umum

| Masalah | Solusi |
|---|---|
| `Unable to locate file in Vite manifest` | Jalankan `npm run build` |
| 403 / 404 di semua halaman | Pastikan root Nginx mengarah ke `public/`, bukan folder project |
| Halaman login tidak muncul / session error | Pastikan `SESSION_DRIVER=database` dan tabel `sessions` sudah ter-migrasi |
| QR WhatsApp tidak muncul / gateway offline | Cek `supervisorctl status wa-gateway` dan `curl http://127.0.0.1:3001/health` |
| Webhook WhatsApp tidak masuk | Pastikan `WEBHOOK_URL` memakai HTTPS publik, `whitelist` pada rute `webhooks/*` sudah di-set, dan port 3001 tidak diblokir |
| Queue job gagal | Cek `php artisan queue:failed` lalu `php artisan queue:retry all` |
| Skrip terjadwal tidak jalan | Verifikasi cron dengan `* * * * * ... schedule:run` dan lihat log |
| Perlu memindahkan data lama | Salin `database/database.sqlite` dari lokal ke server (dan `wa-gateway/sessions/` untuk sesi WA) |
| Sinkronisasi MikroTik gagal | Pastikan router dapat dijangkau dari server production (IP/port publik) |
