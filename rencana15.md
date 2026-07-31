# Refactor Modul Customer: Ubah Tanggal Isolir Menjadi Hari Isolir Bulanan

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, **Senior System Architect**, dan **UI/UX Developer**.

Saya ingin mengubah konsep **Tanggal Isolir** pada Modul Customer.

Saat ini field **Tanggal Isolir** menggunakan format **tanggal lengkap (YYYY-MM-DD)**.

Konsep tersebut kurang sesuai untuk sistem Billing ISP karena isolir dilakukan secara otomatis setiap bulan pada tanggal tertentu, sama seperti **Tanggal Jatuh Tempo**.

---

# Tujuan

Ubah field **Tanggal Isolir** menjadi **Hari Isolir**.

User hanya memilih angka tanggal dalam satu bulan (1–31), bukan tanggal lengkap.

Contoh:

- 1
- 5
- 10
- 11
- 15
- 20
- 25
- 31

Artinya:

Jika Hari Isolir = **11**, maka pelanggan akan diisolir setiap tanggal **11** pada setiap bulan.

---

# Database

Ubah struktur database.

Sebelumnya:

```text
isolation_date (DATE)
```

Menjadi:

```text
isolation_day (TINYINT UNSIGNED)
```

atau

```text
isolation_day (INTEGER)
```

Range nilai:

1 - 31

Buat migration untuk mengubah struktur tabel tanpa menghilangkan data penting.

Jika terdapat data lama, lakukan migrasi dengan mengambil nilai **DAY()** dari `isolation_date`.

Contoh:

2026-08-11

↓

11

---

# Form Create Customer

Ubah field:

Tanggal Isolir

Menjadi:

Hari Isolir

Gunakan dropdown atau number input dengan pilihan:

1 sampai 31

Tampilkan helper text:

"Hari dalam bulan ketika pelanggan akan diisolir apabila memiliki tunggakan."

---

# Form Edit Customer

Field juga berubah menjadi:

Hari Isolir

Nilai lama harus otomatis terpilih.

---

# Detail Customer

Sebelumnya:

Tanggal Isolir

2026-08-11

Menjadi:

Hari Isolir

11 setiap bulan

atau

Tanggal 11 setiap bulan

---

# List Customer

Jika sebelumnya terdapat kolom:

Tanggal Isolir

Ubah menjadi:

Hari Isolir

Contoh tampilan:

11

atau

Tgl 11

---

# Validasi

Gunakan Form Request Validation.

Rule:

- wajib diisi
- integer
- minimal 1
- maksimal 31

Contoh:

```php
'isolation_day' => ['required', 'integer', 'between:1,31']
```

---

# Business Logic

Seluruh proses billing dan isolir otomatis harus menggunakan field baru:

`isolation_day`

Bukan lagi:

`isolation_date`

Contoh:

Hari ini:

11 Agustus

Customer:

Hari Isolir = 11

↓

Customer masuk daftar isolir.

Bulan berikutnya:

11 September

↓

Customer kembali memenuhi syarat untuk proses isolir jika masih menunggak.

---

# Backward Compatibility

Perbarui seluruh bagian aplikasi yang masih menggunakan:

- isolation_date
- tanggal_isolir

Menjadi:

- isolation_day
- hari_isolir

Meliputi:

- Migration
- Model
- Controller
- Service
- Validation
- Blade View
- API Resource
- Seeder
- Factory
- Filter
- Export
- Import
- Testing

Pastikan tidak ada referensi field lama yang tersisa.

---

# User Interface

Gunakan tampilan yang konsisten dengan field **Tanggal Jatuh Tempo**.

Contoh dropdown:

Hari Isolir

▼ 11

atau Number Input:

[ 11 ]

Tambahkan tooltip:

"Customer akan diisolir setiap tanggal ini apabila masih memiliki tagihan yang belum dibayar."

---

# Acceptance Criteria

- ✅ Field `isolation_date` diganti menjadi `isolation_day`.
- ✅ User hanya memilih angka 1–31.
- ✅ Form Create dan Edit menggunakan field baru.
- ✅ Detail Customer menampilkan "Tanggal 11 setiap bulan".
- ✅ List Customer menampilkan Hari Isolir.
- ✅ Validasi hanya menerima angka 1–31.
- ✅ Seluruh business logic menggunakan `isolation_day`.
- ✅ Data lama berhasil dimigrasikan menggunakan nilai hari dari `isolation_date`.
- ✅ Kompatibel dengan Laravel 13 dan arsitektur Service Layer.
```