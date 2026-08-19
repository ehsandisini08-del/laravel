<div align="center">

# 📡 Billnet — Sistem Manajemen & Billing ISP

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?logo=alpine.js&logoColor=white)](https://alpinejs.dev)
[![Pest](https://img.shields.io/badge/Pest-4-C21325?logo=pestphp&logoColor=white)](https://pestphp.com)
[![SQLite](https://img.shields.io/badge/Database-SQLite-003B57?logo=sqlite&logoColor=white)](https://www.sqlite.org)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

**Sistem manajemen Internet Service Provider (ISP) berbasis MikroTik** — kelola router, pelanggan, billing, pembayaran otomatis, WhatsApp Gateway, portal pelanggan, hingga aplikasi mobile dalam satu platform terintegrasi.

</div>

---

## ✨ Fitur Utama

| Modul | Deskripsi |
|---|---|
| 📊 **Dashboard** | Ringkasan status router (total / online / offline), aktivitas terbaru, aksi cepat |
| 🌐 **Network** | Manajemen **Router MikroTik** (test koneksi, sync), **PPP Secret**, **PPP Profile**, **PPP Active**, dan **CPE Devices** (integrasi GenieACS) |
| 👥 **Customer** | CRUD pelanggan, import/export **Excel**, filter area/paket, pengiriman password portal via **WhatsApp**, rekonsiliasi |
| 📦 **Package & Area** | Manajemen paket layanan dan area layanan |
| 🧾 **Billing** | Generate invoice otomatis, pembayaran via **Midtrans / Xendit / Tripay**, pengingat invoice, isolasi otomatis pelanggan menunggak, cetak invoice |
| 💬 **WhatsApp Gateway** | Gateway berbasis **Baileys**: kelola perangkat (QR), template pesan, pesan masuk/keluar, broadcast, webhook |
| 🔐 **Customer Portal** | Login pelanggan, dashboard, tagihan, info WiFi (ubah SSID/password via GenieACS), pembayaran, akun |
| 📱 **Aplikasi Mobile** | Dukungan push notification via **Firebase Cloud Messaging (FCM)** untuk admin & pelanggan |
| 🏗️ **Infrastruktur** | Modul ODC / ODP / MAP *(dalam pengembangan)* |
| 🏬 **Gudang** | Stok Barang, Barang Masuk, Barang Keluar *(dalam pengembangan)* |
| 🛠️ **Administrasi** | Manajemen pengguna & peran, pengaturan aplikasi, log aktivitas, backup, job monitor, update aplikasi, buka kunci akun |

---

## 🧩 Arsitektur

Proyek ini terdiri dari **2 komponen** yang berjalan bersamaan:

1. **Aplikasi Laravel** — web utama: manajemen, billing, portal pelanggan, dan API.
2. **wa-gateway** — service Node.js (Baileys) untuk WhatsApp Gateway, dijalankan dengan `npm start` di folder `wa-gateway/`.

```
┌──────────────────────┐      ┌──────────────────────┐      ┌───────────────────┐
│   Web Admin (Laravel)│ ───▶ │   MikroTik Router    │      │  GenieACS (CPE)   │
│   Portal Pelanggan   │ ◀─── │  (RouterOS API)      │      │  (TR-069)         │
└──────────┬───────────┘      └──────────────────────┘      └───────────────────┘
           │
┌──────────▼───────────┐      ┌──────────────────────┐      ┌───────────────────┐
│ Payment Gateway      │      │ WhatsApp Gateway     │      │ FCM Push          │
│ Midtrans/Xendit/Tripay│      │ (wa-gateway/Baileys) │      │ (Aplikasi Mobile) │
└──────────────────────┘      └──────────────────────┘      └───────────────────┘
```

---

## 🛠️ Tech Stack

### Backend
- **PHP** 8.4 & **Laravel** 13
- **SQLite** sebagai database default (tanpa MySQL/MariaDB)
- **RouterOS API** (`evilfreelancer/routeros-api-php`) untuk integrasi MikroTik
- **GenieACS** untuk sinkronisasi perangkat CPE/ONU
- **spatie/laravel-activitylog** untuk log aktivitas
- **PhpSpreadsheet** untuk import/export Excel

### Frontend
- **Blade** + **Tailwind CSS 3** + **Alpine.js 3** (dark mode)
- **Laravel Breeze** sebagai dasar autentikasi
- **Vite** untuk bundling aset

### Layanan Pendukung
- **WhatsApp Gateway** (Node.js + Baileys) — terhubung lewat `BAILEYS_GATEWAY_URL`
- **Payment Gateway** — Midtrans, Xendit, Tripay (switchable driver)
- **Firebase Cloud Messaging (FCM)** — push notification mobile
- **Queue** (database driver), **Scheduler**, **Supervisor** untuk worker

---

## 👥 Peran Pengguna (Roles)

| Peran | Hak Akses |
|---|---|
| 👨‍💻 **Developer** | Akses penuh termasuk Settings, Update Aplikasi, Job Monitor |
| 🛡️ **Super Admin** | Akses penuh tanpa menu developer |
| 🗺️ **Admin Area** | Terbatas pada area tertentu (router/network disembunyikan) |

---

## 🚀 Instalasi

### Prasyarat

| Komponen | Versi Minimum |
|---|---|
| PHP | 8.3 (disarankan 8.4) |
| Composer | 2.x |
| Node.js | 20+ |
| npm | 10+ |

Ekstensi PHP yang dibutuhkan: `sqlite3`, `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`.

### Langkah Instalasi

```bash
# 1. Install dependensi PHP & setup dasar
composer install
cp .env.example .env
php artisan key:generate

# 2. Migrasi database SQLite
php artisan migrate --force
# (opsional) php artisan db:seed

# 3. Frontend
npm install
npm run build

# 4. Jalankan aplikasi + queue + scheduler
php artisan serve
php artisan queue:listen --tries=1
php artisan schedule:work

# 5. (Opsional) WhatsApp Gateway
npm --prefix wa-gateway install
npm --prefix wa-gateway run start
```

> 💡 Jalankan semuanya sekaligus dengan perintah bawaan:
> ```bash
> composer run dev
> ```

---

## ⚙️ Konfigurasi Penting (`.env`)

```env
APP_NAME=Billnet
APP_URL=http://project1.test
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

# WhatsApp Gateway (Baileys)
BAILEYS_GATEWAY_URL=http://localhost:3001
BAILEYS_GATEWAY_TOKEN=
BAILEYS_WEBHOOK_SECRET=whsec_baileys_2026

# Payment Gateway (pilih salah satu / beberapa)
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
XENDIT_SECRET_KEY=
XENDIT_WEBHOOK_VERIFICATION_TOKEN=
TRIPAY_MERCHANT_CODE=
TRIPAY_API_KEY=
TRIPAY_PRIVATE_KEY=

# Firebase Cloud Messaging (push notification mobile)
FIREBASE_PROJECT=app
FIREBASE_CREDENTIALS=
```

---

## 🧪 Testing

Proyek menggunakan **Pest** untuk pengujian otomatis:

```bash
# Jalankan seluruh test suite
php artisan test

# Jalankan dengan output ringkas
php artisan test --compact

# Jalankan test tertentu
php artisan test --filter=CustomerTest
```

Format kode dengan **Laravel Pint**:

```bash
vendor/bin/pint
```

---

## 📂 Struktur Direktori Utama

```
project1/
├── app/
│   ├── Console/Commands/     # Perintah artisan kustom (sync, cleanup, update)
│   ├── Enums/                # Enum status invoice, pembayaran, pelanggan
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Billing/      # Invoice, payment
│   │   │   ├── Portal/       # Portal pelanggan
│   │   │   ├── WhatsApp/     # Gateway WhatsApp
│   │   │   └── Mobile/       # Device token push
│   │   ├── Middleware/       # admin, installation, admin-area, developer
│   │   └── Requests/         # Form request validasi
│   ├── Jobs/                 # Job antrian (billing, whatsapp)
│   ├── Models/               # Eloquent models
│   ├── Notifications/        # Notifikasi (FCM, dll)
│   └── Services/
│       ├── Mikrotik/         # RouterOS API (PPP, CPE, dsb.)
│       ├── Billing/          # Invoice, payment, auto-isolation
│       ├── PaymentGateway/   # Midtrans, Xendit, Tripay
│       ├── WhatsApp/         # Baileys gateway
│       ├── Genieacs/         # Sinkronisasi CPE
│       └── Excel/            # Import/export customer
├── database/
│   ├── migrations/           # Skema database
│   ├── factories/            # Factory model (untuk test)
│   └── seeders/              # Seeder data awal
├── resources/views/          # Blade views (admin, portal, whatsapp, billing)
├── routes/                   # web, billing, portal, mobile, whatsapp, auth
├── tests/                    # Pest test suite
└── wa-gateway/               # Service WhatsApp Gateway (Node.js/Baileys)
```

---

## 🔑 Modul Utama

### 🌐 Network (MikroTik)
- Manajemen **Router** dengan integrasi RouterOS API — test koneksi, sync, status online/offline.
- **PPP Secret** — kelola user PPPoE, enable/disable, bulk sync antar router.
- **PPP Profile** — profil bandwidth/rate-limit sinkron dari router.
- **PPP Active** — pantau koneksi aktif, disconnect, bulk disconnect.
- **CPE Devices** — sinkronisasi perangkat ONU/ONT via GenieACS, ubah SSID & password WiFi.

### 🧾 Billing
- Generate invoice bulanan otomatis sesuai `due_day` pelanggan.
- Pembayaran melalui **Midtrans / Xendit / Tripay** dengan webhook verifikasi signature.
- **Auto-isolation** pelanggan menunggak melewati tenggat isolasi.
- **Invoice reminder** otomatis (H-3, H-1, dsb.) via WhatsApp.
- Cetak invoice & export (CSV / Excel / PDF).

### 💬 WhatsApp Gateway
- Pairing perangkat via **QR Code** (Baileys).
- Template pesan untuk tagihan, pengingat, notifikasi.
- Broadcast massal ke pelanggan.
- Webhook menerima pesan masuk/keluar.

### 🔐 Portal Pelanggan
- Login mandiri pelanggan (password dikirim via WhatsApp).
- Lihat tagihan, bayar online, kelola WiFi sendiri, dan data akun.

---

## 📦 Scheduler & Jobs

```bash
# Melihat daftar job terjadwal
php artisan schedule:list

# Menjalankan scheduler (via cron/supervisor)
php artisan schedule:work
```

| Job | Fungsi |
|---|---|
| Generate invoice | Membuat invoice bulanan otomatis |
| Invoice reminder | Mengirim pengingat tagihan via WhatsApp |
| Auto isolation | Mengisolasi pelanggan yang menunggak |
| Sync routers | Sinkronisasi data router & PPP secret |
| Logs cleanup | Membersihkan log lama |

---

## 🚢 Deployment

Panduan lengkap deployment ke server production tersedia di **[`DEPLOYMENT.md`](DEPLOYMENT.md)**.

Hal yang perlu disiapkan: Nginx/Apache, Supervisor (queue worker & wa-gateway), cron untuk scheduler, dan upload folder yang diperlukan saja (jangan upload `vendor`, `node_modules`, `.env`).

---

## 🤝 Kontribusi

Terima kasih sudah mempertimbangkan untuk berkontribusi! Silakan buat *pull request* atau laporkan *issue*. Pastikan kode mengikuti standar:

- Ikuti pola kode yang sudah ada (convention Laravel).
- Tulis test untuk setiap perubahan (`php artisan make:test --pest`).
- Format kode dengan Pint sebelum submit.

---

## 📄 Lisensi

Proyek ini dirilis di bawah lisensi **MIT**. Silakan lihat file [LICENSE](LICENSE) untuk detail selengkapnya.