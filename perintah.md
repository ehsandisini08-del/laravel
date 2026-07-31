Saya justru akan membuat prompt yang lebih mengarah ke **root cause analysis**, bukan meminta AI langsung mengubah kode. Berdasarkan kode dan log yang Anda kirim, saya ingin AI melakukan audit terhadap lifecycle `Client` dan penggunaan library `evilfreelancer/routeros-api-php`.

# Root Cause Analysis - Multi Router PPP Secret Sync

Bertindaklah sebagai **Senior Laravel Developer**, **Senior PHP Engineer**, dan **Maintainer RouterOS API Client** yang memahami cara kerja package `evilfreelancer/routeros-api-php`.

Jangan langsung memperbaiki kode.

Saya ingin Anda melakukan **Root Cause Analysis** terlebih dahulu sebelum mengubah implementasi.

---

# Masalah

Saya memiliki aplikasi Laravel 13 yang mendukung Multi Router MikroTik.

Kondisi:

Router A

* Test Connection berhasil.
* Sync PPP Secret berhasil.

Router B

* Test Connection berhasil.
* Sync PPP Secret selalu menghasilkan:

```
0 PPP Secret imported
```

Padahal Router B memiliki banyak PPP Secret.

Log menunjukkan:

```
Router connected successfully

Executing /ppp/secret/print count-only

PPP Secret count retrieved
count = 0

Executing /ppp/secret/print

Stream timed out
```

Artinya:

* koneksi berhasil
* login berhasil
* client berhasil dibuat
* query pertama berhasil
* query kedua timeout

---

# Saya ingin Anda mengaudit seluruh lifecycle RouterOS Client

Periksa secara menyeluruh:

* MikrotikService
* RouterConnectionService
* PPPSecretService
* Service Container
* Dependency Injection
* Singleton
* Property Client

Saya ingin memastikan apakah object Client:

* dibuat setiap request
* digunakan ulang
* masih menyimpan koneksi router sebelumnya
* atau mengalami stream yang belum selesai dibaca.

---

# Audit connect()

Periksa implementasi connect().

Pastikan:

* Client benar-benar disimpan ke property class.
* Tidak ada variable lokal yang hilang setelah method selesai.
* getClient() selalu mengembalikan object Client yang valid.
* Tidak pernah menggunakan Client dari router lain.

---

# Audit getClient()

Saya ingin memastikan:

* apakah getClient() mengembalikan Client lama.
* apakah Client disimpan sebagai singleton.
* apakah Client dibuat ulang setiap router berganti.
* apakah Client di-cache.

Jika ada implementasi seperti:

* static Client
* singleton Client
* shared Client
* global Client

maka jelaskan mengapa itu menyebabkan bug pada Multi Router.

---

# Audit PPPSecretService

Periksa method:

getAllSecrets()

Saya melihat implementasi seperti ini:

1.

```
/ppp/secret/print count-only
```

kemudian

2.

```
/ppp/secret/print
```

Saya ingin Anda memverifikasi berdasarkan dokumentasi package `evilfreelancer/routeros-api-php` apakah pola tersebut aman.

Periksa apakah:

* stream sudah habis dibaca
* stream perlu ditutup
* stream perlu dibuat ulang
* query kedua harus menggunakan client baru
* atau count-only memang tidak boleh digunakan sebelum print.

Jika count-only tidak diperlukan, hapus seluruh implementasinya.

Gunakan:

```
$response = $client
    ->query(new Query('/ppp/secret/print'))
    ->read();

$count = count($response);
```

---

# Audit Timeout

Saya melihat timeout:

```
Stream timed out
```

Saya ingin memastikan apakah timeout berasal dari:

* jaringan
* RouterOS
* API Client
* stream yang belum selesai
* reuse Client
* atau bug implementasi.

Jangan hanya menambah timeout menjadi lebih besar.

Temukan penyebab sebenarnya.

---

# Audit Multi Router

Pastikan seluruh komunikasi RouterOS menggunakan router yang dipilih.

Controller

↓

PPPSecretService

↓

MikrotikService

↓

Client

↓

Router yang dipilih

Tidak boleh ada:

* Router::first()
* Default Router
* Cache Router
* Session Router
* Reuse Client dari router sebelumnya

---

# Tambahkan Debug Logging

Tambahkan log berikut:

## Saat connect

* Router ID
* Host
* Username
* Object Hash Client

Contoh:

```
spl_object_id($client)
```

atau

```
spl_object_hash($client)
```

---

## Setelah getClient()

Log:

* apakah object sama dengan sebelumnya
* router id
* object hash

---

## Sebelum query

```
/ppp/secret/print
```

Log:

* router id
* client hash

---

## Setelah query

Log:

* jumlah data
* response pertama
* response terakhir

---

# Refactoring

Jika diperlukan lakukan refactoring.

Saya lebih memilih:

```
connect(Router $router): Client
```

dibandingkan:

```
connect(): bool
```

Karena Service seharusnya mengembalikan object Client, bukan boolean.

Jangan mengubah arsitektur secara besar-besaran, tetapi pastikan lifecycle Client menjadi benar.

---

# Output

Saya tidak ingin solusi berdasarkan dugaan.

Saya ingin:

1. Menemukan akar penyebab bug.
2. Menjelaskan mengapa Router A berhasil tetapi Router B gagal.
3. Menjelaskan apakah penyebabnya berasal dari lifecycle Client, stream RouterOS, reuse connection, atau implementasi Multi Router.
4. Menampilkan file yang harus diubah.
5. Menampilkan kode sebelum dan sesudah perubahan.
6. Menjelaskan alasan teknis setiap perubahan.

Pastikan implementasi akhir benar-benar mendukung Multi Router, di mana setiap router memiliki object Client sendiri, koneksi sendiri, dan proses sinkronisasi sendiri tanpa saling berbagi state.

**Satu catatan penting:** dari kode yang Anda kirim, saya juga menyarankan AI untuk **mengubah desain `connect()`**. Daripada `connect(): bool`, jauh lebih baik jika menjadi `connect(Router $router): Client` sehingga setiap operasi (`Test Connection`, `Sync PPP Secret`, `PPP Profile`, dll.) selalu menggunakan instance `Client` yang baru dan terikat pada router yang dipilih. Ini mengurangi risiko berbagi state antar-router dan biasanya menghasilkan implementasi Multi Router yang lebih andal.
