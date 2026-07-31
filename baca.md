# Root Cause Investigation - MikroTik Multi Router PPP Secret (Jangan Menebak, Temukan Penyebabnya)

Bertindaklah sebagai **Senior Laravel Architect**, **Senior PHP Backend Engineer**, **Network Engineer**, dan **Maintainer package evilfreelancer/routeros-api-php**.

Saya tidak ingin Anda melakukan refactoring atau menulis ulang kode terlebih dahulu.

Saya ingin Anda melakukan **investigasi menyeluruh (Root Cause Analysis)** terhadap bug yang terjadi pada implementasi Multi Router MikroTik.

## Fakta yang Sudah Dipastikan

### Router A

* RouterOS 7.20
* Public IP langsung
* Port API Custom (9697)
* Test Connection berhasil
* Sync PPP Secret berhasil

### Router B

* RouterOS 7.7
* Menggunakan Port API Custom
* Sebagian router berada di jaringan lokal
* Sebagian menggunakan Port Forward
* Test Connection berhasil
* Sync PPP Secret selalu menghasilkan:

```text
0 PPP Secret imported
```

Padahal Router B memiliki lebih dari **105 PPP Secret**.

Saya sudah membuktikannya langsung melalui Terminal MikroTik:

```bash
/ppp/secret/print count-only

105
```

dan

```bash
/ppp/secret/print
```

mengembalikan seluruh data PPP Secret dengan normal.

Artinya:

* RouterOS normal.
* Permission user API normal.
* PPP Secret memang ada.
* Masalah berada pada sisi aplikasi Laravel atau penggunaan library RouterOS API.

---

## Log Laravel

Saat proses Sync, log menunjukkan:

```text
Router connected successfully

Client created

Executing /ppp/secret/print count-only

PPP Secret count retrieved

count = 0

Executing /ppp/secret/print

Stream timed out
```

Artinya:

* Koneksi berhasil.
* Login berhasil.
* Client berhasil dibuat.
* Query pertama berjalan.
* Query kedua mengalami timeout.

Saya ingin mengetahui mengapa hal tersebut bisa terjadi.

---

# Yang Harus Dianalisis

Lakukan audit terhadap seluruh lifecycle komunikasi RouterOS.

Periksa secara menyeluruh:

* MikrotikService
* PPPSecretService
* RouterConnectionService
* getClient()
* connect()
* seluruh penggunaan RouterOS Client
* seluruh penggunaan Query()
* Dependency Injection
* Service Container
* Singleton
* Property Client
* Reuse Connection

---

# Audit connect()

Saat ini implementasi menggunakan:

```php
connect(): bool
```

Saya ingin Anda mengevaluasi apakah desain tersebut sudah benar.

Periksa apakah:

* object Client benar-benar disimpan.
* object Client hilang setelah method selesai.
* connect() membuat Client baru tetapi tidak pernah digunakan.
* getClient() mengambil object yang salah.
* object Client berasal dari router sebelumnya.
* object Client digunakan ulang.

---

# Audit getClient()

Lakukan audit terhadap implementasi:

```php
getClient()
```

Pastikan:

* selalu mengembalikan object Client yang valid.
* tidak menggunakan singleton.
* tidak menggunakan static property.
* tidak menggunakan cache.
* tidak menggunakan object Client dari router lain.

Jika menemukan implementasi seperti:

```php
protected Client $client;
```

atau

```php
private static Client $client;
```

atau reuse object Client,

jelaskan mengapa hal tersebut dapat menyebabkan bug pada implementasi Multi Router.

---

# Audit PPPSecretService

Saya melihat implementasi berikut:

Langkah 1

```php
/ppp/secret/print count-only
```

Langkah 2

```php
/ppp/secret/print
```

Saya ingin Anda memverifikasi berdasarkan dokumentasi package `evilfreelancer/routeros-api-php` apakah dua query tersebut aman dijalankan menggunakan object Client yang sama.

Periksa apakah:

* stream RouterOS masih terbuka,
* stream belum selesai dibaca,
* stream perlu di-reset,
* stream perlu reconnect,
* atau terdapat bug pada library ketika menjalankan dua query berurutan.

Jika memang tidak diperlukan, hapus seluruh penggunaan `count-only`.

Gunakan cukup:

```php
$response = $client
    ->query(new Query('/ppp/secret/print'))
    ->read();

$count = count($response);
```

---

# Audit Stream Timeout

Saya tidak ingin solusi seperti:

* menambah timeout,
* sleep(),
* retry lebih banyak.

Saya ingin mengetahui penyebab sebenarnya dari:

```text
Stream timed out
```

Analisis apakah timeout berasal dari:

* reuse object Client,
* stream belum selesai,
* query count-only,
* bug library,
* atau implementasi aplikasi.

---

# Architecture Review

Saya ingin melakukan perbaikan arsitektur.

Evaluasi apakah desain berikut lebih tepat.

Daripada:

```php
connect(): bool
```

gunakan:

```php
connect(Router $router): Client
```

Sehingga setiap operasi akan memperoleh object Client yang benar sesuai router yang dipilih.

Namun saya **tidak ingin membuat login/logout berkali-kali untuk setiap query**.

Saya ingin menerapkan pola:

## One Client Per HTTP Request

Contoh:

```text
HTTP Request
        │
        ▼
connect(router)
        │
        ▼
Client
        │
        ├── Get Identity
        ├── PPP Profile
        ├── PPP Secret
        ├── Active Connection
        └── Save Database
        │
        ▼
Request selesai
```

Bukan:

```text
Connect

Disconnect

Connect

Disconnect

Connect

Disconnect
```

Karena saya melihat di log MikroTik terjadi login/logout yang sangat cepat dan berulang.

Saya ingin koneksi cukup dibuat **sekali** dalam satu proses sinkronisasi, kemudian digunakan bersama oleh seluruh operasi pada router tersebut.

---

# Debugging yang Wajib Ditambahkan

Tambahkan logging berikut:

Saat connect

* Router ID
* Router Name
* Host
* Port
* Username
* Object Hash Client (`spl_object_id()` atau `spl_object_hash()`)

Saat getClient()

* Router ID
* Object Hash Client

Sebelum query

* Router ID
* Nama Query
* Object Hash Client

Sesudah query

* Total data diterima
* Data pertama
* Data terakhir

Jika timeout

* Query terakhir yang dijalankan
* Object Hash Client
* Router ID
* Host
* Stack Trace

---

# Validasi

Saya ingin AI membuktikan secara teknis mengapa:

* Router A berhasil.
* Router B timeout.
* RouterOS Terminal berhasil.
* Laravel gagal.

Jangan memberikan dugaan.

Lakukan audit terhadap implementasi dan tunjukkan file yang menyebabkan bug.

Untuk setiap bug yang ditemukan:

1. Jelaskan akar penyebabnya.
2. Tampilkan file yang bermasalah.
3. Tampilkan kode sebelum dan sesudah.
4. Jelaskan alasan teknis perubahan.
5. Pastikan implementasi akhir benar-benar mendukung Multi Router tanpa login/logout berulang dan tanpa berbagi state antar router.

Jangan berhenti sampai akar penyebab bug ditemukan.
