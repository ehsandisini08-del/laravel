# Billnet Customer App (Android WebView + FCM)

Aplikasi pelanggan: membungkus portal pelanggan Laravel dalam `WebView` agar terasa seperti aplikasi native, plus push notification (FCM).

## Cara setup di Android Studio

1. **Buat project baru** di Android Studio:
   - Template: **Empty Views Activity**
   - Name: `Billnet Customer`
   - Package name: `com.billnet.customer`
   - Language: **Kotlin**
   - Minimum SDK: **24**

2. **Salin file** dari folder ini ke project Anda:
   - `app/build.gradle.kts` → timpa
   - `settings.gradle.kts` → timpa
   - `build.gradle.kts` → timpa
   - `gradle.properties` → timpa
   - `app/src/main/AndroidManifest.xml` → timpa
   - `app/src/main/java/com/billnet/customer/*.kt` → copy
   - `app/src/main/res/layout/activity_main.xml` → timpa
   - `app/src/main/res/values/strings.xml` → timpa
   - `app/src/main/res/values/themes.xml` → timpa
   - `app/src/main/res/values/colors.xml` → timpa

3. **Ganti URL backend** di `MainActivity.kt`:
   ```kotlin
   const val BASE_URL = "https://APP_URL_ANDA.test"   // sesuaikan domain produksi
   ```

4. **Firebase**:
   - Buat project di [Firebase Console](https://console.firebase.google.com).
   - Tambahkan app Android dengan package `com.billnet.customer`.
   - Unduh `google-services.json` → letakkan di folder `app/` project.
   - Firebase SDK tidak butuh config lain untuk FCM.

5. **Sync & jalankan**: Gradle Sync (butuh internet), lalu Run ke emulator/HP.

> Pastikan Laravel sudah **online + HTTPS**. Login/WebView & FCM tidak bisa dipakai dengan `http://localhost`.

## Alur kerja

- Aplikasi membuka `BASE_URL/portal` → login pelanggan (session cookie disimpan WebView).
- Setelah halaman termuat & cookie session ada, `FcmTokenRegistrar` mengirim token FCM ke `POST BASE_URL/portal/customer/device-token`.
- Laravel mengirim push: tagihan jatuh tempo, pembayaran diterima, isolasi layanan.

## Catatan

- Token dikirim ulang saat token FCM berubah (`onNewToken`) atau saat ada cookie session baru di halaman baru.
- Notifikasi background ditangani sistem; foreground ditangani `MyFirebaseMessagingService`.
