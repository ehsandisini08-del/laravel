# Modul Package (Paket Internet) - Multi Router MikroTik

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, **Senior System Architect**, dan **Network Engineer** yang berpengalaman dengan MikroTik RouterOS API.

Saya telah menyelesaikan modul:

- Dashboard
- Authentication
- Router (Multi Router)
- PPP Profile
- PPP Secret
- Active Connection
- Area

Sekarang saya ingin membangun **Modul Package (Paket Internet)** sebagai master data yang akan digunakan oleh Customer, Billing, PPP Secret, dan modul lainnya.

Modul ini **bukan data yang berasal dari MikroTik**, melainkan data master yang disimpan di database Laravel. Namun, setiap Package harus terhubung dengan Router, PPP Profile pada router tersebut, serta dapat digunakan pada **lebih dari satu Area**.

---

# Tujuan

Bangun modul Package yang dapat:

- Menampilkan daftar seluruh paket internet.
- Menambahkan paket baru.
- Mengubah paket.
- Menghapus paket.
- Mendukung Multi Router.
- Terintegrasi dengan PPP Profile.
- Terintegrasi dengan Area (Multi Area).

Package akan menjadi referensi utama saat membuat Customer maupun PPP Secret.

---

# Database

Buat tabel:

packages

Field minimal:

- id
- name
- price
- router_id
- ppp_profile_id
- description
- is_active
- created_at
- updated_at

Buat tabel pivot:

package_area

Field:

- package_id
- area_id

Gunakan foreign key dan unique constraint:

(package_id, area_id)

Relasi:

Router

↓

Package

PPP Profile

↓

Package

Package

↔

Area (Many-to-Many)

---

# Relasi

Package memiliki relasi:

- belongsTo Router
- belongsTo PPPProfile
- belongsToMany Area

Area memiliki relasi:

- belongsToMany Package

PPP Profile berasal dari tabel hasil sinkronisasi MikroTik.

Area berasal dari tabel Area yang sudah tersedia pada aplikasi.

---

# Halaman List Package

Tampilkan tabel modern.

Kolom:

- Nama Paket
- Harga
- Router
- PPP Profile
- Area
- Status
- Dibuat
- Action

Jika Package memiliki lebih dari satu Area:

- Tampilkan maksimal 3 badge Area.
- Jika lebih dari 3 Area, tampilkan badge tambahan seperti:

+2 Area

Saat badge diarahkan (hover), tampilkan seluruh daftar Area.

---

# Fitur Tabel

Tambahkan:

- Search
- Pagination
- Sorting
- Sticky Header
- Filter Router
- Filter Area
- Filter Status
- Skeleton Loading
- Responsive Table
- Empty State

---

# Create Package

Field:

- Nama Paket *
- Harga *
- Pilih Router *
- Pilih PPP Profile *
- Pilih Area (Multi Select) *
- Deskripsi
- Status Aktif

Area menggunakan **Multi Select** sehingga satu Package dapat digunakan di beberapa Area.

Gunakan komponen modern seperti:

- Tom Select
- Select2
- Choices.js

atau komponen Multi Select yang sudah digunakan pada dashboard.

---

# Dynamic Dropdown

Saat user memilih Router:

Dropdown PPP Profile harus otomatis berubah sesuai router tersebut.

Profile harus diambil dari tabel:

ppp_profiles

berdasarkan:

router_id

Jangan mengambil profile langsung dari RouterOS API.

Gunakan AJAX/Fetch/Livewire agar dropdown Profile diperbarui tanpa reload halaman.

---

# Validasi

Nama Paket wajib.

Harga wajib.

Router wajib dipilih.

PPP Profile wajib dipilih.

Minimal harus memilih **1 Area**.

Harga harus berupa angka positif.

Nama Paket tidak boleh duplikat pada kombinasi:

- Router
- PPP Profile
- Area

Contoh:

Router A

Profile 20 Mbps

Area Jakarta

Paket Home 20 Mbps

tidak boleh dibuat dua kali pada Area yang sama.

Namun Package yang sama boleh digunakan pada beberapa Area melalui relasi many-to-many.

Gunakan Form Request Validation.

Pastikan seluruh Area yang dipilih merupakan Area yang aktif.

---

# Edit Package

User dapat mengubah:

- Nama Paket
- Harga
- Router
- PPP Profile
- Area (Multi Select)
- Deskripsi
- Status

Jika Router berubah:

Dropdown PPP Profile harus otomatis memuat profile milik router baru.

Saat Edit:

- Area yang telah dipilih sebelumnya harus otomatis terpilih.
- User dapat menambah maupun menghapus Area.
- Sinkronkan relasi menggunakan:

