# Modul Active Connection (PPP Active) - Multi Router MikroTik

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, dan **Senior Network Engineer** yang berpengalaman dengan MikroTik RouterOS API.

Saya telah menyelesaikan modul:

- Dashboard
- Authentication
- Router (Multi Router)
- PPP Secret
- PPP Profile

Sekarang saya ingin membangun **Modul Active Connection (PPP Active)** yang menampilkan seluruh user PPP yang sedang online secara real-time dari MikroTik dengan dukungan Multi Router.

Modul ini akan menjadi pusat monitoring koneksi pelanggan.

---

# Tujuan

Bangun modul Active Connection yang mampu:

- Menampilkan seluruh PPP Active dari router yang dipilih.
- Menampilkan status online secara real-time.
- Disconnect satu user.
- Disconnect banyak user (Bulk Disconnect).
- Refresh data tanpa reload halaman.
- Mendukung Multi Router.
- Menampilkan statistik koneksi.

Semua data Active Connection harus diambil langsung dari RouterOS API.

Jangan menyimpan data active secara permanen di database kecuali untuk logging atau histori.

---

# Multi Router

Tambahkan Router Selector di bagian atas halaman.

Ketika router berubah:

- Data Active Connection otomatis diperbarui.
- Router yang dipilih tetap tersimpan selama pengguna berada pada modul ini.
- Semua operasi hanya berlaku pada router yang sedang dipilih.

---

# Data Source

Gunakan RouterOS API:

/ppp/active

Jangan mengambil data dari database lokal.

---

# Halaman Active Connection

Tampilkan tabel modern.

Kolom:

- Username
- Service
- Profile
- Caller ID (MAC)
- Address (IP Client)
- Uptime
- Session Time
- Connected Since
- Router
- Status
- Action

---

# Tambahkan Informasi

Jika tersedia dari RouterOS tampilkan:

- Interface
- Encoding
- Radius
- Bytes In
- Bytes Out
- Packets In
- Packets Out

Jika RouterOS tidak menyediakan secara langsung, tampilkan hanya data yang tersedia tanpa menghasilkan error.

---

# Statistik

Di bagian atas halaman tampilkan kartu statistik:

- Total Active User
- Router Aktif
- Router Offline
- Total PPP Secret
- Online Percentage

Semua statistik dihitung secara real-time.

---

# Search & Filter

Tambahkan:

- Search Username
- Filter Profile
- Filter Service
- Filter Status
- Filter Router
- Pagination
- Sticky Header
- Skeleton Loading
- Responsive Table

---

# Refresh

Tambahkan tombol:

Refresh

Ketika ditekan:

- Ambil ulang data dari MikroTik.
- Jangan reload halaman.
- Gunakan AJAX/Fetch/Livewire.
- Tampilkan loading indicator.

Tambahkan Auto Refresh yang dapat diaktifkan pengguna:

- Off
- 10 detik
- 30 detik
- 60 detik

Saat Auto Refresh aktif:

- Jangan mengganggu filter dan pencarian yang sedang digunakan.
- Pertahankan posisi scroll tabel.

---

# Detail Active User

Klik Username untuk membuka halaman detail.

Tampilkan:

- Username
- Router
- Service
- Profile
- Local Address
- Remote Address
- Caller ID
- Interface
- Uptime
- Connected Since
- Session ID
- Bytes In
- Bytes Out
- Packets In
- Packets Out
- Encoding
- Radius
- Comment (jika ada)

Tambahkan tombol:

Disconnect User

---

# Disconnect User

Ketika tombol Disconnect ditekan:

Tampilkan modal konfirmasi.

Isi modal:

"Apakah Anda yakin ingin memutus koneksi user ini?"

Jika dikonfirmasi:

- Jalankan remove pada /ppp/active menggunakan RouterOS API.
- Refresh daftar Active Connection.
- Tampilkan Toast Notification sukses.

---

# Bulk Disconnect

Tambahkan checkbox pada tabel.

User dapat memilih banyak Active Connection.

Tambahkan tombol:

Disconnect Selected

Konfirmasi sebelum menjalankan proses.

Setelah selesai:

- Refresh data.
- Tampilkan jumlah user yang berhasil diputus.
- Tampilkan jika ada user yang gagal diputus.

---

# Sinkronisasi dengan PPP Secret

Saat user Active dipilih:

Ambil data tambahan dari database:

- Nama Customer (jika ada)
- Profile lokal
- Paket Internet
- Nomor Pelanggan
- Nomor WhatsApp
- Status Billing

Gunakan relasi database.

Jangan melakukan query berulang (N+1).

Gunakan eager loading.

---

# Logging

Catat aktivitas:

- User Disconnected
- Bulk Disconnect
- Refresh Active Connection
- Router Connection Error

Gunakan Laravel Activity Log atau Laravel Log.

---

# Error Handling

Tangani:

- Router Offline
- Authentication Failed
- API Disabled
- Timeout
- User sudah disconnect
- Active Session tidak ditemukan
- Router tidak dapat dihubungi

Semua error harus muncul melalui Toast Notification yang informatif.

Jangan menampilkan exception Laravel kepada pengguna.

---

# Service Layer

Seluruh komunikasi RouterOS harus berada di:

app/Services/Mikrotik/

Contoh:

- PPPActiveService
- RouterConnectionService

Controller tidak boleh memanggil RouterOS API secara langsung.

Gunakan Dependency Injection.

---

# Performance

Karena jumlah Active Connection bisa mencapai ribuan:

Optimalkan performa:

- Collection Mapping
- Lazy Processing
- Pagination pada aplikasi bila memungkinkan
- Cache koneksi Router
- Hindari query database berulang
- Hindari request RouterOS yang tidak diperlukan

Gunakan eager loading untuk data lokal.

---

# UI

Gunakan dashboard yang sudah ada.

Tambahkan:

- Modern Card
- Statistic Card
- Status Badge
- Online Badge
- Responsive Table
- Sticky Header
- Skeleton Loading
- Toast Notification
- Confirm Modal
- Loading Button
- Hover Animation
- Empty State

Gunakan warna status:

Hijau = Online

Merah = Disconnect

Abu-abu = Loading

---

# Code Quality

Ikuti:

- SOLID
- DRY
- Clean Architecture
- Service Layer
- Repository Pattern (jika sudah digunakan)
- Dependency Injection
- Resource Controller
- Form Request
- Reusable Blade Components
- Laravel Best Practice

---

# Output

Berikan implementasi lengkap secara bertahap:

1. Route
2. Controller
3. Service Layer
4. RouterOS API Integration
5. Active Connection Fetch
6. AJAX/Livewire Refresh
7. Disconnect User
8. Bulk Disconnect
9. Detail Page
10. Blade View
11. JavaScript/Alpine.js
12. Toast Notification
13. Activity Log
14. Testing
15. Best Practice

Pastikan implementasi kompatibel dengan Laravel 13, mendukung Multi Router, memiliki performa tinggi untuk ribuan koneksi aktif, dan mudah diintegrasikan dengan modul Customer, Billing, Monitoring, serta Dashboard.

## Catatan Arsitektur

Karena data `/ppp/active` bersifat dinamis dan berubah setiap saat, gunakan pendekatan **router-first** untuk modul ini. Data selalu diambil langsung dari MikroTik ketika halaman dibuka atau di-refresh. Database Laravel hanya digunakan untuk memperkaya informasi (Customer, Paket, Billing) serta menyimpan log aktivitas atau histori jika diperlukan. Hindari menyimpan snapshot Active Connection secara permanen karena berpotensi menyebabkan data tidak sinkron dan meningkatkan kompleksitas aplikasi.