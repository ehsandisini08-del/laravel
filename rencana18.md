# Modul Billing, Invoice & Auto Isolir (Multi Router MikroTik)

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, **Senior System Architect**, **Senior Database Engineer**, dan **Senior Network Engineer** yang berpengalaman dengan **Laravel 13**, **MikroTik RouterOS API**, **Queue**, **Scheduler**, dan **Billing System ISP**.

Saya telah menyelesaikan modul:

- Dashboard
- Authentication
- Router (Multi Router)
- PPP Profile
- PPP Secret
- Active Connection
- Area
- Package
- Customer
- WhatsApp Gateway (Baileys)
- Logs

Sekarang saya ingin membangun **Modul Billing** yang menjadi pusat pengelolaan tagihan pelanggan.

Modul ini nantinya akan terintegrasi dengan:

- Customer
- Package
- Payment Gateway
- WhatsApp Gateway
- MikroTik
- Logs

Gunakan arsitektur **Database First**.

Database Laravel menjadi **Single Source of Truth**, sedangkan MikroTik hanya digunakan untuk proses **Disable** dan **Enable PPP Secret**.

---

# Konsep Billing

Setiap Customer memiliki data berikut:

- Paket
- Router
- PPP Secret
- Hari Jatuh Tempo (1-31)
- Hari Isolir (1-31)

Contoh:

Customer A

Jatuh Tempo

10

Tanggal Isolir

15

Artinya:

Invoice sudah dibuat sebelumnya pada akhir bulan.

Tanggal 10

↓

Jika belum dibayar

↓

Status menjadi:

Overdue (Telat Bayar)

Internet masih aktif.

Tanggal 15 pukul 00:00

↓

Jika masih belum dibayar

↓

Disable PPP Secret pada MikroTik.

Status Customer menjadi:

Isolated

---

Contoh lain:

Customer B

Jatuh Tempo

25

Tanggal Isolir

30

Tanggal 25

↓

Status Overdue.

Tanggal 30 pukul 00:00

↓

Auto Disable PPP Secret.

---

# Tujuan

Bangun sistem Billing yang mampu:

- Generate Invoice otomatis.
- Mengelola status Invoice.
- Menandai Customer Telat Bayar.
- Menjalankan Auto Isolir.
- Mendukung Multi Router.
- Siap terintegrasi dengan Payment Gateway.
- Siap terintegrasi dengan WhatsApp Reminder.

---

# Database

## invoices

Field minimal:

- id
- invoice_number
- customer_id
- package_id
- router_id
- billing_month
- billing_year
- amount
- due_day
- isolation_day
- due_date
- status
- paid_at
- notes
- created_at
- updated_at

Status:

- Draft
- Unpaid
- Overdue
- Paid
- Cancelled

---

## invoice_items

Field:

- id
- invoice_id
- description
- qty
- price
- subtotal
- created_at
- updated_at

---

## billing_logs

Field:

- id
- customer_id
- invoice_id
- action
- description
- created_at

---

## isolation_logs

Field:

- id
- customer_id
- invoice_id
- router_id
- ppp_secret_id
- action
- reason
- status
- executed_at
- created_at

Action:

- Disabled
- Enabled

Status:

- Success
- Failed

---

# Relasi

Customer

↓

Invoice

↓

Invoice Item

Customer

↓

Package

↓

Router

↓

PPP Secret

---

# Generate Invoice

Invoice **tidak dibuat berdasarkan tanggal jatuh tempo**.

Invoice dibuat otomatis:

Hari terakhir setiap bulan.

Jam:

00:00

Gunakan Laravel Scheduler.

Contoh:

31 Januari

↓

Generate seluruh Invoice Februari.

28 Februari

↓

Generate seluruh Invoice Maret.

31 Maret

↓

Generate seluruh Invoice April.

Invoice dibuat untuk seluruh Customer yang aktif.

---

# Invoice Number

Format:

INV-YYYYMM-000001

Contoh:

INV-202608-000001

Nomor harus unik.

---

# Isi Invoice

Invoice mengambil data dari Customer:

- Paket
- Harga
- Router
- Hari Jatuh Tempo
- Hari Isolir

Contoh:

Customer

