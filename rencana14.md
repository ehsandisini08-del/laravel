## Critical Bug Fix: Sinkronisasi Edit & Delete Customer dengan PPPoE Secret MikroTik

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, **Senior System Architect**, dan **Network Engineer** yang berpengalaman dengan MikroTik RouterOS API.

Terdapat bug kritis pada sinkronisasi **Customer ↔ PPP Secret MikroTik**.

Saat ini, operasi **Edit** dan **Delete** hanya mengubah data di database Laravel (Billing System), tetapi **tidak melakukan perubahan pada MikroTik**, sehingga data menjadi tidak sinkron.

Seluruh perbaikan harus mengikuti arsitektur **database-first**, menggunakan **Service Layer**, **Database Transaction**, dan mendukung **Multi Router**.

---

# Bug 1: Edit Customer Tidak Mengubah PPP Secret di MikroTik

## Kondisi Saat Ini

Ketika mengubah data Customer, khususnya:

- PPP Username (Secret)
- PPP Password

Hasilnya:

- ✅ Data berhasil diperbarui di database Laravel.
- ❌ PPP Secret pada MikroTik masih menggunakan Username dan Password lama.
- ❌ Tidak ada notifikasi bahwa sinkronisasi ke MikroTik gagal.

Akibatnya, pelanggan tidak dapat login menggunakan data terbaru.

---

# Expected Behavior

Jika Customer memiliki PPP Secret yang sudah tersinkron ke MikroTik, maka setiap perubahan berikut:

- PPP Username
- PPP Password
- Router
- Package
- PPP Profile

harus otomatis memperbarui PPP Secret pada MikroTik.

Urutan proses:

1. Validasi data.
2. Ambil data Customer beserta relasi:
   - Router
   - PPP Secret
   - Package
   - PPP Profile
3. Hubungkan ke Router MikroTik.
4. Cari PPP Secret menggunakan **mikrotik_id**.
5. Update PPP Secret di MikroTik.
6. Jika berhasil:
   - Update database Laravel.
   - Update relasi jika diperlukan.
7. Commit Transaction.
8. Tampilkan Toast Success.

---

# Jangan Mencari Berdasarkan Username

Pastikan proses update menggunakan:

`ppp_secrets.mikrotik_id`

atau `.id` dari RouterOS.

Jangan mencari Secret berdasarkan Username, karena Username bisa berubah.

Alur yang benar:

Database

↓

ppp_secrets.mikrotik_id

↓

/ppp/secret/set

---

# Data yang Harus Diupdate

Pastikan field berikut dikirim ke MikroTik:

- name
- password
- profile
- disabled
- comment (opsional)

Jika field tidak berubah, tidak perlu dikirim.

---

# Bug 2: Delete Customer Tidak Menghapus PPP Secret di MikroTik

## Kondisi Saat Ini

Saat Customer dihapus:

- ✅ Customer berhasil dihapus dari database.
- ✅ PPP Secret lokal berhasil dihapus.
- ❌ PPP Secret masih tetap ada di MikroTik.

Akibatnya muncul orphan PPP Secret di Router.

---

# Expected Behavior

Saat Customer dihapus:

1. Hubungkan ke Router.
2. Cari PPP Secret berdasarkan `mikrotik_id`.
3. Hapus PPP Secret dari MikroTik.
4. Pastikan RouterOS mengembalikan response berhasil.
5. Hapus data `ppp_secrets` di database.
6. Hapus Customer.
7. Commit Transaction.
8. Toast Success.

---

# Jika PPP Secret Tidak Ditemukan di MikroTik

Jangan menghentikan proses.

Lakukan:

- Catat warning.
- Hapus data lokal.
- Lanjutkan proses delete.

---

# Jika Router Offline

Jangan langsung menghapus Customer.

Rollback transaksi.

Tampilkan pesan:

"Gagal menghapus PPP Secret dari MikroTik karena router tidak dapat dihubungi."

Hal ini untuk menjaga sinkronisasi data.

---

# Database Transaction

Seluruh proses Edit dan Delete wajib menggunakan:

DB::transaction()

Urutan:

Begin Transaction

↓

Operasi RouterOS API

↓

Update/Delete Database

↓

Commit

Jika terjadi exception:

↓

Rollback

---

# Validasi Response RouterOS

Jangan menganggap request berhasil hanya karena API berhasil dipanggil.

Pastikan:

- Response tidak mengandung error.
- Object masih ada setelah update.
- Secret benar-benar hilang setelah delete (jika memungkinkan lakukan verifikasi).

Jika gagal:

Throw Exception.

---

# Service Layer

Pastikan seluruh komunikasi RouterOS hanya berada di:

app/Services/Mikrotik/

Contoh:

- PPPSecretService
- RouterConnectionService

CustomerService hanya memanggil service tersebut.

Controller tidak boleh berkomunikasi langsung dengan RouterOS API.

---

# Logging

Catat aktivitas berikut:

Success:

- Customer Updated
- PPP Secret Updated
- Customer Deleted
- PPP Secret Deleted

Failure:

- PPP Secret Update Failed
- PPP Secret Delete Failed

Log minimal berisi:

- Customer ID
- Customer Name
- Router ID
- Router Name
- PPP Username Lama
- PPP Username Baru
- PPP Profile
- MikroTik ID
- Pesan Error RouterOS

Gunakan Laravel Activity Log dan Laravel Log.

---

# Error Handling

Tangani dengan baik:

- Router Offline
- Authentication Failed
- API Disabled
- Connection Timeout
- PPP Secret tidak ditemukan
- MikroTik ID kosong
- MikroTik ID tidak valid
- PPP Profile tidak ditemukan
- Validation Error

Semua error harus ditampilkan melalui Toast Notification yang mudah dipahami.

Jangan menampilkan halaman Exception.

---

# User Experience

Saat Edit/Delete:

- Disable tombol selama proses berlangsung.
- Tampilkan Loading Button.
- Cegah double submit.

Jika berhasil:

- Toast Success.
- Redirect ke halaman Customer List atau Detail.

Jika gagal:

- Data tetap seperti semula (rollback).
- Tampilkan pesan error yang jelas.

---

# Hal yang Perlu Dicek

Periksa implementasi pada:

- CustomerController
- CustomerService
- PPPSecretService
- RouterConnectionService
- Mikrotik API Service

Pastikan:

- Menggunakan `mikrotik_id` sebagai identifier utama.
- Tidak melakukan update/delete hanya pada database.
- Database baru diubah setelah operasi RouterOS berhasil.
- Seluruh operasi menggunakan Database Transaction.

---

# Acceptance Criteria

- ✅ Edit PPP Username langsung memperbarui Username di MikroTik.
- ✅ Edit PPP Password langsung memperbarui Password di MikroTik.
- ✅ Jika Package berubah, PPP Profile di MikroTik ikut diperbarui.
- ✅ Delete Customer menghapus PPP Secret di MikroTik sebelum menghapus data di database.
- ✅ Tidak ada orphan PPP Secret di MikroTik.
- ✅ Menggunakan `mikrotik_id` untuk update dan delete.
- ✅ Seluruh proses menggunakan Database Transaction.
- ✅ Rollback jika operasi RouterOS gagal.
- ✅ Mendukung Multi Router.
- ✅ Kompatibel dengan Laravel 13 dan arsitektur Service Layer.