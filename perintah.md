Saya memiliki project Laravel yang sudah berjalan dan menggunakan SQLite sebagai database.

Saya ingin menambahkan fitur FTTH Monitoring ke project yang sudah ada.

PENTING:
- Jangan membuat ulang project Laravel.
- Jangan merusak fitur/menu yang sudah ada.
- Jangan membuat tabel pelanggan baru jika data pelanggan sudah tersedia.
- Integrasikan dengan struktur database, model, controller, route, layout, dan UI yang sudah ada.
- Sebelum melakukan perubahan besar, analisis terlebih dahulu struktur project yang ada.
- Gunakan migration agar perubahan database aman.
- Pertahankan kompatibilitas dengan SQLite.

==================================================
FITUR UTAMA: FTTH MONITORING MAP
==================================================

Buat menu baru:

"FTTH Monitoring"

Halaman utama berupa interactive map menggunakan Leaflet.js.

Map harus menggunakan tampilan SATELIT sehingga area dapat terlihat jelas, termasuk:
- bentuk rumah
- gedung
- jalan
- nama jalan
- nama lokasi
- lingkungan sekitar
- objek geografis lainnya

Prioritaskan penggunaan Google Maps Platform secara resmi untuk imagery Satellite/Hybrid jika memungkinkan dan sesuai dengan API/key serta ketentuan penggunaannya.

Gunakan:
- Leaflet.js sebagai library interactive map
- Google Maps Satellite/Hybrid sebagai sumber imagery melalui metode integrasi yang resmi/diizinkan
- Jangan melakukan scraping atau menggunakan endpoint tile Google yang melanggar ketentuan layanan.

Jika Google Satellite tidak dapat digunakan secara langsung melalui Leaflet karena batasan teknis/lisensi, buat abstraction/provider layer sehingga map provider dapat diganti dengan provider imagery resmi lainnya tanpa mengubah fitur FTTH Monitoring.

==================================================
ARSITEKTUR DATA
==================================================

Data pelanggan SUDAH ADA di project.

JANGAN membuat tabel pelanggan baru.

Gunakan model Pelanggan yang sudah ada.

Struktur hubungan yang diinginkan:

OLT
 |
 └── ODC
      |
      └── ODP
           |
           └── Pelanggan

Untuk tahap awal fokus pada:

ODC
ODP
Pelanggan
Map

==================================================
ODC
==================================================

Buat modul ODC.

Field minimal:

- id
- kode
- nama
- alamat
- latitude
- longitude
- kapasitas
- keterangan
- status
- created_at
- updated_at

Contoh:

ODC-001
ODC Kecamatan A
Kapasitas: 144 Core
Koordinat: latitude, longitude
Status: ACTIVE

ODC harus dapat ditampilkan sebagai marker pada map.

Gunakan icon marker khusus ODC agar berbeda dari ODP dan pelanggan.

==================================================
ODP
==================================================

Buat modul ODP.

Field minimal:

- id
- odc_id
- kode
- nama
- alamat
- latitude
- longitude
- kapasitas
- port_terpakai
- port_available
- status
- keterangan
- created_at
- updated_at

Relasi:

ODP belongsTo ODC
ODC hasMany ODP

ODP harus ditampilkan sebagai marker berbeda dari ODC.

Ketika marker ODP diklik, tampilkan popup/card:

Kode ODP
Nama
ODC
Kapasitas
Port terpakai
Port tersedia
Status

Tambahkan tombol:

"Detail ODP"

==================================================
INTEGRASI PELANGGAN YANG SUDAH ADA
==================================================

Jangan membuat model/table pelanggan baru.

Gunakan model Pelanggan yang sudah ada.

Analisis terlebih dahulu:
- nama tabel pelanggan
- nama model
- primary key
- field status
- field alamat
- field koordinat jika sudah tersedia

Jika pelanggan belum memiliki:

latitude
longitude
odp_id

buat migration untuk menambah field tersebut.

Jika field dengan fungsi yang sama sudah ada dengan nama berbeda, gunakan field yang sudah ada.

Jangan membuat field duplikat.

Hubungkan pelanggan ke ODP:

ODP hasMany Pelanggan
Pelanggan belongsTo ODP

Jika struktur project menggunakan nama/model berbeda, ikuti struktur existing project.

==================================================
KOORDINAT PELANGGAN
==================================================

Pelanggan harus bisa mempunyai:

latitude
longitude

Buat fitur pada form pelanggan:

"Lokasi Pelanggan"