Said

Paket

20 Mbps

Harga

200000

Due Day

10

Isolation Day

15

Invoice:

Jumlah

200000

Due Date

10 Agustus

Isolation Date

15 Agustus

Status

Unpaid

---

# Validasi Generate

Invoice hanya boleh dibuat satu kali.

Jika Invoice bulan tersebut sudah ada:

Lewati.

Jangan membuat duplikat.

---

# Scheduler

Gunakan Laravel Scheduler.

Jangan menggunakan Controller.

Gunakan Queue.

---

Scheduler 1

Hari terakhir setiap bulan

00:00

↓

GenerateInvoiceJob

---

Scheduler 2

Setiap hari

00:00

↓

UpdateOverdueInvoiceJob

---

Scheduler 3

Setiap hari

00:00

↓

DisableCustomerJob

---

# Status Invoice

Status:

Draft

↓

Unpaid

↓

Overdue

↓

Paid

atau

Cancelled

---

# Status Overdue

Setiap hari pukul 00:00.

Cari Invoice:

Status

Unpaid

Jika:

Hari Ini

>

Due Date

↓

Update menjadi:

Overdue

Customer masih tetap aktif.

Belum dilakukan isolir.

---

# Status Customer

Tambahkan field:

service_status

Nilai:

- Active
- Overdue
- Isolated

Logika:

Invoice belum jatuh tempo

↓

Active

Invoice lewat jatuh tempo

↓

Overdue

Invoice belum dibayar hingga tanggal isolir

↓

Isolated

---

# Auto Isolir

Setiap hari pukul:

00:00

Cari Invoice:

Status:

Unpaid

atau

Overdue

Kemudian:

Hari Ini

==

Tanggal Isolir Customer

Jika sesuai:

Disable PPP Secret.

---

# Cara Disable

Gunakan RouterOS API.

Jangan menghapus PPP Secret.

Gunakan:

/ppp/secret/set

disabled=yes

Gunakan:

mikrotik_id

bukan username.

Flow:

Customer

↓

Router

↓

PPP Secret

↓

mikrotik_id

↓

Disable

---

# Setelah Disable

Update Database:

Customer

↓

service_status

=

Isolated

PPP Secret

↓

disabled=true

Catat:

Isolation Log

Activity Log

---

# Jika Sudah Isolated

Jangan Disable lagi.

Lewati Customer tersebut.

---

# Jika Router Offline

Jangan mengubah status Customer.

Catat:

Failed

Masukkan ke Log.

Scheduler berikutnya akan mencoba kembali.

---

# Queue

Gunakan Queue.

Scheduler hanya menjalankan:

Dispatch Job.

Job minimal:

- GenerateInvoiceJob
- UpdateOverdueInvoiceJob
- DisableCustomerJob

Persiapan:

- EnableCustomerJob
- ReminderWhatsAppJob

---

# Dashboard

Tambahkan Card:

- Total Invoice Bulan Ini
- Total Belum Bayar
- Total Telat Bayar
- Total Customer Aktif
- Total Customer Terisolir
- Jatuh Tempo Hari Ini
- Isolir Hari Ini

Tambahkan Chart:

- Invoice Bulanan
- Pembayaran Bulanan
- Customer Terisolir

---

# Menu Billing

Sidebar:

Billing

Submenu:

- Dashboard
- Invoice
- Unpaid
- Overdue
- Paid
- Isolation Log

---

# Halaman Invoice

Kolom:

- Invoice
- Customer
- Paket
- Router
- Area
- Total
- Jatuh Tempo
- Tanggal Isolir
- Status
- Action

---

# Filter

Tambahkan:

- Router
- Area
- Paket
- Bulan
- Tahun
- Status

Search:

- Nama Customer
- Nomor Invoice

---

# Badge

Hijau

Paid

Kuning

Overdue

Merah

Unpaid

Abu

Cancelled

---

# Detail Invoice

Tampilkan:

- Nomor Invoice
- Customer
- Paket
- Area
- Router
- Harga
- Jatuh Tempo
- Tanggal Isolir
- Status
- Riwayat Isolir
- Riwayat Pembayaran (Placeholder)

---

# Customer Detail

Tambahkan:

