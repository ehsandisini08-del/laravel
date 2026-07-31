
## Improvement: Tambahkan Menu Sidebar untuk Modul Administrasi

Bertindaklah sebagai **Senior Laravel Developer**, **Senior UI/UX Developer**, dan **Senior System Architect**.

Saya ingin menambahkan beberapa menu baru pada **Sidebar Dashboard** sebagai persiapan pengembangan modul administrasi.

### Tujuan

Tambahkan menu pada sidebar tanpa mengimplementasikan fitur atau halaman terlebih dahulu.

Untuk saat ini hanya menambahkan:

- Menu Sidebar
- Route placeholder
- Controller placeholder
- View placeholder
- Permission placeholder (jika aplikasi sudah menggunakan Role & Permission)

Semua menu harus menggunakan layout dashboard yang sudah ada dan dapat dikembangkan di tahap berikutnya.

---

# Struktur Sidebar

Tambahkan menu baru setelah menu yang sudah ada.

```

Administration
│
├── Settings
├── User Management
├── Logs
└── Backup

```

Gunakan icon yang konsisten dengan dashboard.

Contoh icon:

- Settings → Gear
- User Management → Users
- Logs → File Text / Clipboard
- Backup → Database / Hard Drive

---

# Menu

## Settings

Route:

/settings

Fungsi ke depan:

- General Settings
- Company Profile
- Application Settings
- SMTP
- WhatsApp Gateway
- Payment Gateway
- Branding
- Timezone
- Currency
- System Preferences

---

## User Management

Route:

/users

Fungsi ke depan:

- User List
- Role Management
- Permission Management
- Create User
- Edit User
- Reset Password
- Activate / Deactivate User

---

## Logs

Route:

/logs

Fungsi ke depan:

- Activity Log
- Login Log
- Router Log
- API Log
- Error Log
- Audit Trail

---

## Backup

Route:

/backup

Fungsi ke depan:

- Backup Database
- Restore Database
- Backup Configuration
- Download Backup
- Schedule Backup

---

# Placeholder Page

Setiap menu memiliki halaman sementara.

Tampilkan:

- Judul Halaman
- Deskripsi singkat
- Badge "Coming Soon"

Contoh:

Settings

"Modul Settings akan digunakan untuk mengelola konfigurasi aplikasi."

---

# Route

Tambahkan route resource atau route biasa untuk:

- settings
- users
- logs
- backup

Untuk sementara cukup menampilkan halaman placeholder.

---

# Controller

Buat controller:

- SettingsController
- UserController
- LogController
- BackupController

Masing-masing cukup memiliki method:

index()

yang mengembalikan halaman placeholder.

---

# Blade View

Buat view:

resources/views/settings/index.blade.php

resources/views/users/index.blade.php

resources/views/logs/index.blade.php

resources/views/backup/index.blade.php

Gunakan layout dashboard yang sudah ada.

---

# Permission

Jika aplikasi menggunakan Spatie Laravel Permission:

Siapkan permission:

- settings.view
- users.view
- logs.view
- backup.view

Sidebar hanya tampil jika user memiliki permission yang sesuai.

Jika belum menggunakan permission, cukup siapkan struktur agar mudah diintegrasikan nanti.

---

# UI

Gunakan desain dashboard yang sudah ada.

Tambahkan:

- Active Menu
- Active Parent Menu
- Icon
- Hover Animation
- Responsive Sidebar

---

# Code Quality

Ikuti:

- SOLID
- DRY
- Clean Architecture
- Named Route
- Resource Controller
- Laravel Best Practice

---

# Output

Berikan implementasi lengkap secara bertahap:

1. Update Sidebar Navigation
2. Route
3. Controller Placeholder
4. Blade Placeholder
5. Permission Placeholder
6. Active Menu
7. Best Practice

Pastikan implementasi kompatibel dengan Laravel 13, menggunakan struktur yang mudah dikembangkan di masa depan, dan tidak mengubah fungsi menu yang sudah ada.
```
