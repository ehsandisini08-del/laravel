## Critical Bug Fix: Edit Customer Membuat PPP Secret Baru di MikroTik, Bukan Mengupdate Secret Lama

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, **Senior System Architect**, dan **Network Engineer** yang berpengalaman dengan MikroTik RouterOS API.

Terdapat bug kritis pada proses **Edit Customer**.

---

# Kondisi Saat Ini

Misal data awal:

Customer:

Nama:
Said

PPP Username:
said

PPP Password:
123

Di MikroTik terdapat:

PPP Secret:

- Username : said
- Password : 123

Ketika Customer diedit menjadi:

PPP Username:
said123

PPP Password:
321

Hasil yang terjadi:

Database Laravel:

✔ Username berubah menjadi:

said123

✔ Password berubah menjadi:

321

Namun di MikroTik:

Secret lama:

said

masih ada

dan dibuat Secret baru:

said123

Akibatnya terdapat dua PPP Secret:

- said
- said123

Padahal seharusnya hanya ada satu.

Ini menyebabkan duplicate account, data tidak sinkron, dan pelanggan dapat memiliki lebih dari satu akun PPPoE.

---

# Expected Behavior

Saat mengubah PPP Username atau PPP Password:

Aplikasi **WAJIB melakukan UPDATE terhadap PPP Secret yang sudah ada**, bukan membuat Secret baru.

Hasil akhir yang diharapkan:

Database:

Username

said123

Password

321

MikroTik:

Hanya terdapat:

Username

said123

Password

321

Secret lama:

said

harus berubah menjadi

said123

Bukan membuat Secret baru.

---

# Root Cause yang Harus Dicek

Periksa implementasi pada:

- CustomerService
- PPPSecretService
- MikrotikService

Kemungkinan saat ini aplikasi memanggil:

/ppp/secret/add

atau

createSecret()

setiap proses Edit.

Hal ini salah.

---

# Implementasi yang Benar

Jika Customer sudah memiliki:

- ppp_secret_id
- mikrotik_id

maka proses Edit **WAJIB** menggunakan:

RouterOS

/ppp/secret/set

atau method:

updateSecret()

Bukan:

/ppp/secret/add

atau

createSecret()

---

# Gunakan mikrotik_id Sebagai Primary Identifier

Jangan mencari Secret berdasarkan Username.

Karena Username dapat berubah.

Gunakan:

ppp_secrets.mikrotik_id

↓

RouterOS .id

↓

/ppp/secret/set

Contoh:

Database

mikrotik_id

*5

↓

RouterOS

set

.id=*5

name=said123

password=321

Bukan:

add

name=said123

---

# Flow Edit yang Benar

1.

Ambil Customer.

↓

2.

Ambil PPP Secret lokal.

↓

3.

Ambil mikrotik_id.

↓

4.

Hubungkan ke Router.

↓

5.

Update Secret menggunakan:

.id = mikrotik_id

↓

6.

Jika berhasil:

Update database.

↓

Commit.

---

# Jangan Pernah Membuat Secret Baru Jika

Customer sudah memiliki:

- ppp_secret_id
- mikrotik_id

Logika yang benar:

IF

ppp_secret_id != null

AND

mikrotik_id != null

↓

UPDATE

ELSE

CREATE

---

# Tambahkan Validasi Sebelum Create

Sebelum memanggil:

createSecret()

pastikan:

Customer benar-benar belum memiliki PPP Secret.

Jika sudah ada:

Throw Exception atau langsung gunakan updateSecret().

---

# Sinkronisasi Database

Setelah update berhasil:

Pastikan field berikut ikut diperbarui:

- username
- password
- profile
- comment
- updated_at

Jangan membuat record PPP Secret baru di database.

Gunakan record yang sama.

---

# Validasi MikroTik

Sebelum melakukan Create:

Cek apakah Secret dengan `mikrotik_id` masih ada.

Jika ada:

WAJIB UPDATE.

Jika tidak ada:

Baru lakukan CREATE.

---

# Logging

Catat aktivitas:

Success:

- PPP Secret Updated

Jangan mencatat:

PPP Secret Created

ketika proses berasal dari Edit Customer.

Failure:

- PPP Secret Update Failed

Log harus berisi:

- Customer ID
- Customer Name
- Router
- MikroTik ID
- Username Lama
- Username Baru
- Password Lama (jangan tampilkan plaintext, cukup masked atau hash sesuai kebijakan)
- PPP Profile
- Response RouterOS

---

# Database Transaction

Gunakan:

DB::transaction()

Urutan:

Begin Transaction

↓

Update PPP Secret di MikroTik

↓

Update Database

↓

Commit

Jika gagal:

↓

Rollback

---

# Acceptance Criteria

- ✅ Edit Customer tidak pernah memanggil `/ppp/secret/add` jika PPP Secret sudah ada.
- ✅ Menggunakan `/ppp/secret/set` dengan `mikrotik_id`.
- ✅ Secret lama di MikroTik berubah menjadi Username baru.
- ✅ Tidak ada Secret baru yang dibuat saat proses Edit.
- ✅ Setelah Edit hanya ada satu PPP Secret di MikroTik.
- ✅ Database dan MikroTik selalu sinkron.
- ✅ Jika update MikroTik gagal, perubahan database ikut di-rollback.
- ✅ Mendukung Multi Router.
- ✅ Kompatibel dengan Laravel 13 dan arsitektur Service Layer.