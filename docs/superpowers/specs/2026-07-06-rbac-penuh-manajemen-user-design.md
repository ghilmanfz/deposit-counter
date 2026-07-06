# Desain RBAC Penuh Manajemen User

Tanggal: 2026-07-06

## Tujuan

Sistem manajemen user perlu mendukung pembuatan role staf internal baru seperti Manajer, Kasir, dan Gudang. Setiap role dapat diberi hak akses detail berdasarkan modul dan aksi, bukan hanya akses modul secara umum. Admin tetap menjadi superuser, sedangkan Pelanggan tetap role khusus client.

## Konteks Saat Ini

Aplikasi sudah memiliki tabel `users`, `user_groups`, halaman `users.php`, `add_user.php`, `edit_user.php`, dan halaman `access_control.php`. Kode juga sudah memiliki pondasi RBAC ringan melalui `role_permissions`, `role_can()`, dan `require_module()`.

Masalah utama saat ini adalah beberapa halaman masih memakai aturan level lama seperti `page_require_level(2)` atau `page_require_level(3)`. Role baru yang levelnya lebih besar dari 4 dapat tertolak walaupun hak akses modulnya dicentang. Selain itu, hak akses saat ini hanya sebatas modul, belum membedakan aksi seperti tambah, edit, hapus, cetak, dan proses.

## Keputusan Desain

Implementasi akan menggunakan RBAC penuh untuk staf internal. Hak akses disimpan sebagai kombinasi:

- role
- modul
- aksi
- status allowed

Role Admin selalu memiliki semua izin tanpa perlu disimpan sebagai checkbox. Role Pelanggan tetap diperlakukan sebagai role client bawaan dan tidak menjadi template role staf internal. Role baru yang dibuat dari halaman Kelola Hak Akses otomatis menjadi role staf internal.

## Modul dan Aksi

Aksi standar:

- `view`: melihat halaman/data
- `create`: menambah data
- `update`: mengubah data
- `delete`: menghapus data
- `print`: mencetak dokumen/laporan
- `process`: memproses/approve/tandai status

Modul awal:

- `satuan`: Satuan Barang
- `barang`: Barang Titipan
- `media`: Media
- `transaksi`: Transaksi Barang
- `penagihan`: Penagihan
- `surat_jalan`: Surat Jalan
- `laporan`: Laporan Barang
- `pickup`: Request Pengambilan

Modul admin-only tetap tidak bisa diberikan ke role staf biasa:

- manajemen user
- manajemen role/hak akses
- kategori barang
- konten dan publikasi
- pengaturan landing page

## Perubahan Database

Tambahkan tabel permission detail baru:

```sql
CREATE TABLE role_action_permissions (
  role_level int(11) NOT NULL,
  module_key varchar(40) NOT NULL,
  action_key varchar(30) NOT NULL,
  allowed tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (role_level, module_key, action_key)
);
```

Tabel lama `role_permissions` dapat tetap ada untuk kompatibilitas dan migrasi awal. Saat tabel detail masih kosong, sistem dapat men-seed permission detail dari izin modul lama: jika sebuah role punya akses modul, role itu minimal mendapat `view` untuk modul tersebut. Permission untuk aksi lain diisi sesuai perilaku role bawaan yang sudah ada.

## Perubahan Helper Akses

Tambahkan helper baru:

- `permission_actions()`: daftar aksi standar dan label UI.
- `access_permission_modules()`: daftar modul, label, dan aksi yang relevan per modul.
- `role_can_action($module_key, $action_key, $level = null)`: cek izin detail.
- `require_permission($module_key, $action_key = 'view')`: guard halaman dan aksi.
- `role_is_internal_staff($level)`: membedakan staf internal dari Admin dan Pelanggan.

Helper lama tetap dipertahankan:

- `role_can($module_key, $level = null)` menjadi alias untuk `role_can_action($module_key, 'view', $level)`.
- `require_module($module_key)` menjadi alias untuk `require_permission($module_key, 'view')`.

Dengan cara ini, menu lama tetap bekerja saat transisi, tetapi halaman dan tombol baru dapat memakai izin aksi detail.

## Perubahan UI

Halaman `access_control.php` akan menampilkan:

- daftar role dengan tambah, rename, status, dan hapus role
- matriks hak akses per role staf internal
- baris modul
- kolom aksi seperti Lihat, Tambah, Ubah, Hapus, Cetak, dan Proses

Admin tidak perlu checkbox karena selalu penuh. Pelanggan tetap tidak ikut konfigurasi staf internal. Role baru muncul otomatis dalam matriks setelah dibuat.

Halaman user:

- `users.php` tetap menjadi daftar user untuk Admin.
- `add_user.php` bisa membuat user dan memilih role aktif.
- `edit_user.php` bisa mengubah role user.
- Role yang ditampilkan untuk staf internal mencakup role baru seperti Manajer, Kasir, dan Gudang.

## Perubahan Guard Halaman dan Tombol

Halaman akan memakai guard berdasarkan aksi:

- daftar data memakai `require_permission(module, 'view')`
- form tambah memakai `require_permission(module, 'create')`
- form edit memakai `require_permission(module, 'update')`
- endpoint hapus memakai `require_permission(module, 'delete')`
- halaman cetak memakai `require_permission(module, 'print')`
- approve, reject, proses stok, dan tandai lunas memakai `require_permission(module, 'process')`

Tombol di UI juga disembunyikan sesuai izin. Contoh: user tanpa `delete` tidak melihat tombol Hapus; user tanpa `print` tidak melihat tombol Cetak.

## Data Flow

1. Admin membuat role baru di Kelola Hak Akses.
2. Sistem memberi `group_level` baru di atas level bawaan dan menandainya sebagai staf internal.
3. Admin mencentang aksi yang boleh dilakukan role tersebut.
4. Admin membuat atau mengedit user dan memilih role tersebut.
5. Saat user login, menu memakai izin `view`.
6. Saat user membuka halaman atau menekan aksi, backend memvalidasi izin aksi terkait.

## Error Handling

Jika user tidak login, sistem mengarahkan ke halaman login. Jika role nonaktif, sistem menolak akses seperti perilaku saat ini. Jika user tidak punya izin aksi, sistem menampilkan pesan hak akses ditolak dan mengarahkannya ke dashboard sesuai role.

Hapus role tetap ditolak jika role dilindungi atau masih dipakai user. Admin dan Pelanggan tetap tidak bisa dihapus dari halaman RBAC.

## Pengujian

Pengujian manual utama:

- Admin dapat membuat role Manajer, Kasir, dan Gudang.
- Admin dapat mengatur permission aksi tiap role.
- Admin dapat membuat user baru dengan role tersebut.
- Role tanpa `view` tidak melihat menu modul.
- Role dengan `view` tetapi tanpa `create` bisa melihat daftar tetapi tidak bisa membuka form tambah.
- Role tanpa `delete` tidak melihat tombol hapus dan endpoint hapus tetap menolak akses langsung.
- Role Kasir dapat mengelola Penagihan sesuai checkbox yang diberikan.
- Role Gudang dapat mengelola Barang/Transaksi/Surat Jalan sesuai checkbox yang diberikan.
- Role Pelanggan tetap memakai alur client yang sudah ada.
- Admin tetap memiliki akses penuh.

## Batasan Scope

Desain ini tidak menambahkan audit log perubahan permission, multi-cabang gudang, atau permission per data milik client. Fokus perubahan adalah role staf internal dan hak akses per aksi pada modul yang sudah ada.
