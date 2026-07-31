```text
## Bug Fix: Kolom Identity dan Version Tidak Tampil pada Modul Router

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, dan **Network Engineer** yang berpengalaman dengan MikroTik RouterOS API.

Terdapat bug pada modul **Router (Multi Router)**.

### Kondisi Saat Ini

Pada halaman **Router List**, kolom **Identity** dan **Version** tidak menampilkan data (kosong), meskipun router berhasil terhubung dan status koneksi menunjukkan **Online**.

### Expected Behavior

Setiap Router harus menampilkan:

- Identity (System Identity MikroTik)
- RouterOS Version

Data harus diambil langsung dari MikroTik melalui RouterOS API dan disimpan/disinkronkan ke database agar dapat ditampilkan pada daftar Router.

---

## Sumber Data

Ambil informasi berikut dari RouterOS API:

### Identity

Gunakan:

```

/system/identity/print

```

Field:

```

name

```

Simpan ke kolom:

```

routers.identity

```

---

### Version

Gunakan:

```

/system/resource/print

```

Field:

```

version

```

Simpan ke kolom:

```

routers.version

```

---

## Sinkronisasi

Saat:

- Test Connection
- Create Router
- Update Router
- Refresh Router
- Sync Router Information

Aplikasi harus otomatis mengambil:

- Identity
- Version

Kemudian memperbarui data pada database.

---

## Hal yang Perlu Dicek

- Pastikan RouterOS API memanggil endpoint yang benar.
- Pastikan response API berhasil dimapping ke model Router.
- Pastikan field `identity` dan `version` terdapat pada tabel `routers`.
- Pastikan proses update database berjalan setelah data berhasil diambil dari MikroTik.
- Pastikan Blade/View menggunakan field yang benar (`identity` dan `version`).
- Periksa apakah terdapat accessor, resource, atau transformer yang menghilangkan field tersebut.
- Pastikan proses tetap mendukung Multi Router.

---

## Error Handling

Jika router tidak dapat dihubungi:

- Identity tampil:
  `-`

- Version tampil:
  `-`

- Jangan menyebabkan halaman gagal dimuat.

Catat error pada log untuk kebutuhan debugging.

---

## User Interface

Pada halaman Router, tampilkan kolom:

- Router Name
- Host/IP
- Port
- Identity
- Version
- Status
- Last Connected
- Action

Jika Identity atau Version belum tersedia:

Tampilkan badge:

```

Unknown

```

atau

```

*

```

Jangan biarkan kolom kosong.

---

## Acceptance Criteria

- ✅ Kolom Identity menampilkan System Identity dari MikroTik.
- ✅ Kolom Version menampilkan versi RouterOS.
- ✅ Data otomatis diperbarui saat Test Connection, Create, Update, atau Sync Router.
- ✅ Tidak ada kolom kosong akibat kesalahan mapping.
- ✅ Jika router offline, tampilkan "-" atau "Unknown" tanpa menghasilkan exception.
- ✅ Tetap kompatibel dengan Multi Router.
- ✅ Tidak terjadi penurunan performa yang signifikan.
```
