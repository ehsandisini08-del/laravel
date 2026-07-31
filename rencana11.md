## Improvement: Tambahkan Menu "WA Gateway" pada Sidebar Administrasi

Bertindaklah sebagai **Senior Laravel Developer**, **Senior UI/UX Developer**, dan **Senior System Architect**.

Tambahkan satu menu baru pada grup **Administration** untuk persiapan integrasi WhatsApp Gateway.

Untuk saat ini **hanya tambahkan menu, route, controller, dan halaman placeholder**. Belum perlu mengimplementasikan fitur WhatsApp Gateway.

---

# Struktur Sidebar

Perbarui menu Administration menjadi:

Administration
│
├── Settings
├── User Management
├── WA Gateway
├── Logs
└── Backup

Gunakan icon yang sesuai, misalnya:

- WA Gateway → Message Circle / Message Square / WhatsApp

---

# Menu WA Gateway

Route:

/wa-gateway

Nama Menu:

WA Gateway

---

# Fungsi di Masa Depan

Modul ini nantinya akan digunakan untuk:

- Konfigurasi WhatsApp Gateway
- QR Code Login
- Status Koneksi WhatsApp
- Disconnect Device
- Reconnect Device
- Multiple Device
- Test Message
- Template Pesan
- Broadcast
- Auto Notification
- Webhook Configuration
- Session Management
- Message Queue
- Delivery Report

Saat ini cukup sebagai placeholder.

---

# Route

Tambahkan route:

GET /wa-gateway

Named Route:

wa-gateway.index

---

# Controller

Buat controller:

WAGatewayController

Method:

index()

Controller hanya mengembalikan halaman placeholder.

---

# Blade View

Buat view:

resources/views/wa-gateway/index.blade.php

Gunakan layout dashboard yang sudah ada.

---

# Placeholder Page

Tampilkan:

Judul:

WA Gateway

Deskripsi:

"Modul WA Gateway akan digunakan untuk mengelola koneksi WhatsApp, mengirim notifikasi otomatis, broadcast pesan, serta integrasi komunikasi pelanggan."

Tambahkan badge:

Coming Soon

---

# Permission

Jika menggunakan Spatie Laravel Permission:

Tambahkan permission:

wa-gateway.view

Sidebar hanya ditampilkan jika user memiliki permission tersebut.

Jika permission belum digunakan, cukup siapkan strukturnya.

---

# User Interface

Gunakan desain dashboard yang sudah ada.

Tambahkan:

- Active Menu
- Icon
- Hover Animation
- Responsive Layout
- Coming Soon Card

---

# Code Quality

Ikuti:

- SOLID
- DRY
- Clean Architecture
- Resource Controller
- Named Route
- Laravel Best Practice

---

# Output

Berikan implementasi lengkap secara bertahap:

1. Update Sidebar Navigation
2. Route
3. WAGatewayController
4. Blade Placeholder
5. Permission Placeholder
6. Active Menu
7. Best Practice

Pastikan implementasi kompatibel dengan Laravel 13 dan hanya menambahkan struktur placeholder tanpa mengimplementasikan fitur WhatsApp Gateway.