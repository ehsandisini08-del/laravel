# Modul Logs (System Activity & Audit Log)

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, **Senior System Architect**, dan **Senior DevOps Engineer**.

Saya telah menyelesaikan modul:

- Dashboard
- Authentication
- Router (Multi Router)
- PPP Profile
- PPP Secret
- Active Connection
- Area
- Package

Sekarang saya ingin membangun **Modul Logs** sebagai pusat monitoring aktivitas sistem dan audit trail.

Modul ini digunakan untuk memudahkan administrator melakukan audit, troubleshooting, dan monitoring seluruh aktivitas pengguna maupun sistem.

---

# Tujuan

Bangun modul Logs yang dapat:

- Menampilkan seluruh aktivitas sistem.
- Menampilkan aktivitas setiap user.
- Menampilkan aktivitas Router.
- Menampilkan aktivitas CRUD.
- Menampilkan aktivitas Login & Logout.
- Mendukung filter, pencarian, dan export.
- Siap dikembangkan menjadi Audit Trail.

---

# Database

Gunakan package **Spatie Laravel Activity Log** apabila belum digunakan.

Jika sudah digunakan:

Gunakan tabel:

activity_log

Jika belum:

Implementasikan package tersebut sesuai best practice Laravel.

---

# Jenis Log

Minimal tampilkan aktivitas berikut:

## Authentication

- Login Success
- Login Failed
- Logout
- Password Changed
- Password Reset

---

## Router

- Router Created
- Router Updated
- Router Deleted
- Router Connected
- Router Connection Failed
- Router Synced

---

## PPP Secret

- PPP Secret Created
- PPP Secret Updated
- PPP Secret Deleted
- PPP Secret Enabled
- PPP Secret Disabled
- PPP Secret Synced

---

## PPP Profile

- PPP Profile Created
- PPP Profile Updated
- PPP Profile Deleted
- PPP Profile Synced

---

## Active Connection

- User Disconnected
- Bulk Disconnect
- Refresh Active Connection

---

## Package

- Package Created
- Package Updated
- Package Deleted

---

## Area

- Area Created
- Area Updated
- Area Deleted

---

## System

- Backup Created
- Restore Database
- Settings Updated
- WA Gateway Connected
- WA Gateway Disconnected

---

# Halaman Logs

Tampilkan tabel modern.

Kolom:

- Waktu
- User
- Modul
- Aktivitas
- Deskripsi
- IP Address
- Browser
- Action

---

# Fitur Tabel

Tambahkan:

- Search
- Pagination
- Sticky Header
- Sorting
- Responsive
- Skeleton Loading
- Empty State

---

# Filter

Tambahkan filter:

- User
- Modul
- Jenis Aktivitas
- Tanggal Awal
- Tanggal Akhir
- Router (jika berkaitan)
- Status

Filter dapat digunakan secara bersamaan.

---

# Search

Pencarian berdasarkan:

- Nama User
- Nama Modul
- Aktivitas
- Deskripsi
- IP Address

---

# Detail Log

Klik salah satu log untuk membuka halaman detail.

Tampilkan:

- Waktu
- User
- Email
- Modul
- Aktivitas
- Deskripsi
- Router (jika ada)
- IP Address
- Browser
- Operating System
- URL
- HTTP Method
- Request Data (JSON)
- Old Value
- New Value
- Properties
- User Agent

Gunakan tampilan JSON Viewer untuk data JSON agar mudah dibaca.

---

# Badge

Gunakan warna berbeda:

Hijau

- Create
- Login
- Connected

Kuning

- Update
- Sync

Merah

- Delete
- Failed
- Disconnect

Biru

- View
- Refresh

Abu-Abu

- Lainnya

---

# Export

Tambahkan tombol:

Export

Pilihan:

- Excel (.xlsx)
- CSV
- PDF

Export mengikuti filter yang sedang aktif.

---

# Clear Logs

Tambahkan tombol:

Clear Logs

Saat ditekan:

Tampilkan modal konfirmasi.

Konfirmasi:

"Apakah Anda yakin ingin menghapus seluruh log?"

Hanya dapat dilakukan oleh Super Admin.

---

# Logging Otomatis

Pastikan seluruh modul otomatis mencatat aktivitas.

Gunakan helper/service agar tidak perlu menulis kode logging berulang.

Contoh:

ActivityLogger::log(
module: "Package",
action: "Created",
description: "Package Home 20 Mbps berhasil dibuat."
);

Seluruh controller cukup memanggil helper tersebut.

---

# Middleware

Tambahkan middleware untuk otomatis mencatat:

- Login
- Logout
- URL yang diakses
- HTTP Method
- Response Status
- IP Address
- Browser
- Operating System

---

# Dashboard Widget

Tambahkan widget kecil pada Dashboard.

Menampilkan:

- 10 aktivitas terakhir.

Kolom:

- User
- Aktivitas
- Waktu

---

# Performance

Karena jumlah log dapat mencapai jutaan data:

Gunakan:

- Pagination
- Index Database
- Lazy Loading
- Queue jika diperlukan
- Hindari eager loading berlebihan
- Optimalkan query

---

# Retention

Tambahkan konfigurasi:

Log Retention

Pilihan:

- 30 Hari
- 90 Hari
- 180 Hari
- 365 Hari
- Tidak Pernah

Siapkan command Artisan untuk menghapus log lama berdasarkan konfigurasi.

Contoh:

php artisan logs:cleanup

Gunakan Scheduler Laravel.

---

# Permission

Jika menggunakan Spatie Permission:

Tambahkan permission:

- logs.view
- logs.export
- logs.clear

Hanya Super Admin yang dapat menghapus log.

---

# User Interface

Gunakan desain dashboard yang sudah ada.

Tambahkan:

- Statistic Card
- Modern Table
- Badge
- Timeline
- Detail Modal / Detail Page
- JSON Viewer
- Skeleton Loading
- Loading Button
- Toast Notification
- Confirm Modal
- Responsive Layout
- Empty State

---

# Statistic

Di bagian atas halaman tampilkan:

- Total Logs
- Login Hari Ini
- Error Hari Ini
- Router Activity
- User Activity

---

# Error Handling

Tangani:

- Log tidak ditemukan.
- Export gagal.
- Permission ditolak.
- Database error.

Semua error harus ditampilkan menggunakan Toast Notification.

---

# Code Quality

Ikuti:

- SOLID
- DRY
- Clean Architecture
- Service Layer
- Dependency Injection
- Resource Controller
- Form Request Validation
- Named Route
- Reusable Blade Components
- Laravel Best Practice

---

# Output

Berikan implementasi lengkap secara bertahap:

1. Install & Konfigurasi Spatie Activity Log
2. Model & Konfigurasi
3. Activity Logger Helper / Service
4. Middleware Logging
5. Controller
6. Route
7. Blade View
8. Filter & Search
9. Detail Log
10. Export Excel / CSV / PDF
11. Dashboard Widget
12. Cleanup Command
13. Scheduler
14. Permission
15. Testing
16. Best Practice

Pastikan implementasi kompatibel dengan Laravel 13, memiliki performa tinggi untuk jutaan data log, mendukung audit trail, serta mudah dikembangkan untuk seluruh modul aplikasi ISP.

## Arsitektur

Gunakan pendekatan **centralized logging**.

- Seluruh modul menggunakan satu service/helper untuk mencatat aktivitas.
- Hindari penulisan kode logging langsung di setiap controller.
- Gunakan Spatie Activity Log sebagai sumber utama audit trail.
- Log harus konsisten di seluruh aplikasi agar mudah dianalisis, diekspor, dan diintegrasikan dengan monitoring di masa depan.