# Modul Customer (Pelanggan ISP) - Multi Router MikroTik

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, **Senior System Architect**, **Senior UI/UX Developer**, dan **Network Engineer** yang berpengalaman dengan MikroTik RouterOS API.

Saya telah menyelesaikan modul:

- Dashboard
- Authentication
- Router (Multi Router)
- PPP Profile
- PPP Secret
- Active Connection
- Area
- Package
- Logs

Sekarang saya ingin membangun **Modul Customer (Pelanggan)**.

Customer merupakan data utama pada aplikasi Billing ISP.

Setiap Customer memiliki paket internet, area, router, PPP Secret, dan informasi instalasi.

Gunakan pendekatan **database-first** sehingga seluruh data Customer dikelola di database Laravel.

PPP Secret di MikroTik hanya dibuat atau diperbarui ketika diperlukan melalui Service Layer.

---

# Tujuan

Bangun modul Customer yang dapat:

- Menampilkan daftar Customer.
- Menambahkan Customer baru.
- Mengubah Customer.
- Menghapus Customer.
- Melihat detail Customer.
- Mendukung Multi Router.
- Terintegrasi dengan Package.
- Terintegrasi dengan PPP Secret.
- Siap digunakan oleh Billing, Invoice, Payment, dan WA Gateway.

---

# Database

Buat tabel:

customers

Field minimal:

- id
- customer_code
- name
- address
- phone
- latitude
- longitude
- area_id
- router_id
- package_id
- ppp_secret_id (nullable)
- ppp_username
- ppp_password
- installation_date
- due_day
- isolation_date
- status
- notes
- created_at
- updated_at

---

# Relasi

Customer

belongsTo

- Area
- Router
- Package
- PPP Secret (nullable)

Package

belongsTo

- Router

Router

hasMany

- Customer

Area

hasMany

- Customer

---

# Status Customer

Gunakan enum:

- Active
- Isolated
- Suspended
- Terminated

Tampilkan badge warna:

Hijau

Active

Kuning

Suspended

Merah

Isolated

Abu

Terminated

---

# Halaman List Customer

Kolom:

- Kode Customer
- Nama
- Nomor Telepon
- Area
- Router
- Paket
- PPP Username
- Status
- Jatuh Tempo
- Action

---

# Fitur Tabel

Tambahkan:

- Search
- Pagination
- Sorting
- Sticky Header
- Filter Area
- Filter Router
- Filter Paket
- Filter Status
- Skeleton Loading
- Responsive
- Empty State

---

# Create Customer

Field:

## Data Customer

- Nama *
- Alamat *
- Nomor Telepon *
- Koordinat Lokasi *
- Area *
- Router *
- Paket *

## Data PPP

- PPP Username *
- PPP Password *

## Instalasi

- Tanggal Pemasangan *
- Tanggal Jatuh Tempo *
- Tanggal Isolir
- Catatan

---

# Koordinat Lokasi

Gunakan Google Maps atau Leaflet + OpenStreetMap.

Tambahkan:

- Map Picker
- Marker Drag
- Search Lokasi
- Klik pada peta

Saat user klik pada peta:

Latitude dan Longitude otomatis terisi.

User juga dapat:

- drag marker
- copy koordinat
- edit manual

Tambahkan tombol:

Gunakan Lokasi Saya

Menggunakan HTML5 Geolocation.

---

# Dynamic Dropdown

Saat memilih Router:

Dropdown Package hanya menampilkan Package yang dimiliki Router tersebut.

Saat memilih Package:

Otomatis isi:

- Router
- PPP Profile (internal)
- Area (jika package hanya memiliki satu area)

Jika Package memiliki banyak Area:

Dropdown Area hanya menampilkan Area yang berelasi dengan Package.

---

# Validasi

Nama wajib.

Alamat wajib.

Nomor Telepon wajib.

Koordinat wajib.

Area wajib.

Router wajib.

Package wajib.

PPP Username wajib.

PPP Password wajib.

Tanggal Pemasangan wajib.

Tanggal Jatuh Tempo wajib.

PPP Username harus unik.

Nomor Telepon harus unik.

Gunakan Form Request Validation.

---

# Integrasi Package

Package menjadi Single Source of Truth.

Saat Package dipilih:

Customer otomatis mengetahui:

- Router
- PPP Profile
- Area

Tidak perlu memilih konfigurasi lain secara manual.

---

# Integrasi PPP Secret

Saat Customer dibuat:

