# Sistem Penitipan Barang

Aplikasi web berbasis PHP native dan MySQL/MariaDB untuk mengelola penitipan barang di gudang. Sistem ini mencatat barang masuk, stok per klien, riwayat mutasi, request pengambilan barang, surat jalan, tagihan, dan laporan operasional.

Proyek ini dikembangkan dari struktur OSWA-INV dan disesuaikan menjadi sistem penitipan barang dengan akses internal dan akses klien.

## Ringkasan

- Tipe aplikasi: PHP native
- Database: MySQL/MariaDB
- Frontend: Bootstrap 3 dan aset lokal di folder `libs/`
- Nama database bawaan: `inventorysystem`
- File dump database: `DATABASE FILE/inventorysystem.sql`
- Konfigurasi koneksi: `includes/config.php`

## Fitur Utama

- Landing page dan portal login.
- Dashboard internal untuk admin/staf dan dashboard khusus klien.
- Manajemen user, grup, dan hak akses.
- Manajemen kategori dan satuan barang.
- Manajemen barang titipan per klien.
- Pencatatan detail barang masuk, termasuk nomor surat jalan, batch, grade, ukuran, volume, dan scan surat jalan.
- Riwayat stok masuk, keluar, dan penyesuaian.
- Pencatatan barang cacat beserta foto bukti.
- Request pengambilan barang dari klien.
- Approval/reject request pengambilan oleh admin.
- Pemrosesan stok saat surat jalan pengambilan dicetak/diproses.
- Penagihan/invoice klien, jatuh tempo, status pembayaran, dan cetak invoice.
- Surat jalan barang masuk dan barang keluar.
- Laporan pengambilan harian, bulanan, dan periode tertentu.
- Pembatasan data klien berdasarkan `client_id`.

## Hak Akses

| Level | Role | Keterangan |
| --- | --- | --- |
| 1 | Admin | Akses penuh ke user, kategori, satuan, barang, transaksi, tagihan, surat jalan, dan laporan. |
| 2 | Special | Akses operasional lanjutan sesuai menu yang diizinkan. |
| 3 | User/Staff | Akses operasional gudang dan laporan. |
| 4 | Client | Akses read-only untuk barang, stok, tagihan, surat jalan, dan dapat membuat request pengambilan. |

## Kebutuhan Sistem

- Web server lokal seperti Laragon, XAMPP, atau WAMP.
- PHP dengan ekstensi `mysqli`.
- MySQL atau MariaDB.
- phpMyAdmin atau tool database lain untuk import file SQL.
- Browser modern.

Catatan versi: catatan proyek awal merekomendasikan PHP `5.6.3`, sedangkan dump database terbaru dibuat dari lingkungan PHP `8.2.12` dan MariaDB `10.4.32`. Jika memakai PHP versi baru, pastikan konfigurasi error reporting dan ekstensi `mysqli` sudah aktif.

## Instalasi Lokal

1. Salin folder proyek ke direktori web server.

   Contoh Laragon:

   ```text
   C:/laragon/www/InventorySystem_PHP
   ```

2. Jalankan Apache/Nginx dan MySQL/MariaDB.

3. Buat database baru:

   ```sql
   CREATE DATABASE inventorysystem;
   ```

4. Import file SQL berikut ke database tersebut:

   ```text
   DATABASE FILE/inventorysystem.sql
   ```

5. Periksa konfigurasi database di `includes/config.php`.

   Konfigurasi bawaan:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'inventorysystem');
   ```

6. Pastikan folder upload dapat ditulis oleh web server:

   ```text
   uploads/products
   uploads/users
   uploads/defects
   uploads/surat_jalan
   ```

7. Buka aplikasi dari browser:

   ```text
   http://localhost/InventorySystem_PHP/
   ```

## Akun Login Default

| Role | Username | Password |
| --- | --- | --- |
| Admin | `admin` | `admin` |
| Special User | `special` | `special` |
| User/Employee | `user` | `user` |
| Client | `client` | `client` |

Segera ubah password akun default setelah aplikasi dipakai di lingkungan selain lokal.

## Catatan Database

Beberapa tabel penting:

- `users`: data akun, role, status, dan tarif penyimpanan khusus klien.
- `user_groups`: level akses aplikasi.
- `products`: data barang titipan, pemilik barang, satuan, stok, dan detail surat jalan masuk.
- `units`: daftar satuan barang.
- `stock_movements`: riwayat stok masuk, keluar, dan penyesuaian.
- `product_defects`: catatan barang cacat.
- `product_defect_photos`: foto bukti barang cacat.
- `pickup_requests`: request pengambilan barang dari klien.
- `delivery_orders`: surat jalan barang masuk/keluar.
- `billings`: invoice dan status pembayaran klien.
- `withdrawals`: catatan transaksi pengambilan barang.
- `app_settings`: pengaturan aplikasi, termasuk tarif penyimpanan default.

Aplikasi juga memiliki penyesuaian schema otomatis di `includes/sql.php`. Saat aplikasi dibuka, fungsi terkait akan membuat atau melengkapi tabel/kolom fitur warehouse jika belum tersedia. Jika server database membatasi operasi `ALTER TABLE`, lakukan penyesuaian schema secara manual dari dump SQL yang sudah tersedia.

## Struktur Direktori

```text
admin/           Halaman dan aset pendukung admin lama
DATABASE FILE/   File dump database
fungsi/          File fungsi tambahan
includes/        Konfigurasi, koneksi database, helper, session, upload, dan query
layouts/         Header, footer, dan menu berdasarkan role
libs/            CSS, JavaScript, font, gambar, dan plugin frontend
uploads/         File upload produk, user, defect, dan surat jalan
```

## Alur Singkat Penggunaan

1. Admin membuat atau memeriksa user klien.
2. Admin menambahkan kategori dan satuan barang jika diperlukan.
3. Admin menambahkan barang titipan dan memilih klien pemilik barang.
4. Sistem mencatat stok awal, riwayat stok masuk, dan surat jalan masuk.
5. Klien dapat melihat barang, stok, tagihan, surat jalan, dan mengajukan request pengambilan.
6. Admin menyetujui atau menolak request pengambilan.
7. Surat jalan pengambilan diproses, stok berkurang, dan status request diperbarui.
8. Admin mengelola billing/invoice dan laporan pengambilan.

## Troubleshooting

- Jika muncul pesan gagal memilih database, pastikan nama database di `includes/config.php` sama dengan database yang dibuat.
- Jika gambar atau file tidak bisa diupload, pastikan folder di dalam `uploads/` dapat ditulis oleh web server.
- Jika fitur baru tidak muncul setelah import database lama, pastikan schema otomatis di `includes/sql.php` berhasil berjalan atau lengkapi schema dari dump terbaru.
- Jika login gagal, pastikan database sudah berhasil diimport dan akun default tersedia di tabel `users`.

## Lisensi

Proyek ini menyertakan file `LICENSE` di root directory.