belongsToMany()->sync()

---

# Delete Package

Saat tombol Delete ditekan:

Tampilkan modal konfirmasi.

Isi modal:

"Apakah Anda yakin ingin menghapus paket ini?"

Jika Package masih digunakan oleh Customer atau PPP Secret:

Jangan izinkan penghapusan.

Tampilkan pesan:

"Paket masih digunakan dan tidak dapat dihapus."

---

# Detail Package

Klik Nama Paket untuk membuka halaman detail.

Tampilkan:

- Nama Paket
- Harga
- Router
- PPP Profile
- Daftar Area
- Deskripsi
- Status
- Jumlah Customer yang menggunakan paket
- Jumlah PPP Secret yang menggunakan paket
- Dibuat
- Terakhir diubah

Daftar Area ditampilkan dalam bentuk badge.

Contoh:

[Jakarta]

[Bogor]

[Depok]

---

# Integrasi Customer

Modul Customer nantinya akan menggunakan Package sebagai referensi.

Saat memilih Package:

Customer otomatis mengetahui:

- Harga Paket
- PPP Profile
- Router
- Daftar Area

Dropdown Package dapat difilter berdasarkan Area Customer.

Hanya Package yang memiliki relasi dengan Area tersebut yang dapat dipilih.

---

# Integrasi PPP Secret

Saat membuat PPP Secret melalui Customer:

Profile MikroTik harus otomatis mengikuti PPP Profile yang ada pada Package.

---

# Logging

Catat aktivitas:

- Package Created
- Package Updated
- Package Deleted

Gunakan Laravel Activity Log atau Laravel Log.

---

# Error Handling

Tangani dengan baik:

- Nama paket sudah ada.
- Router tidak ditemukan.
- PPP Profile tidak ditemukan.
- Area tidak ditemukan.
- Harga tidak valid.
- Package masih digunakan.
- Validation Error.

Semua error harus ditampilkan melalui Toast Notification yang informatif.

---

# Service Layer

Seluruh business logic ditempatkan pada:

app/Services/

Contoh:

- PackageService

Controller hanya menangani request dan response.

Gunakan Dependency Injection.

---

# Repository (Opsional)

Jika arsitektur menggunakan Repository Pattern:

app/Repositories/

- PackageRepository

Pisahkan query database dari business logic.

---

# User Interface

Gunakan desain dashboard yang sudah ada.

Tambahkan:

- Modern Card
- Responsive Table
- Sticky Header
- Loading Button
- Skeleton Loading
- Toast Notification
- Confirm Delete Modal
- Status Badge
- Empty State
- Hover Animation

Status:

Hijau = Aktif

Merah = Nonaktif

---

# API Endpoint

Sediakan endpoint AJAX:

GET /packages/router/{router}/profiles

Endpoint ini mengembalikan seluruh PPP Profile berdasarkan Router yang dipilih.

Format JSON:

[
    {
        "id": 1,
        "name": "10 Mbps"
    },
    {
        "id": 2,
        "name": "20 Mbps"
    }
]

Gunakan endpoint ini untuk mengisi dropdown Profile secara dinamis.

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
4. Pivot Table (package_area)
5. Service Layer
6. Repository (jika digunakan)
7. Controller
8. Form Request Validation
9. Route
10. AJAX Endpoint Profile berdasarkan Router
11. Blade View
12. JavaScript/Alpine.js
13. Multi Select Area
14. Toast Notification
15. Delete Confirmation
16. Testing
17. Best Practice

Pastikan seluruh implementasi kompatibel dengan Laravel 13, mendukung Multi Router, menggunakan data PPP Profile hasil sinkronisasi dari database (bukan langsung dari RouterOS API), serta siap menjadi master data yang digunakan oleh Customer, Billing, PPP Secret, Invoice, dan modul lainnya tanpa perubahan arsitektur di masa depan.

## Arsitektur

Gunakan pendekatan **database-first**.

- Router hanya digunakan untuk sinkronisasi PPP Profile.
- Package sepenuhnya dikelola di database Laravel.
- Dropdown PPP Profile membaca data dari tabel `ppp_profiles` berdasarkan `router_id`.
- Dropdown Area membaca data dari tabel `areas`.
- Relasi Package dan Area menggunakan tabel pivot `package_area` (many-to-many).
- Customer dan PPP Secret hanya membaca data dari tabel `packages`, sehingga seluruh konfigurasi paket (harga, router, profile, dan area) berasal dari satu sumber data (single source of truth).
- Satu Package dapat digunakan oleh banyak Area tanpa perlu membuat data Package yang sama berulang kali.