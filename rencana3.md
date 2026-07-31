# Modul PPP Secret (Multi Router MikroTik)

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, dan **Network Engineer** yang berpengalaman dengan MikroTik RouterOS API.

Saya telah menyelesaikan:

* Dashboard Admin
* Login & Authentication
* Modul Router MikroTik (Multi Router)

Sekarang saya ingin membangun **Modul PPP Secret**.

Fokus utama adalah mengelola PPP Secret langsung pada Router MikroTik dengan dukungan **Multi Router**, bukan hanya CRUD database.

---

# Tujuan

Bangun modul PPP Secret yang dapat:

* Menampilkan seluruh PPP Secret dari router yang dipilih.
* Menambahkan PPP Secret ke router.
* Mengubah PPP Secret.
* Menghapus PPP Secret.
* Mengaktifkan dan menonaktifkan Secret.
* Sinkronisasi data dari MikroTik.
* Mendukung banyak router.

Semua operasi Create, Update, Delete, Enable, Disable dilakukan langsung melalui RouterOS API, kemudian disinkronkan ke database Laravel.

---

# Multi Router

Karena aplikasi mendukung Multi Router:

Pada bagian atas halaman tambahkan:

**Router Selector**

Dropdown:

* Router A
* Router B
* Router C

Semua data PPP Secret yang ditampilkan hanya berasal dari router yang sedang dipilih.

Router aktif harus dipertahankan selama pengguna berada pada modul PPP Secret.

---

# Database

Buat tabel:

ppp_secrets

Minimal field:

* id
* router_id
* mikrotik_id
* name
* password
* service
* profile
* local_address
* remote_address
* caller_id
* disabled
* comment
* last_logged_out
* created_at
* updated_at

Gunakan relasi:

Router

↓

PPP Secret

---

# Halaman List PPP Secret

Tampilkan tabel modern.

Kolom:

* Username
* Profile
* Service
* Local Address
* Remote Address
* Status
* Comment
* Router
* Last Logout
* Action

---

# Fitur Tabel

* Search
* Pagination
* Sticky Header
* Sort
* Filter Profile
* Filter Status
* Filter Router
* Skeleton Loading
* Responsive

---

# Status

Gunakan badge:

🟢 Active

🔴 Disabled

---

# Create PPP Secret

Field:

* Username
* Password
* Service
* Profile
* Local Address
* Remote Address
* Caller ID
* Comment

Saat membuka form:

Profile harus otomatis diambil dari router yang dipilih.

Service juga harus diambil dari RouterOS.

---

# Edit PPP Secret

Dapat mengubah:

* Password
* Profile
* Comment
* Local Address
* Remote Address
* Caller ID

Update langsung ke MikroTik.

---

# Delete PPP Secret

Tambahkan modal konfirmasi.

Saat Delete:

* Hapus di MikroTik.
* Sinkronkan database.
* Toast Success.
* Refresh tabel.

---

# Enable / Disable

Tambahkan tombol:

Enable

Disable

Tidak perlu edit data.

Gunakan RouterOS API.

---

# Sync

Tambahkan tombol:

Sync PPP Secret

Saat ditekan:

* Ambil seluruh PPP Secret dari router.
* Simpan ke database.
* Update data yang berubah.
* Tambahkan data baru.
* Hapus data yang sudah tidak ada di router (atau tandai sebagai tidak sinkron, sesuai desain yang dipilih).

---

# Bulk Action

Tambahkan:

* Bulk Delete
* Bulk Disable
* Bulk Enable
* Bulk Sync

---

# Detail PPP Secret

Klik username membuka halaman detail.

Tampilkan:

* Username
* Password (disembunyikan)
* Profile
* Service
* Local Address
* Remote Address
* Caller ID
* Comment
* Router
* Status

---

# Service Layer

Pisahkan seluruh komunikasi RouterOS.

Contoh:

app/Services/Mikrotik/

* PPPSecretService
* RouterConnectionService

Controller tidak boleh langsung memanggil RouterOS API.

---

# Security

* Password router tetap terenkripsi.
* Password PPP Secret tidak ditampilkan dalam bentuk teks biasa pada halaman daftar.
* Validasi Form Request.
* Authorization.
* CSRF Protection.
* Gunakan Eloquent Relationship.

---

# User Interface

Gunakan desain dashboard yang sudah ada.

Tambahkan:

* Skeleton Loading
* Toast Notification
* Loading Button
* Confirm Delete Modal
* Empty State
* Hover Animation
* Badge Status
* Responsive Table

---

# Error Handling

Tangani:

* Secret sudah ada.
* Profile tidak ditemukan.
* Router Offline.
* Authentication Failed.
* Timeout.
* API Error.
* Validation Error.

Semua error harus ditampilkan melalui Toast Notification yang informatif.

---

# Logging

Catat aktivitas:

* PPP Secret Created
* PPP Secret Updated
* PPP Secret Deleted
* PPP Secret Enabled
* PPP Secret Disabled
* PPP Secret Synced

---

# Code Quality

Ikuti:

* SOLID
* DRY
* Service Layer
* Resource Controller
* Form Request Validation
* Reusable Blade Components
* Laravel Best Practice

---

# Output

Berikan implementasi lengkap secara bertahap:

1. Migration.
2. Model.
3. Relationship.
4. RouterOS Service.
5. Sinkronisasi PPP Secret.
6. CRUD.
7. Enable / Disable.
8. Bulk Action.
9. Controller.
10. Route.
11. Blade View.
12. JavaScript/Alpine.js.
13. Toast Notification.
14. Testing.
15. Best Practice.

Pastikan seluruh implementasi mengikuti Laravel 13 dan mendukung arsitektur Multi Router, sehingga modul Customer, Billing, dan Monitoring di masa depan dapat menggunakan PPP Secret sebagai sumber data utama tanpa perlu perubahan arsitektur yang signifikan.
