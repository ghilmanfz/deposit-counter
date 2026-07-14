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
- Pemrosesan stok melalui tombol **Proses Pengambilan**; melihat atau mencetak Surat Jalan tidak mengubah stok.
- Penagihan/invoice klien, jatuh tempo, status pembayaran, dan cetak invoice.
- Surat jalan barang masuk dan barang keluar.
- Laporan pengambilan harian, bulanan, dan periode tertentu.
- Pembatasan data klien berdasarkan `client_id`.

## Hak Akses

Sistem memakai RBAC berbasis role. Admin selalu memiliki akses penuh. Pelanggan adalah role khusus client. Role staf internal seperti Manajer, Kasir, dan Gudang dapat dibuat dari menu Manajemen User -> Kelola Hak Akses.

Hak akses staf internal dapat diatur per modul dan per aksi:

| Aksi | Keterangan |
| --- | --- |
| Lihat | Membuka halaman dan melihat data. |
| Tambah | Membuat data baru. |
| Ubah | Mengedit data yang sudah ada. |
| Hapus | Menghapus data. |
| Cetak | Mencetak invoice, surat jalan, atau laporan. |
| Proses | Approve/reject request, proses stok, atau tandai status. |

Modul manajemen user, manajemen role/hak akses, kategori barang, konten publikasi, dan landing page tetap khusus Admin.

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
- `pickup_request_items`: snapshot setiap bundle fisik yang dipilih dalam request.
- `inventory_bundles`: rincian bundle fisik, isi unit dasar, dan status tersedia/dipesan/keluar.
- `delivery_orders`: surat jalan barang masuk/keluar.
- `delivery_order_items`: snapshot bundle yang tercantum pada Surat Jalan keluar.
- `billings`: invoice dan status pembayaran klien.
- `withdrawals`: catatan transaksi pengambilan lama; pengambilan bundle baru dilaporkan dari `stock_movements` agar tidak dicatat dua kali.
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
6. Klien memilih satu atau beberapa bundle utuh; satu request boleh berisi beberapa jenis barang.
7. Sistem mereservasi bundle terpilih agar tidak dapat dipilih oleh request lain.
8. Admin menyetujui atau menolak request. Penolakan/pembatalan melepaskan reservasi.
9. Admin menekan **Proses Pengambilan** saat barang benar-benar diserahkan. Pada tahap ini stok semua produk terkait berkurang dalam satu transaksi.
10. Surat Jalan dapat dilihat atau dicetak setelah proses selesai tanpa memotong stok kembali.
11. Admin mengelola billing/invoice dan laporan pengambilan.

## Model Stok Bundle

- `products.quantity` menyimpan stok fisik dalam satuan dasar, misalnya lembar atau pcs.
- Setiap krat/dus/palet dicatat sebagai satu baris `inventory_bundles` dengan isi aktualnya. Isi antar-bundle boleh berbeda.
- `products.unit_id` adalah satuan bundle dan `products.base_unit_id` adalah satuan isi.
- Request baru wajib memilih bundle utuh berdasarkan stok milik klien. Sistem tidak memakai rata-rata isi bundle dan tidak memilih FIFO otomatis.
- Bundle baru wajib memiliki client aktif. Stok internal historis tetap memakai alur transaksi lama sampai ditetapkan sebagai barang titipan milik client.
- Produk lama tidak dikonversi otomatis. Admin perlu membuka **Rincian Bundle** dan memasukkan isi aktual setiap bundle dengan total yang sama persis dengan stok historis yang sudah tercatat.
- Jika satu Surat Jalan masuk memuat beberapa jenis atau kualitas barang, masukkan tiap kombinasi barang/grade sebagai produk terpisah dengan nomor Surat Jalan yang sama.

## Troubleshooting

- Jika muncul pesan gagal memilih database, pastikan nama database di `includes/config.php` sama dengan database yang dibuat.
- Jika gambar atau file tidak bisa diupload, pastikan folder di dalam `uploads/` dapat ditulis oleh web server.
- Jika fitur baru tidak muncul setelah import database lama, pastikan schema otomatis di `includes/sql.php` berhasil berjalan atau lengkapi schema dari dump terbaru.
- Jika login gagal, pastikan database sudah berhasil diimport dan akun default tersedia di tabel `users`.

## Lisensi

Proyek ini menyertakan file `LICENSE` di root directory.
