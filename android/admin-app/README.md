# Billnet Admin App (Android WebView + FCM)

Aplikasi admin: membungkus dashboard admin Laravel dalam `WebView` agar terasa seperti aplikasi native, plus push notification (FCM) untuk admin (mis. notifikasi pembayaran baru).

## Cara setup di Android Studio

1. **Buat project baru** di Android Studio:
   - Template: **Empty Views Activity**
   - Name: `Billnet Admin`
   - Package name: `com.billnet.admin`
   - Language: **Kotlin**
   - Minimum SDK: **24**

2. **Salin file** dari folder ini ke project Anda:
   - `app/build.gradle.kts` → timpa
   - `settings.gradle.kts` → timpa
   - `build.gradle.kts` → timpa
   - `gradle.properties` → timpa
   - `app/src/main/AndroidManifest.xml` → timpa
   - `app/src/main/java/com/billnet/admin/*.kt` → copy
   - `app/src/main/res/layout/activity_main.xml` → timpa
   - `app/src/main/res/values/strings.xml` → timpa
   - `app/src/main/res/values/themes.xml` → timpa
   - `app/src/main/res/values/colors.xml` → timpa

3. **Ganti URL backend** di `MainActivity.kt`:
   ```kotlin
   const val BASE_URL = "https://APP_URL_ANDA.test"   // sesuaikan domain produksi
   ```

4. **Firebase**:
   - Tambahkan app Android dengan package `com.billnet.admin` pada project Firebase yang sama.
   - Unduh `google-services.json` → letakkan di folder `app/` project.

5. **Sync & jalankan**: Gradle Sync, lalu Run.

> Pastikan Laravel sudah **online + HTTPS**.

## Izin & Fitur WebView

Aplikasi ini meminta izin berikut agar fitur web berjalan penuh:

| Izin | Kegunaan |
|---|---|
| `INTERNET` / `ACCESS_NETWORK_STATE` | Memuat dashboard |
| `POST_NOTIFICATIONS` | Push notification FCM (Android 13+) |
| `ACCESS_FINE_LOCATION` / `ACCESS_COARSE_LOCATION` | Peta & "Gunakan Lokasi Saya" |
| `CAMERA` | **Ambil foto KTP** dari kamera (`getUserMedia`) di form customer |
| `WRITE_EXTERNAL_STORAGE` (≤ API 28) | Unduh file (invoice, export Excel) |

Catatan teknis:

- `WebChromeClient.onPermissionRequest()` meng-grant `RESOURCE_VIDEO_CAPTURE` sehingga `getUserMedia` (modal kamera) bekerja. Izin `CAMERA` diminta saat runtime melalui `ActivityResultContracts.RequestPermission()`.
- `settings.mediaPlaybackRequiresUserGesture = false` agar preview video kamera bisa autoplay.
- `onShowFileChooser()` menangani `<input type="file">` (tombol "Pilih File").
- Wajib **HTTPS** (`usesCleartextTraffic="false"`), karena `getUserMedia` memerlukan secure context.

## Alur kerja

- Aplikasi membuka `BASE_URL` → login admin (session cookie WebView).
- Setelah halaman termuat & cookie session ada, `FcmTokenRegistrar` mengirim token FCM ke `POST BASE_URL/mobile/admin/device-token`.
- Laravel mengirim push ke admin: pembayaran baru diterima.