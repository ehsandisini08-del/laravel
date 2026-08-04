# Cara Test Sistem Isolir

> **Catatan Windows PowerShell:** gunakan format `"<kode>" | php artisan tinker` (stdin)
> untuk semua perintah tinker di bawah. Format `--execute '...'` bisa gagal di
> PowerShell 5.1 karena bug parsing argumen, terutama untuk kode dengan `app(...)->handle(...)`.

## 1. Generate Invoice

Klik tombol **Generate Invoice** di Billing Dashboard → `GenerateInvoiceJob` akan membuat invoice untuk bulan depan untuk semua customer Active.

Atau manual via tinker:

```powershell
"app(App\Services\Billing\InvoiceService::class)->generateAllForMonth(8, 2026);" | php artisan tinker
```

## 2. Buat Invoice Overdue

Jalankan `UpdateOverdueInvoiceJob` untuk menandai invoice yang melewati `due_date` sebagai Overdue. Ubah `due_date` invoice yang baru digenerate ke kemarin dulu:

```powershell
"App\Models\Invoice::where('status', 'unpaid')->update(['due_date' => now()->subDays(2)]);" | php artisan tinker
```

Lalu jalankan jobnya:

```powershell
"app(App\Jobs\Billing\UpdateOverdueInvoiceJob::class)->handle(app(App\Services\Billing\InvoiceService::class));" | php artisan tinker
```

Cek: status invoice berubah jadi **Overdue**, `service_status` customer jadi **overdue**.

```powershell
"echo 'Invoice: '.App\Models\Invoice::find(1)->status->value.PHP_EOL; echo 'Customer: '.App\Models\Customer::find(1)->service_status->value.PHP_EOL;" | php artisan tinker
```

## 3. Tes Auto Isolir

Pastikan:
- Invoice status = `unpaid` / `overdue`
- `due_date` sudah lewat
- `isolation_day` di **invoice** terisi (karena isolasi dibaca dari `invoice.isolation_day`)
- Hari ini == tanggal isolasi (tanggal dibentuk dari `billing_month` + `billing_year` + `isolation_day`)

Jika belum sesuai, samakan dengan hari ini:

```powershell
"App\Models\Invoice::where('id', 1)->update(['isolation_day' => now()->day, 'billing_month' => now()->month, 'billing_year' => now()->year]);" | php artisan tinker
```

Jalankan job isolasi:

```powershell
"app(App\Jobs\Billing\DisableCustomerJob::class)->handle(app(App\Services\Billing\AutoIsolationService::class));" | php artisan tinker
```

> **Perhatian:** job ini akan benar-benar mematikan (disable) PPP Secret customer di MikroTik.

Cek:
- `isolation_logs` → record baru dengan status `success` / `failed`
- `service_status` customer → `isolated`
- `ppp_secret.disabled` → `true`
- PPP Secret di MikroTik → `disabled=yes`

```powershell
"echo json_encode(App\Models\IsolationLog::latest()->first()?->toArray());" | php artisan tinker
"echo 'Customer: '.App\Models\Customer::find(1)->service_status->value.PHP_EOL; echo 'PPP disabled: '.(App\Models\PppSecret::find(5)->disabled ? 'true' : 'false').PHP_EOL;" | php artisan tinker
```

## 4. Cek Log

```powershell
Get-Content storage/logs/laravel.log -Tail 50 -Wait | Select-String -Pattern "isolat|disable|invoice"
```

## Ringkasan Skenario

| Langkah                  | Tujuan                    |
|--------------------------|---------------------------|
| Generate invoice         | Buat tagihan              |
| Ubah due_date ke kemarin | Simulasi telat bayar      |
| UpdateOverdueInvoiceJob  | Tandai sebagai Overdue    |
| Ubah isolation_day ke hari ini | Simulasi hari isolir |
| DisableCustomerJob       | Eksekusi isolir           |
| Cek isolation_logs & MikroTik | Verifikasi hasil      |

## 5. Tes Pembayaran (Customer Bayar)

Fitur bayar tersedia via tombol **Mark as Paid** di halaman detail invoice
(`billing/invoices/{id}`), untuk invoice berstatus `unpaid` atau `overdue`.

Yang terjadi saat invoice ditandai dibayar:
- Status invoice → `paid`, `paid_at` terisi
- `billing_logs` → record `invoice_paid`
- Jika customer `overdue`/`isolated` → di-reaktivasi:
  - `service_status` → `active`
  - Jika PPP Secret sedang `disabled` → di-enable ulang di MikroTik (`/ppp/secret/enable`)
  - `isolation_logs` → record `enabled` (status `success`/`failed`)
  - Jika reactivation gagal (router offline/dll), invoice tetap `paid` tapi customer tetap `isolated`

### Skenario A: Bayar saat customer Overdue (PPP Secret masih aktif)

Langsung dari detail invoice klik **Mark as Paid**, atau via tinker:

```powershell
"app(App\Services\Billing\PaymentService::class)->markAsPaid(App\Models\Invoice::find(1));" | php artisan tinker
```

Cek:
- invoice status → `paid`
- customer `service_status` → `active`
- `ppp_secret.disabled` → tetap `false`
- **tanpa** record `isolation_logs` (karena secret belum di-disable)

### Skenario B: Bayar setelah Isolasi (PPP Secret di-disable)

1. Jalankan isolasi dulu (lihat langkah 3) sampai customer `isolated` & PPP Secret `disabled=true`.
2. Klik **Mark as Paid** di detail invoice, atau via tinker:

```powershell
"app(App\Services\Billing\PaymentService::class)->markAsPaid(App\Models\Invoice::find(1));" | php artisan tinker
```

3. Cek:
- invoice status → `paid`
- customer `service_status` → `active`
- `ppp_secret.disabled` → `false`
- `isolation_logs` → record baru `action=enabled`, `status=success`
- PPP Secret di MikroTik → `disabled=no` (aktif kembali)

### Jika Reactivation Gagal

Jika router offline, `isolation_logs` mencatat `action=enabled`, `status=failed`.
Invoice tetap `paid`, tapi customer tetap `isolated` — bisa di-retry dengan menjalankan
perintah bayar lagi setelah router online.
