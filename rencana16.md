# Modul WhatsApp Gateway (Evolution API + Baileys) - Multi Device

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, **Senior Node.js Developer**, **Senior DevOps Engineer**, **Senior System Architect**, dan **Integration Engineer** yang berpengalaman dengan **Evolution API**, **Baileys**, dan **Laravel 13**.

Saya telah menyelesaikan modul:

- Dashboard
- Authentication
- Router (Multi Router MikroTik)
- PPP Profile
- PPP Secret
- Active Connection
- Area
- Package
- Customer
- Logs

Sekarang saya ingin membangun **Modul WhatsApp Gateway** menggunakan **Evolution API** (engine Baileys).

WhatsApp Gateway akan digunakan untuk:

- Reminder Tagihan
- Notifikasi Pembayaran
- Notifikasi Isolir
- Broadcast
- Pesan Manual
- OTP (masa depan)
- Notifikasi Sistem

Gunakan arsitektur **database-first**, sehingga seluruh data device, session, message, dan log disimpan di database Laravel, sedangkan Evolution API hanya bertugas sebagai WhatsApp Engine.

---

# Arsitektur

Gunakan arsitektur berikut:

Laravel Billing

↓

REST API

↓

Evolution API

↓

Baileys

↓

WhatsApp

Laravel tidak boleh berkomunikasi langsung dengan WhatsApp Web.

Seluruh komunikasi harus melalui REST API Evolution API.

---

# Konfigurasi

Tambahkan halaman konfigurasi:

Settings

↓

WhatsApp Gateway

Field:

- Evolution API URL
- Evolution API Key
- Default Instance Name
- Webhook URL
- Webhook Secret
- Request Timeout
- Auto Reconnect
- Status Gateway

Semua konfigurasi disimpan di database atau `.env`.

Gunakan Service Layer untuk membaca konfigurasi.

---

# Database

## Table

wa_instances

Field:

- id
- instance_name
- display_name
- phone_number
- profile_name
- status
- qr_code
- last_seen
- webhook_url
- connected_at
- disconnected_at
- created_at
- updated_at

---

wa_messages

Field:

- id
- instance_id
- customer_id (nullable)
- phone
- type
- direction
- message
- media_url
- status
- message_id
- sent_at
- delivered_at
- read_at
- created_at
- updated_at

---

wa_templates

Field:

- id
- name
- category
- title
- content
- is_active
- created_at
- updated_at

---

# Sidebar

Administrasi

↓

WhatsApp Gateway

Submenu:

- Dashboard
- Devices
- Templates
- Messages
- Broadcast
- Settings
- Logs

---

# Dashboard

Tampilkan:

Card:

- Connected Device
- Disconnected Device
- Total Message Today
- Total Sent
- Total Failed
- Queue

Chart:

- Message per Day
- Delivery Success Rate

Recent Activity:

- Device Connected
- Device Disconnected
- QR Generated
- Message Sent

---

# Device Management

Halaman utama:

Daftar seluruh WhatsApp Device.

Kolom:

- Device
- Nomor
- Nama WhatsApp
- Status
- Last Seen
- Action

Status:

Hijau

Connected

Merah

Disconnected

Kuning

Connecting

Abu

Not Registered

---

# Create Device

Field:

- Instance Name
- Display Name

Saat disimpan:

Panggil Evolution API:

Create Instance

Kemudian tampilkan:

QR Code

untuk discan menggunakan WhatsApp.

---

# QR Code

Tambahkan tombol:

Generate QR

Saat ditekan:

Panggil endpoint Evolution API.

Tampilkan QR Code pada Modal.

QR otomatis refresh apabila expired.

Polling status setiap 3–5 detik.

Jika berhasil login:

Modal otomatis tertutup.

Status berubah menjadi:

Connected

Tidak perlu reload halaman.

---

# Device Detail

Tampilkan:

- Nama Device
- Nomor WhatsApp
- Foto Profil (jika tersedia)
- Nama Profil
- Status
- Battery (jika tersedia)
- Connected Since
- Last Seen

Action:

- Generate QR
- Disconnect
- Logout
- Restart
- Refresh Status

---

# Disconnect

Saat Disconnect:

Panggil Evolution API.

Update status database.

---

# Logout

Saat Logout:

- Hapus Session WhatsApp.
- Update database.
- Status berubah menjadi:

Not Registered

---

# Auto Sync

Tambahkan tombol:

Sync Device

Saat ditekan:

Ambil data terbaru dari Evolution API.

Sinkronkan:

- Status
- Nomor
- Nama
- Last Seen

---

# Template Message

CRUD Template.

Field:

- Nama Template
- Kategori

Kategori:

- Reminder
- Payment
- Broadcast
- OTP
- Custom

Isi Template.

Support Variable:

{{customer_name}}

{{phone}}

{{package}}

{{price}}

{{due_date}}

{{invoice_number}}

{{company}}

