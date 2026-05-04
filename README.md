# Inventory System (PHP)

Aplikasi manajemen inventori berbasis PHP + MySQL.

## Informasi Proyek

- Nama database dari file SQL: `inventory_system`
- File dump database: `DATABASE FILE/inventory_system.sql`
- Versi PHP yang direkomendasikan (sesuai sumber proyek): `5.6.3`

## Fitur Utama

- Manajemen kategori
- Manajemen produk
- Manajemen penjualan
- Laporan penjualan harian dan bulanan
- Manajemen user (admin, special user, employee)

## Kebutuhan Sistem

- Web server lokal (Laragon/XAMPP/WAMP)
- PHP
- MySQL/MariaDB
- phpMyAdmin (opsional, untuk import database)

## Cara Menjalankan

1. Letakkan folder project di direktori web server, contoh:
   - Laragon: `C:/laragon/www/InventorySystem_PHP`
2. Jalankan Apache/Nginx dan MySQL dari Laragon.
3. Buat database baru dengan nama:
   - `inventory_system`
4. Import file SQL berikut ke database tersebut:
   - `DATABASE FILE/inventory_system.sql`
5. Sesuaikan konfigurasi koneksi di file:
   - `includes/config.php`

Contoh konfigurasi default lokal:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventory_system');
```

## Catatan Penting

Di `includes/config.php` bawaan, nilai `DB_NAME` saat ini adalah `inventorysystem` (tanpa underscore), sementara file SQL menggunakan `inventory_system` (dengan underscore).

Agar aplikasi tersambung ke database hasil import, pastikan nama database di `includes/config.php` sama dengan database yang kamu buat.

## Akun Login Default

### Admin
- Username: `admin`
- Password: `admin`

### Special User
- Username: `special`
- Password: `special`

### User (Employee)
- Username: `user`
- Password: `user`

## Struktur Direktori Singkat

- `includes/` konfigurasi, helper, dan session
- `layouts/` komponen layout
- `libs/` asset CSS/JS/gambar
- `uploads/` media upload
- `DATABASE FILE/` file SQL database

## Lisensi

Proyek ini menyertakan file `LICENSE` di root directory.