Status Layanan

Badge:

Hijau

Active

Kuning

Overdue

Merah

Isolated

Tampilkan:

- Invoice Aktif
- Invoice Terakhir
- Hari Jatuh Tempo
- Hari Isolir

---

# Integrasi WhatsApp

Siapkan hook.

Belum perlu implementasi.

Nantinya akan digunakan untuk:

- Invoice Baru
- H-7 Jatuh Tempo
- H-3 Jatuh Tempo
- H-1 Jatuh Tempo
- Hari H
- Overdue
- Sebelum Isolir
- Setelah Isolir
- Setelah Pembayaran

---

# Integrasi Payment Gateway

Belum perlu implementasi.

Namun arsitektur harus siap.

Flow nanti:

Payment Gateway

↓

Webhook

↓

Invoice

Paid

↓

Jika Customer Isolated

↓

Enable PPP Secret

↓

Customer Active

---

# Logging

Catat:

- Invoice Created
- Invoice Updated
- Invoice Paid
- Invoice Cancelled
- Invoice Overdue
- Customer Isolated
- Customer Enabled
- Disable Failed
- Enable Failed
- Router Offline

Gunakan Laravel Activity Log.

---

# Error Handling

Tangani:

- Router Offline
- Authentication Failed
- PPP Secret Tidak Ditemukan
- mikrotik_id kosong
- Customer tanpa Router
- Customer tanpa Paket
- Customer tanpa PPP Secret
- Invoice Duplikat
- Queue Failed
- Scheduler Error
- Validation Error

Semua error harus dicatat dan ditampilkan menggunakan Toast Notification jika berasal dari proses manual.

---

# Service Layer

Seluruh business logic ditempatkan pada:

app/Services/Billing/

Contoh:

- BillingService
- InvoiceService
- InvoiceGeneratorService
- AutoIsolationService
- RouterPPPService

Controller hanya menangani Request dan Response.

Gunakan Dependency Injection.

---

# Repository (Opsional)

Jika menggunakan Repository Pattern:

app/Repositories/

- InvoiceRepository
- BillingRepository

Pisahkan Business Logic dan Query Database.

---

# Code Quality

Ikuti:

- SOLID
- DRY
- Clean Architecture
- Service Layer
- Repository Pattern (Opsional)
- Dependency Injection
- Queue
- Scheduler
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
4. Invoice Service
5. Billing Service
6. Invoice Generator Service
7. Auto Isolation Service
8. Queue Job
9. Laravel Scheduler
10. RouterOS Integration
11. Controller
12. Form Request Validation
13. Routes
14. Blade View
15. Dashboard Widget
16. Activity Log
17. Testing
18. Best Practice

Pastikan implementasi kompatibel dengan Laravel 13, mendukung Multi Router, menggunakan Queue dan Scheduler, serta siap diintegrasikan dengan Payment Gateway, WhatsApp Gateway, Customer, Logs, dan Dashboard tanpa perubahan arsitektur di masa depan.

---

# Acceptance Criteria

- ✅ Invoice otomatis dibuat pada pukul 00:00 di hari terakhir setiap bulan.
- ✅ Setiap Customer hanya memiliki satu Invoice aktif setiap bulan.
- ✅ Tanggal Jatuh Tempo mengikuti data Customer.
- ✅ Tanggal Isolir mengikuti data Customer.
- ✅ Customer yang melewati tanggal jatuh tempo otomatis berubah menjadi **Overdue**, tetapi layanan internet tetap aktif.
- ✅ Customer baru diisolir pada pukul 00:00 sesuai tanggal isolir masing-masing.
- ✅ Isolir dilakukan menggunakan `disabled=yes` pada PPP Secret, bukan menghapus Secret.
- ✅ Menggunakan `mikrotik_id` sebagai identitas utama saat komunikasi dengan MikroTik.
- ✅ Customer yang sudah terisolir tidak diproses ulang.
- ✅ Seluruh proses berjalan melalui Laravel Queue dan Scheduler.
- ✅ Arsitektur siap untuk integrasi Payment Gateway sehingga ketika pembayaran berhasil, PPP Secret dapat otomatis diaktifkan kembali (`disabled=no`).
```