Preview Template.

---

# Send Manual Message

Form:

- Pilih Device
- Nomor Tujuan
- Template (opsional)
- Pesan

Support:

- Text
- Image
- PDF
- Document

Saat dikirim:

Gunakan Evolution API.

Simpan log ke database.

---

# Broadcast

Field:

- Device
- Area
- Package
- Status Customer

Target:

Semua Customer

atau

berdasarkan filter.

Preview jumlah penerima.

Gunakan Queue Laravel.

Progress realtime.

---

# Integrasi Customer

Pada Detail Customer tambahkan:

WhatsApp

Nomor

Status

Riwayat Pesan

Tombol:

Send WhatsApp

Isi otomatis:

Nomor Customer.

---

# Integrasi Billing

Nantinya Billing dapat mengirim:

Reminder H-7

Reminder H-3

Reminder H-1

Hari H

Lewat Tempo

Sudah Dibayar

Terisolir

Gunakan Template Message.

---

# Queue

Seluruh pengiriman pesan wajib menggunakan:

Laravel Queue.

Jangan mengirim langsung dari Controller.

Tambahkan retry jika gagal.

---

# Webhook

Sediakan endpoint:

POST

/webhooks/whatsapp

Webhook menerima:

- Connected
- Disconnected
- QR Updated
- Message Sent
- Delivered
- Read
- Incoming Message

Simpan seluruh event ke database.

---

# Logging

Catat aktivitas:

- Device Created
- Device Connected
- Device Disconnected
- QR Generated
- Message Sent
- Message Failed
- Broadcast Started
- Broadcast Finished

Gunakan Laravel Activity Log.

---

# Error Handling

Tangani:

- Evolution API Offline
- Invalid API Key
- Instance Not Found
- QR Expired
- Device Offline
- Timeout
- Invalid Phone Number
- Rate Limit
- Message Failed

Semua error tampil menggunakan Toast Notification.

---

# Service Layer

Seluruh komunikasi dengan Evolution API harus berada pada:

app/Services/WhatsApp/

Contoh:

- EvolutionApiService
- WhatsAppGatewayService
- WhatsAppMessageService
- WhatsAppTemplateService

Controller tidak boleh memanggil REST API secara langsung.

Gunakan Laravel HTTP Client.

---

# Repository (Opsional)

Jika menggunakan Repository Pattern:

app/Repositories/

- WhatsAppRepository
- TemplateRepository

Pisahkan query database dari business logic.

---

# API Client

Buat wrapper khusus untuk Evolution API.

Method minimal:

- createInstance()
- deleteInstance()
- connect()
- disconnect()
- logout()
- restart()
- generateQr()
- getQr()
- getStatus()
- getProfile()
- sendText()
- sendImage()
- sendDocument()
- sendFile()
- sendLocation()
- sendContact()
- sendButtons()
- sendList()
- getMessages()

Jangan memanggil endpoint Evolution API secara langsung dari Controller.

---

# User Interface

Gunakan desain dashboard yang sudah ada.

Tambahkan:

- QR Modal
- Live Status Badge
- Skeleton Loading
- Toast Notification
- Loading Button
- Confirm Modal
- Empty State
- Device Card
- Hover Animation

Polling status menggunakan AJAX/Fetch atau Laravel Reverb/WebSocket bila tersedia.

---

# Security

- API Key tidak boleh ditampilkan di frontend.
- Simpan credential dengan aman.
- Validasi seluruh request.
- Gunakan CSRF Protection.
- Validasi Webhook Secret.
- Rate limit endpoint webhook.

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
4. Evolution API Client Service
5. WhatsApp Gateway Service
6. Repository (Opsional)
7. Controller
8. Form Request Validation
9. Routes
10. Webhook Endpoint
11. Blade View
12. JavaScript (Polling QR & Status)
13. Queue Job untuk Pengiriman Pesan
14. Template Message CRUD
15. Broadcast Module
16. Customer Integration
17. Billing Integration (Placeholder)
18. Toast Notification
19. Activity Log
20. Testing
21. Best Practice

Pastikan implementasi kompatibel dengan Laravel 13, menggunakan Evolution API sebagai WhatsApp Engine berbasis Baileys, mendukung Multi Device, Multi Session, Queue, Webhook, QR Scan Login, serta siap diintegrasikan dengan modul Customer, Billing, Invoice, Payment, Reminder, dan Logs tanpa perubahan arsitektur di masa depan.

## Arsitektur

Gunakan pendekatan **database-first + service-oriented architecture**.

- Laravel menjadi pusat business logic (single source of truth).
- Evolution API hanya bertugas sebagai WhatsApp Engine.
- Seluruh status device, template, dan riwayat pesan disimpan di database Laravel.
- Seluruh komunikasi ke Evolution API dilakukan melalui Service Layer.
- Semua pengiriman pesan menggunakan Queue agar tidak menghambat request pengguna.