Sediakan opsi:

☐ Buat PPP Secret otomatis di MikroTik

Jika dicentang:

- buat PPP Secret pada router yang dipilih
- gunakan PPP Profile dari Package
- simpan relasi ke tabel ppp_secrets

Jika gagal:

Rollback transaksi.

Tampilkan Toast Notification.

---

# Edit Customer

User dapat mengubah:

- Nama
- Alamat
- Nomor Telepon
- Lokasi
- Area
- Router
- Package
- PPP Username
- PPP Password
- Tanggal Instalasi
- Jatuh Tempo
- Isolir
- Catatan
- Status

Jika Router berubah:

Validasi Package harus berasal dari Router tersebut.

Jika PPP Username berubah:

Sinkronkan ke MikroTik jika PPP Secret sudah ada.

---

# Delete Customer

Saat Delete:

Tampilkan konfirmasi.

Jika Customer memiliki:

- Invoice aktif
- PPP Secret aktif

Jangan izinkan penghapusan.

Sarankan ubah status menjadi:

Terminated.

---

# Detail Customer

Tampilkan:

## Informasi Customer

- Nama
- Kode
- Telepon
- Alamat
- Area
- Router

## Lokasi

- Google Maps / Leaflet
- Latitude
- Longitude

## Paket

- Nama Paket
- Harga

## PPP

- Username
- Status PPP

## Instalasi

- Tanggal Pemasangan
- Jatuh Tempo
- Tanggal Isolir

## Billing

Placeholder:

- Total Tagihan
- Invoice Aktif
- Pembayaran Terakhir

---

# Logging

Catat:

- Customer Created
- Customer Updated
- Customer Deleted
- Customer Activated
- Customer Isolated

Gunakan Activity Logger.

---

# Error Handling

Tangani:

- PPP Username sudah digunakan.
- Nomor Telepon sudah digunakan.
- Package tidak ditemukan.
- Router tidak ditemukan.
- Area tidak ditemukan.
- PPP Secret gagal dibuat.
- Router Offline.
- Validation Error.

Semua error tampil menggunakan Toast Notification.

---

# Service Layer

Gunakan:

app/Services/

CustomerService

PPPSecretService

Controller tidak boleh berisi business logic.

---

# Repository (Opsional)

Jika menggunakan Repository Pattern:

CustomerRepository

Pisahkan query database dari business logic.

---

# User Interface

Gunakan dashboard yang sudah ada.

Tambahkan:

- Modern Card
- Step Form
- Map Picker
- Responsive Layout
- Toast Notification
- Loading Button
- Confirm Modal
- Status Badge
- Skeleton Loading
- Empty State

---

# API Endpoint

Sediakan endpoint AJAX:

GET /customers/router/{router}/packages

Mengembalikan Package berdasarkan Router.

GET /customers/package/{package}/areas

Mengembalikan Area yang dimiliki Package.

---

# Code Quality

Ikuti:

- SOLID
- DRY
- Clean Architecture
- Service Layer
- Dependency Injection
- Form Request Validation
- Resource Controller
- Named Route
- Reusable Blade Components
- Laravel Best Practice

---

# Output

Berikan implementasi lengkap secara bertahap:

1. Migration
2. Model
3. Relationship
4. Service Layer
5. Repository (Opsional)
6. Controller
7. Form Request Validation
8. Route
9. AJAX Endpoint
10. Blade View
11. Map Picker (Leaflet/OpenStreetMap)
12. Dynamic Dropdown
13. PPP Secret Integration
14. Toast Notification
15. Delete Confirmation
16. Activity Log
17. Testing
18. Best Practice

Pastikan implementasi kompatibel dengan Laravel 13, mendukung Multi Router, menggunakan pendekatan **database-first**, serta siap diintegrasikan dengan modul Billing, Invoice, Payment Gateway, WA Gateway, dan Monitoring tanpa perubahan arsitektur di masa depan.

## Arsitektur

Gunakan pendekatan **Single Source of Truth**.

- Customer menjadi pusat data pelanggan.
- Package menjadi sumber konfigurasi layanan (harga, router, profile, area).
- PPP Secret hanya menyimpan data autentikasi MikroTik.
- RouterOS API hanya digunakan saat sinkronisasi atau Create/Update/Delete PPP Secret.
- Seluruh modul (Billing, Invoice, Payment, WA Gateway) membaca data dari Customer dan Package, bukan langsung dari MikroTik.
```