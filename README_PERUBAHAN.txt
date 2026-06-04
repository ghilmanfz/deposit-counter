PERUBAHAN FITUR DEPOSIT COUNTER / SISTEM PENITIPAN BARANG

Framework/struktur:
- Project ini adalah aplikasi PHP native dengan MySQL/MariaDB.
- Frontend memakai Bootstrap 3 dan file JS/CSS bawaan di folder libs/.
- Database utama berada di DATABASE FILE/inventorysystem.sql.

Fitur yang ditambahkan/disesuaikan:
1. Hak akses klien tetap read-only untuk data stok, tagihan, surat jalan, dan riwayat stok. Klien hanya bisa membuat Request Pengambilan Barang.
2. Sidebar klien berisi Dashboard, Barang Saya, Riwayat Stok, Tagihan Saya, Surat Jalan, Request Pengambilan Barang.
3. Stok dipisahkan berdasarkan client_id pada products dan ditampilkan pada dashboard admin per client/barang.
4. Menu Satuan Barang ditambahkan untuk admin.
5. Produk/barang titipan memiliki satuan barang.
6. Barang masuk dapat mencatat barang cacat, keterangan cacat, dan banyak foto bukti.
7. Barang cacat tetap masuk ke stok penuh; catatan cacat hanya sebagai informasi.
8. Menu Request Pengambilan Barang ditambahkan.
9. Request klien auto-reject jika jumlah melebihi stok.
10. Admin dapat approve/reject request dengan alasan.
11. Approve request tidak langsung memotong stok. Stok dipotong saat Surat Jalan dicetak/diproses.
12. Billing manual ditambahkan: tambah, edit, hapus, tandai lunas.
13. Semua link delete memiliki popup konfirmasi: “Apakah Anda yakin ingin menghapus data ini?”
14. Backend tetap membatasi akses berdasarkan role/page_require_level dan filter client_id.

Database:
- Aplikasi menjalankan penyesuaian schema otomatis melalui includes/sql.php saat dibuka.
- File upgrade manual juga tersedia: DATABASE FILE/upgrade_warehouse_features.sql
- Jalankan file upgrade tersebut bila server/database tidak mengizinkan ALTER otomatis dari aplikasi.

Cara menjalankan:
1. Extract folder project ke htdocs/www.
2. Buat database sesuai includes/config.php, default: inventorysystem.
3. Import DATABASE FILE/inventorysystem.sql.
4. Jika perlu, import juga DATABASE FILE/upgrade_warehouse_features.sql.
5. Pastikan folder uploads/products, uploads/users, uploads/defects bisa ditulis oleh web server.
6. Buka index.php melalui browser.

Login default:
- admin / admin
- client / client

Catatan testing:
- Semua file PHP sudah dicek dengan php -l dan tidak ada error syntax.
- Pengujian browser/MySQL penuh perlu dilakukan di server lokal Anda karena environment ini tidak menjalankan service MySQL/web server.
