## Improvement: Hapus Kolom Profile pada Modul Active Connection

Bertindaklah sebagai **Senior Laravel Developer**, **Senior Backend Engineer**, dan **Network Engineer** yang berpengalaman dengan MikroTik RouterOS API.

Lakukan perubahan pada modul **Active Connection (PPP Active)**.

### Perubahan yang Diminta

Hapus kolom **Profile** dari halaman **Active Connection**.

### Alasan

Kolom **Profile** tidak diperlukan pada modul Active Connection karena informasi tersebut sudah tersedia pada modul PPP Secret dan Customer. Menghapus kolom ini akan membuat tampilan lebih sederhana dan mengurangi data yang tidak diperlukan pada monitoring koneksi aktif.

### Yang Harus Diubah

- Hapus kolom **Profile** dari tabel Active Connection.
- Hapus field **Profile** dari halaman Detail Active Connection.
- Hapus seluruh mapping, query, atau transformasi data yang hanya digunakan untuk menampilkan Profile.
- Sesuaikan lebar tabel agar tampilan tetap rapi setelah kolom dihapus.
- Pastikan fitur Search, Refresh, Auto Refresh, Disconnect User, dan Bulk Disconnect tetap berjalan normal.

### Hal yang Perlu Dicek

- Perbarui Blade/View agar tidak lagi merender kolom Profile.
- Perbarui Controller/Service jika masih mengirim field `profile` yang sudah tidak digunakan.
- Hapus kode JavaScript atau Alpine.js yang berkaitan dengan kolom Profile.
- Pastikan tidak ada error JavaScript maupun PHP setelah kolom dihapus.

### Acceptance Criteria

- ✅ Kolom Profile tidak lagi tampil pada halaman Active Connection.
- ✅ Detail Active Connection tidak lagi menampilkan Profile.
- ✅ Tidak ada referensi field `profile` yang tidak digunakan.
- ✅ Layout tabel tetap rapi dan responsif.
- ✅ Seluruh fitur Active Connection tetap berfungsi dengan baik.
- ✅ Tetap kompatibel dengan Multi Router.