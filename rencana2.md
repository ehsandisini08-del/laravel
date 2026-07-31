#Integrasi MikroTik untuk Laravel 13

Bertindaklah sebagai **Senior Backend Engineer**, **Senior Laravel Developer**, dan **Network Engineer** yang berpengalaman dengan **MikroTik RouterOS API**.

Saya sudah memiliki Dashboard Admin menggunakan Laravel 13. Sekarang saya ingin membangun **modul integrasi MikroTik** sebagai fondasi untuk aplikasi Billing ISP yang mendukung **multi-router**.

Jangan membuat modul Customer, PPP Secret, atau Billing terlebih dahulu. Fokus hanya pada **manajemen koneksi router MikroTik**.

---

# Tujuan

Buat sistem yang dapat:

* Menambahkan banyak router MikroTik.
* Menghubungkan aplikasi ke router melalui RouterOS API.
* Menguji koneksi router.
* Menyimpan status koneksi.
* Mengelola router secara terpusat.
* Menjadi pondasi untuk modul PPP, Hotspot, Queue, Radius, dan Billing di masa depan.

---

# Teknologi

Gunakan:

* Laravel 13
* PHP 8.4+
* Blade
* Tailwind CSS
* Alpine.js
* Vite
* Laravel Service Layer
* Eloquent ORM

Gunakan package RouterOS API yang stabil dan masih aktif dikembangkan.

---

# Database

Buat tabel **routers** dengan struktur yang fleksibel.

Kolom minimal:

* id
* name
* description
* host
* api_port
* api_ssl
* username
* password (terenkripsi)
* location
* timezone
* routeros_version
* board_name
* identity
* architecture
* uptime
* last_seen_at
* status (Online / Offline)
* enabled
* is_default
* created_at
* updated_at

Gunakan migration terbaik.

---

# CRUD Router

Buat halaman:

## Router List

Tampilkan:

* Nama Router
* Identity
* Host/IP
* Port API
* RouterOS Version
* Board Name
* Status
* Last Seen
* Default Router
* Action

Fitur:

* Search
* Pagination
* Filter Status
* Sort
* Refresh Status
* Bulk Delete
* Bulk Enable
* Bulk Disable

---

## Create Router

Field:

* Nama
* Host/IP
* API Port
* SSL Enable
* Username
* Password
* Lokasi
* Deskripsi

Validasi lengkap.

---

## Edit Router

Dapat mengubah seluruh konfigurasi tanpa menghapus data.

---

## Delete Router

Soft Delete.

---

# Test Connection

Tambahkan tombol:

"Test Connection"

Ketika ditekan:

* mencoba login ke RouterOS API
* menampilkan loading
* apabila berhasil tampilkan:

✓ Connected

beserta informasi:

* Identity
* RouterOS Version
* Board Name
* Architecture
* CPU
* Total Memory
* Free Memory
* Uptime
* Current Time

Jika gagal tampilkan alasan kegagalan.

---

# Auto Synchronization

Buat command Artisan:

php artisan mikrotik:sync

Command akan:

* mengecek semua router aktif
* memperbarui status Online / Offline
* mengambil informasi router terbaru
* memperbarui Last Seen

Command dapat dijalankan melalui Scheduler.

---

# Service Layer

Pisahkan seluruh komunikasi RouterOS ke dalam:

app/Services/Mikrotik/

Contoh:

* MikrotikService
* RouterConnectionService
* RouterInformationService

Controller tidak boleh langsung memanggil API RouterOS.

---

# Security

Password router:

* wajib dienkripsi menggunakan Crypt Laravel
* jangan pernah ditampilkan kembali dalam bentuk asli
* hanya didekripsi saat membuat koneksi API

Gunakan:

* Form Request Validation
* Authorization
* CSRF Protection
* Rate Limiting bila diperlukan

---

# Multi Router Support

Setiap router memiliki:

* UUID
* Default Router
* Status
* Priority

Seluruh modul di masa depan harus dapat memilih router yang akan digunakan.

Jangan mengasumsikan hanya ada satu router.

---

# User Interface

Gunakan desain dashboard yang sudah ada.

Halaman Router harus memiliki:

* Skeleton Loading
* Toast Notification
* Badge Status
* Confirm Delete Modal
* Loading Button
* Empty State
* Responsive Table

Status router:

🟢 Online

🔴 Offline

🟡 Checking...

---

# Logging

Catat setiap aktivitas:

* Router Added
* Router Updated
* Router Deleted
* Test Connection
* Connection Failed
* Router Online
* Router Offline

Gunakan Laravel Log dan Activity Log.

---

# Error Handling

Tangani dengan baik:

* Timeout
* Authentication Failed
* API Disabled
* SSL Error
* Connection Refused
* Host Not Found
* Router Tidak Merespon

Tampilkan pesan yang mudah dipahami oleh pengguna.

---

# Code Quality

Ikuti:

* SOLID
* DRY
* Repository Pattern bila diperlukan
* Service Layer
* Form Request
* Resource Controller
* Named Route
* Reusable Components
* Laravel Best Practice

---

# Output

Berikan implementasi lengkap secara bertahap:

1. Struktur folder.
2. Package RouterOS yang digunakan dan cara instalasi.
3. Migration tabel routers.
4. Model Router.
5. Form Request Validation.
6. Service Layer.
7. CRUD Router.
8. Test Connection.
9. Sinkronisasi informasi router.
10. Artisan Command.
11. Scheduler.
12. Controller.
13. Route.
14. Blade View.
15. Best Practice.
16. Struktur yang mudah dikembangkan untuk modul PPP Secret, PPP Profile, Hotspot, Queue, Radius, dan Monitoring.

Pastikan seluruh implementasi mengikuti standar Laravel 13 dan dirancang agar mudah dikembangkan menjadi aplikasi Billing ISP dengan dukungan multi-router.
