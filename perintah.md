## Integrasi Payment Gateway dan Auto Isolir

Integrasikan fitur **Payment Gateway** dengan sistem billing.

### Alur Pembayaran

* Ketika pelanggan berhasil melakukan pembayaran melalui Payment Gateway, status invoice harus otomatis berubah menjadi **Sudah Dibayar**.
* Gunakan **Bahasa Indonesia** untuk seluruh tampilan dan status pembayaran.

### Auto Aktif Setelah Pembayaran

* Jika pelanggan sedang dalam kondisi **terisolir (internet terputus)** karena menunggak, maka setelah pembayaran berhasil diverifikasi, sistem harus **otomatis mengaktifkan kembali layanan internet** tanpa perlu tindakan manual dari admin.

### Pembayaran Manual oleh Admin

* Pada halaman **Invoice**, tambahkan tombol **Bayar** pada kolom aksi.
* Tombol ini digunakan oleh admin untuk menandai invoice sebagai **Sudah Dibayar** jika pelanggan melakukan pembayaran secara **tunai (cash)** atau transfer yang diverifikasi secara manual.

### Informasi Metode Pembayaran

Tambahkan kolom **Metode Pembayaran** pada tabel invoice yang menampilkan sumber pembayaran, contohnya:

* **Cash** (dibayar langsung kepada admin)
* **Virtual Account BCA**
* **Virtual Account BRI**
* **Virtual Account Mandiri**
* **QRIS**
* **E-Wallet** (GoPay, OVO, DANA, ShopeePay, dll.)
* Metode lain yang disediakan oleh Payment Gateway.

Dengan demikian, admin dapat mengetahui apakah invoice dibayar secara manual (cash) atau melalui Payment Gateway beserta metode pembayaran yang digunakan.
