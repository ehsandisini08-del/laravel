# Modul Area (Master Data Area)

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, dan **Senior System Architect**.

Saya telah menyelesaikan modul:

- Dashboard
- Authentication
- Router (Multi Router)
- PPP Profile
- PPP Secret
- Active Connection
- Package

Sekarang saya ingin membangun **Modul Area** sebagai **master data wilayah** yang akan digunakan oleh Package, Customer, Billing, Invoice, dan modul lainnya.

Modul Area sepenuhnya dikelola di database Laravel dan tidak memiliki integrasi dengan MikroTik.

---

# Tujuan

Bangun modul Area yang dapat:

- Menampilkan daftar Area.
- Menambahkan Area.
- Mengubah Area.
- Menghapus Area.
- Digunakan sebagai master data oleh modul lain.

---

# Database

Buat tabel:

areas

Field:

- id
- code
- name
- description (nullable)
- is_active
- created_at
- updated_at

---

# Aturan Data

Field wajib:

- Kode Area *
- Nama Area *

Opsional:

- Deskripsi
- Status Aktif

Contoh data:

| Kode | Nama Area |
|------|-----------|
| JKT | Jakarta |
| BGR | Bogor |
| DPK | Depok |
| BKS | Bekasi |
| TGR | Tangerang |

Kode Area harus bersifat unik.

Nama Area juga harus unik.

---

# Halaman List Area

Tampilkan tabel modern.

Kolom:

- Kode Area
- Nama Area
- Status
- Dibuat
- Action

---

# Fitur Tabel

Tambahkan:

- Search
- Pagination
- Sorting
- Sticky Header
- Skeleton Loading
- Responsive Table
- Empty State

Pencarian dapat dilakukan berdasarkan:

- Kode Area
- Nama Area

---

# Create Area

Field:

- Kode Area *
- Nama Area *
- Deskripsi
- Status Aktif

Validasi:

- Kode Area wajib.
- Nama Area wajib.
- Kode Area unik.
- Nama Area unik.
- Panjang Kode Area maksimal 10 karakter.
- Nama Area maksimal 100 karakter.

Gunakan Form Request Validation.

---

# Edit Area

User dapat mengubah:

- Kode Area
- Nama Area
- Deskripsi
- Status

Validasi tetap berlaku saat update.

---

# Delete Area

Saat tombol Delete ditekan:

Tampilkan modal konfirmasi.

Isi modal:

"Apakah Anda yakin ingin menghapus Area ini?"

Jika Area masih digunakan oleh Package atau Customer:

Jangan izinkan penghapusan.

Tampilkan pesan:

"Area masih digunakan sehingga tidak dapat dihapus."

Jika tidak digunakan:

Hapus Area.

Tampilkan Toast Notification sukses.

---

# Detail Area

Klik Nama Area untuk membuka halaman detail.

Tampilkan:

- Kode Area
- Nama Area
- Deskripsi
- Status
- Jumlah Package yang menggunakan Area
- Jumlah Customer pada Area tersebut
- Dibuat
- Terakhir diubah

---

# Relasi

Area memiliki relasi:

Has Many

- Package
- Customer

Gunakan Eloquent Relationship.

---

# Logging

Catat aktivitas:

- Area Created
- Area Updated
- Area Deleted

Gunakan Laravel Activity Log atau Laravel Log.

---

# Error Handling

Tangani dengan baik:

- Kode Area sudah digunakan.
- Nama Area sudah digunakan.
- Validation Error.
- Area masih digunakan.
- Data tidak ditemukan.

Semua error harus ditampilkan melalui Toast Notification yang informatif.

---

# Service Layer

Seluruh business logic ditempatkan pada:

app/Services/

Contoh:

AreaService

Controller hanya menangani request dan response.

Gunakan Dependency Injection.

---

# Repository (Opsional)

Jika menggunakan Repository Pattern:

app/Repositories/

AreaRepository

Pisahkan business logic dari query database.

---

# User Interface

Gunakan desain dashboard yang sudah ada.

Tambahkan:

- Modern Card
- Responsive Table
- Sticky Header
- Skeleton Loading
- Loading Button
- Toast Notification
- Confirm Delete Modal
- Status Badge
- Empty State
- Hover Animation

Status:

Hijau = Aktif

Merah = Nonaktif

---

# Integrasi Modul

Pastikan modul Area dapat digunakan oleh:

- Package
- Customer
- Billing
- Invoice
- Report

Pada modul lain, dropdown Area harus membaca data dari tabel `areas`.

Hanya Area dengan status **Aktif** yang dapat dipilih pada form.

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
5. Repository (jika digunakan)
6. Controller
7. Form Request Validation
8. Route
9. Blade View
10. JavaScript/Alpine.js
11. Toast Notification
12. Delete Confirmation
13. Activity Log
14. Testing
15. Best Practice

Pastikan implementasi kompatibel dengan Laravel 13 dan menjadi master data Area yang dapat digunakan oleh seluruh modul aplikasi ISP tanpa perubahan arsitektur di masa depan.

## Arsitektur

Gunakan pendekatan **database-first**.

- Area merupakan master data lokal di database Laravel.
- Tidak ada komunikasi dengan RouterOS API.
- Seluruh modul (Package, Customer, Billing, Invoice, Report, dan lainnya) menggunakan tabel `areas` sebagai **single source of truth**.
- Terapkan foreign key untuk menjaga integritas data antara Area, Package, dan Customer.