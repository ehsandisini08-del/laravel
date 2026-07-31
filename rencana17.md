# Modul WhatsApp Gateway (Baileys) - Multi Device

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, **Senior Node.js Developer**, **Senior DevOps Engineer**, **Senior System Architect**, dan **WhatsApp Integration Engineer** yang berpengalaman dengan **Baileys** dan **Laravel 13**.

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

Sekarang saya ingin membangun **Modul WhatsApp Gateway** menggunakan **Baileys** secara langsung tanpa Evolution API.

WhatsApp Gateway akan digunakan untuk:

- Reminder Tagihan
- Notifikasi Pembayaran
- Notifikasi Isolir
- Broadcast
- Pesan Manual
- OTP (masa depan)
- Notifikasi Sistem

Gunakan arsitektur **database-first**, sehingga seluruh data device, session, message, template, dan log disimpan di database Laravel, sedangkan Baileys berjalan sebagai service Node.js yang menyediakan REST API untuk Laravel.

---

# Arsitektur

Gunakan arsitektur berikut:

Laravel Billing

↓

REST API

↓

Node.js WhatsApp Service (Baileys)

↓

WhatsApp Multi Device

Laravel tidak boleh berkomunikasi langsung dengan WhatsApp Web.

Seluruh komunikasi dilakukan melalui REST API yang dibuat menggunakan Node.js + Baileys.

---

# Struktur Project

Pisahkan project menjadi dua service:

billing/

↓

Laravel 13

wa-gateway/

↓

Node.js + Baileys

Komunikasi menggunakan HTTP REST API.

---

# Konfigurasi

Tambahkan halaman:

Settings

↓

WhatsApp Gateway

Field:

- Gateway URL
- API Token
- Request Timeout
- Auto Reconnect
- Max Retry
- Session Storage Path
- Webhook URL
- Webhook Secret
- Status Gateway

Konfigurasi dapat disimpan di database atau `.env`.

---

# Database

## Table

wa_devices

Field:

- id
- device_name
- session_name
- phone_number
- profile_name
- profile_picture
- status
- last_seen
- connected_at
- disconnected_at
- created_at
- updated_at

---

wa_messages

Field:

- id
- device_id
- customer_id (nullable)
- phone
- type
- direction
- message
- media_url
- status
- baileys_message_id
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
- Delivery Success

Recent Activity:

- Device Connected
- Device Disconnected
- QR Generated
- Message Sent

---

# Device Management

Daftar Device.

Kolom:

- Device
- Nomor
- Nama WhatsApp
- Status
- Last Seen
- Action

Status:

- Connected
- Connecting
- Disconnected
- QR Waiting
- Logged Out

---

# Create Device

Field:

- Device Name
- Session Name

Saat disimpan:

Node.js membuat session Baileys baru.

Session harus disimpan agar login tidak perlu scan ulang setelah restart.

Gunakan:

useMultiFileAuthState()

atau penyimpanan session yang lebih baik (database/Redis) jika diperlukan.

---

# QR Code

Tombol:

Generate QR

Saat ditekan:

Laravel memanggil REST API Node.js.

Node.js membuat koneksi Baileys.

QR dikembalikan dalam format Base64.

Laravel menampilkan QR pada Modal.

QR otomatis refresh jika expired.

Polling status setiap 3 detik.

Jika berhasil login:

- Modal otomatis tertutup.
- Status berubah menjadi Connected.
- Nomor WhatsApp dan nama profil otomatis tersimpan ke database.

---

# Device Detail

Tampilkan:

- Nama Device
- Nomor WhatsApp
- Nama Profil
- Foto Profil
- Status
- Connected Since
- Last Seen

Action:

- Generate QR
- Disconnect
- Logout
- Restart Session
- Refresh Status

---

# Session Management

Node.js wajib:

- Menyimpan session.
- Auto reconnect.
- Restore session ketika service restart.
- Tidak meminta scan QR lagi jika session masih valid.

Jika session rusak:

Status berubah menjadi:

QR Waiting

---

# Auto Sync

Tambahkan tombol:

Sync Device

Sinkronkan:

- Nomor
- Nama Profil
- Foto Profil
- Status
- Last Seen

---

# Template Message

CRUD Template.

Kategori:

- Reminder
- Payment
- Broadcast
- OTP
- Custom

Support Variable:

{{customer_name}}

{{phone}}

{{package}}

{{price}}

{{due_date}}

{{invoice_number}}

{{company}}

Preview sebelum dikirim.

---

# Send Manual Message

Field:

- Device
- Nomor
- Template (opsional)
- Pesan

Support:

- Text
- Image
- PDF
- Document
- Audio
- Video

Setelah berhasil:

Simpan log ke database.

---

# Broadcast

Filter:

- Area
- Package
- Status Customer

Gunakan Laravel Queue.

Progress realtime.

Delay antar pesan dapat diatur agar lebih aman.

---

# Integrasi Customer

Detail Customer:

Tambahkan:

WhatsApp

- Nomor
- Status Device
- Riwayat Pesan

Tombol:

Send WhatsApp

Nomor otomatis terisi.

---

# Integrasi Billing

Gunakan untuk:

- Reminder H-7
- Reminder H-3
- Reminder H-1
- Hari H
- Lewat Tempo
- Pembayaran Berhasil
- Isolir
- Aktivasi

Semua menggunakan Template Message.

---

# Queue

Seluruh pengiriman pesan wajib menggunakan Laravel Queue.

Controller tidak boleh mengirim pesan secara langsung.

Tambahkan retry apabila gagal.

---

# Webhook

Node.js mengirim webhook ke Laravel.

Endpoint:

POST

/webhooks/whatsapp

Event:

- Connected
- Disconnected
- QR Updated
- Message Sent
- Message Delivered
- Message Read
- Incoming Message

Laravel menyimpan seluruh event ke database.

---

# Logging

Catat:

- Device Created
- Device Connected
- Device Disconnected
- QR Generated
- Session Restored
- Message Sent
- Message Failed
- Broadcast Started
- Broadcast Finished

Gunakan Laravel Activity Log.

---

# Error Handling

Tangani:

- Baileys Service Offline
- Session Not Found
- QR Expired
- Device Offline
- Connection Lost
- Invalid Phone Number
- Timeout
- Rate Limit
- Message Failed

Semua error tampil menggunakan Toast Notification.

---

# Service Layer

Seluruh komunikasi dengan Node.js berada pada:

app/Services/WhatsApp/

Contoh:

- BaileysGatewayService
- WhatsAppDeviceService
- WhatsAppMessageService
- WhatsAppTemplateService

Gunakan Laravel HTTP Client.

Controller tidak boleh memanggil API Node.js secara langsung.

---

# Node.js Service

Bangun service menggunakan:

- Express.js
- Baileys
- QRCode
- Pino Logger

Minimal endpoint:

POST /devices
DELETE /devices/{session}
POST /devices/{session}/connect
POST /devices/{session}/disconnect
POST /devices/{session}/logout
GET /devices/{session}/status
GET /devices/{session}/qr
POST /messages/send-text
POST /messages/send-image
POST /messages/send-document
POST /messages/send-audio
POST /messages/send-video

Gunakan Bearer Token untuk autentikasi.

---

# User Interface

Gunakan desain dashboard yang sudah ada.

Tambahkan:

- QR Modal
- Device Card
- Status Badge
- Skeleton Loading
- Loading Button
- Toast Notification
- Confirm Modal
- Empty State
- Hover Animation

Polling status menggunakan AJAX atau WebSocket.

---

# Security

- REST API wajib menggunakan Bearer Token.
- Validasi seluruh request.
- API hanya menerima request dari Laravel.
- Session Baileys tidak boleh dapat diakses publik.
- Validasi Webhook Secret.
- Gunakan Rate Limiter.

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
4. Node.js Baileys Service
5. REST API Node.js
6. Laravel Gateway Service
7. Controller
8. Form Request Validation
9. Routes
10. Webhook Endpoint
11. Blade View
12. JavaScript (QR Polling & Status)
13. Queue Job
14. Template Message CRUD
15. Broadcast Module
16. Customer Integration
17. Billing Integration (Placeholder)
18. Toast Notification
19. Activity Log
20. Docker Compose (Laravel + Node.js + Redis)
21. Testing
22. Best Practice

Pastikan implementasi kompatibel dengan Laravel 13, menggunakan Baileys sebagai WhatsApp Engine, mendukung Multi Device, Multi Session, Auto Reconnect, Persistent Session, Queue, QR Scan Login, Webhook, serta siap diintegrasikan dengan Customer, Billing, Invoice, Payment, Reminder, dan Logs tanpa perubahan arsitektur di masa depan.

## Arsitektur

Gunakan pendekatan **database-first + service-oriented architecture**.

- Laravel menjadi pusat business logic (single source of truth).
- Node.js + Baileys menjadi WhatsApp Engine.
- Seluruh status device, template, dan riwayat pesan disimpan di database Laravel.
- Session WhatsApp disimpan secara persisten agar tidak perlu scan QR setelah restart.
- Seluruh komunikasi antara Laravel dan Node.js menggunakan REST API yang aman.
- Semua pengiriman pesan menggunakan Queue untuk menjaga performa aplikasi.