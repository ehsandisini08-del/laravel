Karena modul **Router** dan **PPP Secret** sudah mulai terbentuk, langkah yang paling tepat sekarang adalah **PPP Profile**. Sebaiknya modul ini dijadikan **master data yang berasal dari MikroTik**, sehingga saat membuat PPP Secret, daftar profile selalu diambil dari router yang dipilih.

Berikut prompt yang bisa langsung Anda gunakan.

# Modul PPP Profile (Multi Router MikroTik)

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, dan **Network Engineer** yang berpengalaman dengan MikroTik RouterOS API.

Saya telah menyelesaikan modul:

* Dashboard
* Authentication
* Router (Multi Router)
* PPP Secret

Sekarang saya ingin membangun **Modul PPP Profile** sebagai master data yang akan digunakan oleh PPP Secret dan modul lainnya.

Fokus utama adalah mengelola PPP Profile langsung pada Router MikroTik melalui RouterOS API dengan dukungan **Multi Router**.

---

# Tujuan

Bangun modul PPP Profile yang dapat:

* Menampilkan seluruh PPP Profile dari router yang dipilih.
* Menambahkan PPP Profile.
* Mengubah PPP Profile.
* Menghapus PPP Profile.
* Sinkronisasi data dari MikroTik.
* Mendukung Multi Router.

Semua operasi Create, Update, Delete dilakukan langsung pada MikroTik melalui RouterOS API, kemudian disinkronkan ke database Laravel.

---

# Multi Router

Tambahkan **Router Selector** di bagian atas halaman.

Ketika router diganti:

* Data PPP Profile otomatis berubah.
* Router yang dipilih disimpan selama pengguna berada di modul ini.
* Semua operasi hanya berlaku pada router yang sedang dipilih.

---

# Database

Buat tabel:

`ppp_profiles`

Minimal field:

* id
* router_id
* mikrotik_id
* name
* local_address
* remote_address
* dns_server
* rate_limit
* parent_queue
* only_one
* change_tcp_mss
* use_compression
* use_encryption
* use_ipv6
* bridge
* bridge_path_cost
* bridge_horizon
* comment
* synced_at
* created_at
* updated_at

Gunakan relasi:

Router

↓

PPP Profile

---

# Halaman List PPP Profile

Tampilkan tabel modern dengan kolom:

* Profile Name
* Rate Limit
* Local Address
* Remote Address
* DNS Server
* Only One
* Encryption
* Compression
* Router
* Last Sync
* Action

---

# Fitur Tabel

* Search
* Pagination
* Sticky Header
* Sort
* Filter
* Skeleton Loading
* Responsive
* Empty State

---

# Create PPP Profile

Field minimal:

* Profile Name
* Local Address
* Remote Address
* DNS Server
* Rate Limit
* Parent Queue
* Only One
* Change TCP MSS
* Use Compression
* Use Encryption
* Use IPv6
* Bridge
* Bridge Horizon
* Comment

Saat menyimpan:

* Simpan ke MikroTik.
* Sinkronkan ke database.
* Tampilkan Toast Notification.

---

# Edit PPP Profile

Dapat mengubah seluruh konfigurasi profile.

Perubahan harus langsung dikirim ke MikroTik kemudian disinkronkan ke database.

---

# Delete PPP Profile

Saat tombol Delete ditekan:

* Tampilkan modal konfirmasi.
* Tampilkan nama profile.
* Hapus profile dari MikroTik.
* Hapus atau tandai data sinkron di database.
* Tampilkan Toast Notification.

Jika profile masih digunakan oleh PPP Secret:

* Jangan izinkan penghapusan.
* Tampilkan pesan yang jelas kepada pengguna.

---

# Sync PPP Profile

Tambahkan tombol:

**Sync PPP Profile**

Saat ditekan:

* Ambil seluruh profile dari router.
* Tambahkan profile baru.
* Perbarui profile yang berubah.
* Tandai atau hapus profile yang sudah tidak ada di router.
* Perbarui `synced_at`.

---

# Detail PPP Profile

Klik nama profile untuk membuka halaman detail.

Tampilkan:

* Nama Profile
* Router
* Rate Limit
* Local Address
* Remote Address
* DNS Server
* Parent Queue
* Only One
* Compression
* Encryption
* IPv6
* Bridge
* Comment
* Jumlah PPP Secret yang menggunakan profile tersebut (berdasarkan database lokal)

---

# Service Layer

Seluruh komunikasi RouterOS harus berada di:

app/Services/Mikrotik/

Contoh:

* PPPProfileService
* RouterConnectionService

Controller tidak boleh berkomunikasi langsung dengan RouterOS API.

---

# Integrasi dengan PPP Secret

Pastikan modul PPP Secret menggunakan data dari tabel `ppp_profiles`.

Saat membuat atau mengedit PPP Secret:

* Dropdown Profile harus berasal dari database hasil sinkronisasi berdasarkan router yang dipilih.
* Jangan mengambil daftar profile langsung dari RouterOS setiap kali form dibuka.

---

# Logging

Catat aktivitas:

* PPP Profile Created
* PPP Profile Updated
* PPP Profile Deleted
* PPP Profile Synced

Gunakan Laravel Log atau Activity Log.

---

# Error Handling

Tangani dengan baik:

* Nama profile sudah ada.
* Router Offline.
* Authentication Failed.
* API Disabled.
* Connection Timeout.
* Validation Error.
* Profile sedang digunakan oleh PPP Secret.

Semua error harus ditampilkan melalui Toast Notification yang informatif.

---

# User Interface

Gunakan desain dashboard yang sudah ada.

Tambahkan:

* Skeleton Loading
* Loading Button
* Toast Notification
* Confirm Delete Modal
* Responsive Table
* Badge
* Empty State
* Hover Animation

---

# Code Quality

Ikuti:

* SOLID
* DRY
* Clean Architecture
* Service Layer
* Dependency Injection
* Form Request Validation
* Resource Controller
* Named Route
* Reusable Blade Components
* Laravel Best Practice

---

# Output

Berikan implementasi lengkap secara bertahap:

1. Migration.
2. Model.
3. Relationship.
4. RouterOS Service.
5. Sinkronisasi PPP Profile.
6. CRUD.
7. Controller.
8. Form Request Validation.
9. Route.
10. Blade View.
11. JavaScript/Alpine.js.
12. Toast Notification.
13. Testing.
14. Best Practice.

Pastikan seluruh implementasi mengikuti Laravel 13, mendukung Multi Router, dan menjadi master data yang dapat digunakan oleh modul PPP Secret, Customer, Billing, serta modul lainnya tanpa perubahan arsitektur di masa depan.

**Saran arsitektur:** agar performa aplikasi tetap baik, gunakan pola **database-first setelah sinkronisasi**. Artinya, modul seperti **PPP Secret**, **Customer**, dan **Billing** membaca data dari database Laravel, sedangkan RouterOS API digunakan untuk operasi sinkronisasi serta Create/Update/Delete. Pendekatan ini mengurangi beban komunikasi ke router, mempercepat tampilan halaman, dan membuat aplikasi lebih mudah diskalakan ketika jumlah router bertambah.