User dapat:
1. memasukkan latitude/longitude
2. memilih lokasi langsung pada map
3. menggeser marker pelanggan
4. menyimpan koordinat

Jika memungkinkan tambahkan reverse geocoding agar alamat/lokasi dapat membantu ditampilkan.

==================================================
MAP
==================================================

Gunakan Leaflet.js.

Map harus memiliki:

1. Satellite view
2. Hybrid view jika provider resmi mendukung
3. Zoom
4. Pan
5. Fullscreen
6. Locate user/current location
7. Search lokasi
8. Marker clustering untuk pelanggan jika jumlah pelanggan banyak
9. Layer control
10. Legend
11. Popup
12. Fit bounds
13. Drawing/editing lokasi jika diperlukan

Layer:

[✓] ODC
[✓] ODP
[✓] Pelanggan
[✓] Jalur Fiber
[ ] Gangguan
[ ] Area Coverage

Buat layer control sehingga masing-masing layer dapat diaktifkan/nonaktifkan.

==================================================
MARKER
==================================================

Buat marker/icon berbeda:

ODC:
🔴 / icon khusus ODC

ODP:
🟠 / icon khusus ODP

Pelanggan aktif:
🟢

Pelanggan gangguan:
🔴

Pelanggan isolir:
🟡

Pelanggan nonaktif:
⚫

Jangan hanya menggunakan emoji jika bisa dibuat icon SVG/HTML yang lebih profesional.

Marker harus mudah dibedakan pada map satelit.

==================================================
POPUP PELANGGAN
==================================================

Ketika pelanggan diklik tampilkan:

Kode pelanggan
Nama
Alamat
Status
ODP
Port
Paket
Latitude
Longitude

Jika data tersebut tersedia di database.

Tambahkan tombol:

"Detail Pelanggan"

Tombol harus membuka halaman pelanggan EXISTING.

Jangan membuat halaman pelanggan baru jika sudah ada.

==================================================
JALUR FIBER
==================================================

Siapkan sistem untuk menggambar jalur fiber pada map.

Hubungan:

ODC → ODP
ODP → Pelanggan

Tampilkan jalur menggunakan Polyline Leaflet.

Contoh:

ODC
 |
 |=================
                   |
                   ODP
                /  |  \
               /   |   \
              C1   C2   C3

Jalur harus mempunyai:

- id
- nama
- tipe kabel
- source_type
- source_id
- destination_type
- destination_id
- geometry
- status
- keterangan
- created_at
- updated_at

Gunakan GeoJSON/LineString untuk geometry.

Untuk SQLite jangan menggunakan fitur database spatial yang membutuhkan PostGIS.

Simpan geometry sebagai JSON/Text jika diperlukan.

==================================================
FITUR DETAIL ODP
==================================================

Ketika user membuka detail ODP:

Tampilkan:

ODP-001
Nama ODP
ODC
Lokasi
Kapasitas
Port:

01 AVAILABLE
02 USED
03 USED
04 USED
05 AVAILABLE
...

Buat visual port.

Contoh:

[01] 🟢
[02] 🔴
[03] 🔴
[04] 🟢
[05] 🔴

Jika pelanggan terhubung ke port tertentu, tampilkan nama pelanggan.

==================================================
DASHBOARD
==================================================

Di bagian atas map buat summary card:

Total ODC
Total ODP
Total Pelanggan
Pelanggan Online
Pelanggan Gangguan
Pelanggan Isolir
Pelanggan Nonaktif

Contoh:

ODC          12
ODP          148
PELANGGAN    2.843

ONLINE       2.710
GANGGUAN        46
ISOLIR          37
NONAKTIF        50

Data harus dihitung dari database secara dinamis.

==================================================
FILTER
==================================================

Tambahkan filter:

ODC
ODP
Status pelanggan
Area
ODP
Kode pelanggan
Nama pelanggan

Search:

"Cari pelanggan, ODP, ODC..."

Ketika hasil dipilih:

- map otomatis berpindah ke lokasi
- zoom ke marker
- buka popup

==================================================
DETAIL ODC
==================================================

Saat ODC diklik:

Tampilkan:

Kode
Nama
Alamat
Kapasitas
Jumlah ODP
Jumlah pelanggan
Status

Tambahkan:

"Daftar ODP"

Contoh:

ODC-001

ODP-001 — 12/16 port
ODP-002 — 14/16 port
ODP-003 — 8/16 port

==================================================
STATUS COLOR
==================================================

Gunakan status yang mudah dipahami:

ACTIVE
WARNING
DOWN
MAINTENANCE
INACTIVE

Gunakan warna visual yang konsisten.

