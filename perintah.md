Saya ingin kamu memperbaiki sistem navigasi pada aplikasi Android yang menggunakan **WebView**.

### Masalah yang terjadi

Saat ini history navigasi WebView masih bermasalah.

Contoh alurnya:

**Beranda → Menu A → Menu B → Menu C → kembali ke Beranda**

Ketika pengguna sudah berada di Beranda dan menekan tombol **Back fisik pada HP**, aplikasi tidak langsung keluar. Aplikasi justru kembali ke Menu C, kemudian Menu B, Menu A, dan seterusnya sesuai history WebView.

Saya tidak ingin perilaku seperti itu.

### Masalah kedua: Logout

Contoh alurnya:

**Beranda → Menu A → Menu B → Logout → Login**

Setelah logout, pengguna sudah berada di halaman Login.

Namun ketika pengguna menekan tombol **Back fisik pada HP**, aplikasi malah kembali ke halaman Beranda atau halaman yang sebelumnya sudah dibuka.

Ini tidak boleh terjadi karena pengguna sudah logout.

### Yang harus diperbaiki

Terapkan sistem navigasi Back yang benar dan profesional.

#### 1. Tombol Back di Beranda

Jika pengguna sedang berada di halaman **Beranda/root page**, ketika tombol Back HP ditekan:

* Jangan kembali ke halaman/menu sebelumnya dari history WebView.
* Jangan membuka kembali halaman yang sudah pernah dikunjungi.
* Biarkan aplikasi mengikuti perilaku Android yang normal untuk keluar/minimize dari activity.
* Pastikan history halaman lain tidak menyebabkan tombol Back harus ditekan berkali-kali.

#### 2. Navigasi di dalam WebView

Jika pengguna berada di halaman selain Beranda dan halaman tersebut memang memiliki history navigasi yang valid, tombol Back boleh digunakan untuk kembali ke halaman sebelumnya.

Contoh:

**Beranda → Menu A → Menu B**

Jika berada di Menu B dan menekan Back:
**Menu B → Menu A**

Jika kembali sampai ke Beranda:
**Menu A → Beranda**

Kemudian jika menekan Back lagi di Beranda, jangan kembali lagi ke Menu A. Aplikasi harus mengikuti perilaku Back Android normal.

#### 3. Setelah Logout

Ketika pengguna melakukan Logout:

* Hapus/reset history WebView yang berkaitan dengan sesi pengguna sebelumnya.
* Jangan biarkan halaman Beranda atau halaman authenticated lainnya tetap berada di back stack.
* Setelah logout, arahkan pengguna ke halaman Login.
* Setelah berada di Login, tombol Back HP tidak boleh membawa pengguna kembali ke Beranda atau halaman authenticated sebelumnya.
* Pastikan pengguna yang sudah logout tidak dapat kembali ke halaman authenticated hanya dengan menekan tombol Back.

#### 4. Login kembali

Setelah pengguna login kembali, buat history/navigation state yang baru.

Jangan membawa history dari sesi login sebelumnya.

### Hal penting

Sebelum mengubah kode:

1. Periksa terlebih dahulu struktur project.
2. Identifikasi file Activity/WebView yang menangani navigasi.
3. Identifikasi bagaimana URL/page navigation saat ini dikelola.
4. Identifikasi implementasi `OnBackPressed`, `OnBackPressedDispatcher`, WebView history, dan proses logout.
5. Jangan melakukan perubahan yang tidak diperlukan.
6. Jangan menghapus fitur yang sudah berjalan.
7. Pertahankan seluruh fungsi WebView yang sudah ada.

### Target perilaku

Saya ingin hasil akhirnya seperti ini:

**Sebelum logout:**

Beranda → Menu A → Menu B → Menu C → Beranda

Tekan Back di Beranda → **aplikasi keluar/minimize**, bukan kembali ke Menu C/B/A.

**Saat logout:**

Beranda/Menu → Logout → Login

Tekan Back di Login → **tidak kembali ke Beranda atau halaman authenticated sebelumnya**.

**Setelah login kembali:**

Login → Beranda → Menu A → Menu B

History sesi lama tidak boleh ikut terbawa.

Silakan implementasikan solusi yang paling tepat berdasarkan struktur project yang ada. Setelah selesai, jelaskan file apa saja yang diubah dan bagaimana mekanisme Back Navigation yang kamu terapkan.
