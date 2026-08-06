# Billnet Mobile Apps — Panduan Setup Firebase & Deploy

Terdapat 2 aplikasi Android (WebView + FCM):
- `android/customer-app` → aplikasi pelanggan (portal `{APP_URL}/portal`)
- `android/admin-app` → aplikasi admin (dashboard `{APP_URL}`)

Keduanya membutuhkan push notification dari Laravel via **Firebase Cloud Messaging (FCM)**.

## 1. Deploy Laravel (wajib dulu)

Push & WebView tidak bisa bekerja di `localhost`. Pastikan:

- Laravel di-deploy ke server publik dengan **HTTPS**.
- Semua halaman yang di-buka WebView bisa diakses publik (portal admin + customer).
- Jika menggunakan tunnel (ngrok/cloudflared), gunakan domain HTTPS yang tetap.

Setelah deploy, isi di `.env` server:

```env
APP_URL=https://domain-anda.com
```

> `APP_URL` dipakai untuk generate URL pada notifikasi.

## 2. Buat project Firebase

1. Buka [Firebase Console](https://console.firebase.google.com).
2. **Create project** (mis. `billnet-push`).
3. Di **Project settings → Service accounts → Generate new private key** → unduh file JSON (Service Account). Ini untuk **Laravel**.

## 3. Tambahkan 2 aplikasi Android di Firebase

Di halaman Project overview → **Add app → Android**:

- App **Pelanggan**: package `com.billnet.customer` → unduh `google-services.json` → taruh di `android/customer-app/app/`.
- App **Admin**: package `com.billnet.admin` → unduh `google-services.json` → taruh di `android/admin-app/app/`.

Tidak perlu mengisi SHA-1 untuk FCM dasar.

## 4. Konfigurasi Laravel (Firebase Admin SDK)

1. Upload file Service Account JSON ke server, mis. di `storage/app/firebase/service-account.json`.
2. Set di `.env` server:

```env
FIREBASE_PROJECT=app
FIREBASE_CREDENTIALS=/absolute/path/ke/service-account.json
```

Contoh:

```env
FIREBASE_CREDENTIALS=/var/www/billnet/storage/app/firebase/service-account.json
```

3. Jalankan (sekali):

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate --force   # sudah termasuk tabel device_tokens
php artisan queue:restart     # jika ada worker
```

> Pastikan jalur service account dapat dibaca oleh user PHP (`chmod 600`/`chmod 640`).

## 5. Verifikasi

1. **Test endpoint token** (via browser saat sudah login):
   - Pelanggan login di `/portal` → buka console, POST ke `/portal/customer/device-token` `{token:"..."}` → harus `{"success":true}`.
   - Admin login → POST ke `/mobile/admin/device-token`.
2. **Test push**:
   - Daftarkan token dari app Android (log dulu di WebView).
   - Bayar invoice via admin / webhook → pelanggan & admin dapat notifikasi.
   - Jalankan `php artisan schedule:run` untuk overdue/isolasi (pastikan scheduler berjalan di produksi: cron `* * * * * php artisan schedule:run`).

## 6. Catatan operasional

- Token FCM kadaluarsa saat app di-uninstall → Laravel hanya mencatat token; token lama yang gagal dikirim di-log (tanpa merusak proses).
- Scheduler produksi wajib aktif agar push overdue & isolasi jalan otomatis.
- Notification saat batch generate invoice bulanan sengaja **tidak** dikirim (mencegah spam). Yang dikirim: jatuh tempo, pembayaran diterima, isolasi layanan, dan pembayaran baru (admin).