==================================================
API
==================================================

Jika memungkinkan, pisahkan data map melalui API Laravel.

Contoh endpoint:

GET /api/ftth/map
GET /api/ftth/odc
GET /api/ftth/odp
GET /api/ftth/customers

Response map sebaiknya ringan.

Contoh:

{
    "odcs": [],
    "odps": [],
    "customers": [],
    "fibers": []
}

Jangan load seluruh data berat jika tidak diperlukan.

Jika jumlah pelanggan besar, gunakan:
- pagination
- bounding box
- clustering
- lazy loading

==================================================
PERFORMA
==================================================

Project harus tetap cepat walaupun pelanggan mencapai ribuan.

Jangan membuat ribuan DOM marker sekaligus jika Leaflet sudah terlalu berat.

Gunakan marker clustering.

Jika diperlukan:

- load data berdasarkan viewport map
- AJAX/fetch
- clustering
- debounce search
- lazy loading

==================================================
UI/UX
==================================================

Gunakan desain modern dan profesional.

Map menjadi area utama.

Layout:

------------------------------------------------
FTTH MONITORING
------------------------------------------------
[ODC] [ODP] [CUSTOMER] [ONLINE] [GANGGUAN]

[ Search ................................ ]

------------------------------------------------
|                                              |
|                                              |
|                 SATELLITE MAP                |
|                                              |
|                                              |
|                                  [+] [-]     |
------------------------------------------------

Legend:

🔴 ODC
🟠 ODP
🟢 Online
🔴 Gangguan
🟡 Isolir
⚫ Nonaktif

Responsive untuk desktop dan mobile.

Ikuti framework CSS/UI yang SUDAH digunakan project.
Jangan memasukkan framework baru jika project sudah mempunyai UI framework.

==================================================
KEAMANAN
==================================================

Gunakan:

- Laravel validation
- CSRF
- authorization/policy jika project sudah menggunakannya
- route protection
- mass assignment protection
- sanitization output

Jangan expose data sensitif pelanggan ke endpoint publik.

==================================================
DATABASE SQLITE
==================================================

Project menggunakan SQLite.

Semua migration harus kompatibel dengan SQLite.

Jangan menggunakan:

- PostGIS
- PostgreSQL-specific syntax
- MySQL-only syntax

Untuk geometry gunakan TEXT/JSON jika diperlukan.

==================================================
ATURAN PENTING DALAM IMPLEMENTASI
==================================================

Sebelum coding:

1. Analisis struktur folder project.
2. Analisis Laravel version.
3. Analisis database SQLite.
4. Cari model Pelanggan.
5. Cari migration pelanggan.
6. Cari controller pelanggan.
7. Cari route pelanggan.
8. Cari layout/sidebar/menu yang digunakan.
9. Identifikasi UI framework yang digunakan.
10. Identifikasi apakah project sudah menggunakan Vite/Alpine/Livewire/Inertia/Vue/React.
11. Ikuti pola coding yang sudah ada.

Jangan mengganti arsitektur existing project tanpa alasan.

Jika ada field pelanggan yang sudah tersedia, gunakan field tersebut.

Jika ada fitur pelanggan yang sudah ada, integrasikan.
Jangan duplikasi.

==================================================
OUTPUT YANG SAYA INGINKAN
==================================================

Setelah menganalisis project, jelaskan:

1. Struktur existing project.
2. Tabel pelanggan yang ditemukan.
3. Model pelanggan yang digunakan.
4. Field yang perlu ditambahkan.
5. Migration yang akan dibuat.
6. Model ODC.
7. Model ODP.
8. Relasi database.
9. Controller.
10. Route.
11. API.
12. Blade/component/frontend.
13. Integrasi Leaflet.
14. Integrasi satellite imagery.
15. Marker.
16. Popup.
17. Filter.
18. Clustering.
19. Fiber line.
20. Dashboard.

Kemudian implementasikan semuanya secara bertahap.

Jangan memberikan kode fiktif yang tidak sesuai dengan struktur project saya.

Jika terdapat informasi yang belum diketahui, periksa source code project terlebih dahulu sebelum membuat asumsi.

TUJUAN AKHIR:

Saya ingin memiliki sistem FTTH Monitoring berbasis map seperti GIS sederhana:

ODC → ODP → Pelanggan

semuanya terlihat pada map satellite, lengkap dengan lokasi, nama jalan, bangunan/rumah, marker perangkat, status pelanggan, jalur fiber, detail ODC/ODP, dan integrasi langsung dengan menu pelanggan yang sudah ada.