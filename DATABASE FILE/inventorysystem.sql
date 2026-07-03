-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2026 at 07:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventorysystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `billings`
--

CREATE TABLE `billings` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(25,2) NOT NULL DEFAULT 0.00,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'belum_lunas',
  `paid_date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `billings`
--

INSERT INTO `billings` (`id`, `invoice_no`, `client_id`, `product_id`, `reference_type`, `reference_id`, `description`, `amount`, `issue_date`, `due_date`, `status`, `paid_date`, `note`, `created_by`, `created_at`) VALUES
(4, 'INV-20260521130444-4ZCI', NULL, 1, 'pengambilan', 14, 'Penagihan pengambilan barang: Produk Demo', 50000.00, '2026-05-10', '2026-05-17', 'belum_lunas', NULL, 'Tagihan otomatis', NULL, '2026-05-21 13:04:44'),
(5, 'INV-20260521130444-0BEN', NULL, 2, 'pengambilan', 15, 'Penagihan pengambilan barang: Macam-macam Kotak', 25000.00, '2026-05-15', '2026-05-22', 'belum_lunas', NULL, 'Tagihan otomatis', NULL, '2026-05-21 13:04:44'),
(6, 'INV-20260521130444-9YTW', NULL, 3, 'pengambilan', 16, 'Penagihan pengambilan barang: Biji Gandum', 100000.00, '2026-05-20', '2026-05-27', 'belum_lunas', NULL, 'Tagihan otomatis', NULL, '2026-05-21 13:04:44'),
(7, 'INV-20260521131201-72Q9', 6, 14, 'pengambilan', 17, 'Penagihan pengambilan barang: Kursi Kantor Pelanggan', 75000.00, '2026-05-12', '2026-05-19', 'belum_lunas', NULL, 'Tagihan pelanggan', NULL, '2026-05-21 13:12:01'),
(8, 'INV-20260521131201-2YHU', 6, 15, 'pengambilan', 18, 'Penagihan pengambilan barang: Meja Tulis Pelanggan', 75000.00, '2026-05-19', '2026-05-26', 'belum_lunas', NULL, 'Tagihan pelanggan', NULL, '2026-05-21 13:12:01'),
(9, 'INV-20260521121004-PPWH', NULL, 3, 'pengambilan', 19, 'Penagihan pengambilan barang: Biji Gandum', 0.00, '2026-05-21', '2026-05-28', 'belum_lunas', NULL, 'Tagihan otomatis dari transaksi pengambilan barang.', 1, '2026-05-21 12:10:04'),
(10, 'INV-20260604065806-FE4I', 9, 3, NULL, NULL, 'Mengeloala Website, Mengatur seo', 18000.00, '2026-06-04', '2026-06-11', 'belum_lunas', NULL, NULL, 1, '2026-06-04 06:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(8, 'Alat Tulis'),
(2, 'Bahan Baku'),
(4, 'Bahan Packing'),
(6, 'Barang Dalam Proses'),
(3, 'Barang Jadi'),
(1, 'Kategori Demo'),
(5, 'Mesin'),
(9, 'Perlengkapan Pelanggan');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_orders`
--

CREATE TABLE `delivery_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `document_no` varchar(50) NOT NULL,
  `movement_type` varchar(10) NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `document_date` date NOT NULL,
  `recipient` varchar(100) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `pickup_request_id` int(11) UNSIGNED DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `stock_processed` tinyint(1) NOT NULL DEFAULT 1,
  `stock_processed_at` datetime DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `delivery_orders`
--

INSERT INTO `delivery_orders` (`id`, `document_no`, `movement_type`, `client_id`, `product_id`, `quantity`, `document_date`, `recipient`, `driver_name`, `vehicle_no`, `reference_type`, `reference_id`, `pickup_request_id`, `scheduled_at`, `stock_processed`, `stock_processed_at`, `note`, `created_by`, `created_at`) VALUES
(4, 'SJ-20260521130444-WLGH', 'out', NULL, 1, 2, '2026-05-10', 'Internal', 'Joko Riyadi', 'B 1234 CD', 'pengambilan', 14, NULL, NULL, 1, NULL, 'Surat jalan otomatis', NULL, '2026-05-21 13:04:44'),
(5, 'SJ-20260521130444-24AW', 'out', NULL, 2, 1, '2026-05-15', 'Internal', 'Hendra Gani', 'F 9012 GH', 'pengambilan', 15, NULL, NULL, 1, NULL, 'Surat jalan otomatis', NULL, '2026-05-21 13:04:44'),
(6, 'SJ-20260521130444-VXID', 'out', NULL, 3, 5, '2026-05-20', 'Internal', 'Budi Santoso', 'D 5678 EF', 'pengambilan', 16, NULL, NULL, 1, NULL, 'Surat jalan otomatis', NULL, '2026-05-21 13:04:44'),
(7, 'SJ-20260521131201-SQ39', 'out', 6, 14, 1, '2026-05-12', 'Pelanggan Demo', 'Andi Setiawan', 'B 3344 KK', 'pengambilan', 17, NULL, NULL, 1, NULL, 'Surat jalan pelanggan', NULL, '2026-05-21 13:12:01'),
(9, 'SJ-20260521121004-WX1O', 'out', NULL, 3, 1, '2026-05-21', NULL, '-', '-', 'pengambilan', 19, NULL, NULL, 1, NULL, 'Surat jalan otomatis untuk pengambilan barang.', 1, '2026-05-21 12:10:04');

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `file_name`, `file_type`) VALUES
(1, 'default.png', 'image/png'),
(2, 'default.png', 'image/png'),
(3, 'Blue Black Modern Letter G Logo Design.png', 'image/png');

-- --------------------------------------------------------

--
-- Table structure for table `pickup_requests`
--

CREATE TABLE `pickup_requests` (
  `id` int(11) UNSIGNED NOT NULL,
  `request_no` varchar(50) NOT NULL,
  `client_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `unit_id` int(11) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `pickup_date` date NOT NULL,
  `pickup_time` time NOT NULL,
  `driver_name` varchar(100) NOT NULL,
  `vehicle_no` varchar(50) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `processed_by` int(11) UNSIGNED DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `no_surat_jalan` varchar(100) DEFAULT NULL,
  `no_batch` varchar(100) DEFAULT NULL,
  `grade` varchar(20) DEFAULT NULL,
  `tebal` decimal(10,2) DEFAULT NULL,
  `lebar` decimal(10,2) DEFAULT NULL,
  `panjang` decimal(10,2) DEFAULT NULL,
  `m3` decimal(12,4) DEFAULT NULL,
  `sj_scan` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `pcs_per_crate` int(11) DEFAULT NULL,
  `buy_price` decimal(25,2) DEFAULT NULL,
  `sale_price` decimal(25,2) NOT NULL,
  `categorie_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `unit_id` int(11) UNSIGNED DEFAULT NULL,
  `media_id` int(11) DEFAULT 0,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `quantity`, `buy_price`, `sale_price`, `categorie_id`, `client_id`, `unit_id`, `media_id`, `date`) VALUES
(1, 'Produk Demo', '46', 100.00, 500.00, 1, NULL, 1, 0, '2021-04-04 16:45:51'),
(2, 'Macam-macam Kotak', '11999', 55.00, 130.00, 4, NULL, 1, 0, '2021-04-04 18:44:52'),
(3, 'Biji Gandum', '63', 2.00, 5.00, 2, NULL, 1, 0, '2021-04-04 18:48:53'),
(4, 'Kayu Gelondongan', '1200', 780.00, 1069.00, 2, NULL, 1, 0, '2021-04-04 19:03:23'),
(5, 'Mesin Bor Lantai W1848', '26', 299.00, 494.00, 5, NULL, 1, 0, '2021-04-04 19:11:30'),
(6, 'Gergaji Mesin Portabel', '42', 280.00, 415.00, 5, NULL, 1, 0, '2021-04-04 19:13:35'),
(7, 'Sereal Sarapan Pagi', '107', 3.00, 7.00, 3, NULL, 1, 0, '2021-04-04 19:15:38'),
(8, 'Ikan Sarden Laut', '110', 13.00, 20.00, 3, NULL, 1, 0, '2021-04-04 19:17:11'),
(9, 'Mainan Action Figure Woody', '67', 29.00, 55.00, 3, NULL, 1, 0, '2021-04-04 19:19:20'),
(10, 'Mainan Marvel Legends', '106', 219.00, 322.00, 3, NULL, 1, 0, '2021-04-04 19:20:28'),
(11, 'Kepingan Packing', '78', 21.00, 31.00, 4, NULL, 1, 0, '2021-04-04 19:25:22'),
(12, 'Dispenser Selotip Klasik', '160', 5.00, 10.00, 8, NULL, 1, 0, '2021-04-04 19:48:01'),
(13, 'Bubble Wrap Kecil', '199', 8.00, 19.00, 4, NULL, 1, 0, '2021-04-04 19:49:00'),
(14, 'Kursi Kantor Pelanggan', '24', 450.00, 650.00, 9, 6, 1, 1, '2021-04-05 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `product_defects`
--

CREATE TABLE `product_defects` (
  `id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `client_id` int(11) UNSIGNED DEFAULT NULL,
  `defect_qty` int(11) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_defect_photos`
--

CREATE TABLE `product_defect_photos` (
  `id` int(11) UNSIGNED NOT NULL,
  `defect_id` int(11) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `movement_type` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_id` int(11) UNSIGNED DEFAULT NULL,
  `quantity_before` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `client_id`, `movement_type`, `quantity`, `unit_id`, `quantity_before`, `quantity_after`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`) VALUES
(1, 1, NULL, 'in', 48, 1, 0, 48, 'migration', 1, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 16:45:51'),
(2, 2, NULL, 'in', 12000, 1, 0, 12000, 'migration', 2, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 18:44:52'),
(3, 3, NULL, 'in', 69, 1, 0, 69, 'migration', 3, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 18:48:53'),
(4, 4, NULL, 'in', 1200, 1, 0, 1200, 'migration', 4, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 19:03:23'),
(5, 5, NULL, 'in', 26, 1, 0, 26, 'migration', 5, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 19:11:30'),
(6, 6, NULL, 'in', 42, 1, 0, 42, 'migration', 6, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 19:13:35'),
(7, 7, NULL, 'in', 107, 1, 0, 107, 'migration', 7, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 19:15:38'),
(8, 8, NULL, 'in', 110, 1, 0, 110, 'migration', 8, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 19:17:11'),
(9, 9, NULL, 'in', 67, 1, 0, 67, 'migration', 9, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 19:19:20'),
(10, 10, NULL, 'in', 106, 1, 0, 106, 'migration', 10, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 19:20:28'),
(11, 11, NULL, 'in', 78, 1, 0, 78, 'migration', 11, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 19:25:22'),
(12, 12, NULL, 'in', 160, 1, 0, 160, 'migration', 12, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 19:48:01'),
(13, 13, NULL, 'in', 199, 1, 0, 199, 'migration', 13, 'Opening stock migrated from legacy inventory', NULL, '2021-04-04 19:49:00'),
(14, 14, 6, 'in', 25, 1, 0, 25, 'migration', 14, 'Opening stock migrated from legacy inventory', NULL, '2021-04-05 08:00:00'),
(19, 1, NULL, 'out', 2, 1, 48, 46, 'pengambilan', 14, 'Dummy pengambilan', NULL, '2026-05-10 10:00:00'),
(20, 2, NULL, 'out', 1, 1, 12000, 11999, 'pengambilan', 15, 'Dummy pengambilan', NULL, '2026-05-15 10:00:00'),
(21, 3, NULL, 'out', 5, 1, 69, 64, 'pengambilan', 16, 'Dummy pengambilan', NULL, '2026-05-20 10:00:00'),
(22, 14, 6, 'out', 1, 1, 25, 24, 'pengambilan', 17, 'Dummy pengambilan dari klien', NULL, '2026-05-12 14:00:00'),
(24, 3, NULL, 'out', 1, 1, 64, 63, 'pengambilan', 19, 'Barang diambil dari gudang', 1, '2026-05-21 12:10:04');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'unit', 'Satuan default sistem', '2026-06-04 11:01:31'),
(2, 'dus', 'Satuan default sistem', '2026-06-04 11:01:31'),
(3, 'krat', 'Satuan default sistem', '2026-06-04 11:01:31'),
(4, 'lembar', 'Satuan default sistem', '2026-06-04 11:01:31'),
(5, 'palet', 'Satuan default sistem', '2026-06-04 11:01:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_level` int(11) NOT NULL,
  `image` varchar(255) DEFAULT 'no_image.jpg',
  `status` int(11) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `storage_rate` decimal(25,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `user_level`, `image`, `status`, `last_login`) VALUES
(1, 'Budi Admin', 'admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', 1, 'no_image.png', 1, '2026-06-04 07:16:43'),
(2, 'Joko Spesial', 'special', 'ba36b97a41e7faf742ab09bf88405ac04f99599a', 2, 'no_image.png', 1, '2021-04-04 19:53:26'),
(3, 'Christo User', 'user', '12dea96fec20593566ab75692c9949596833adc9', 3, 'no_image.png', 1, '2021-04-04 19:54:46'),
(4, 'Nadia Williams', 'natie', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 3, 'no_image.png', 1, NULL),
(5, 'Kevin Pegawai', 'kevin', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 3, 'no_image.png', 1, '2021-04-04 19:54:29'),
(6, 'Pelanggan Demo', 'client', 'd2a04d71301a8915217dd5faf81d12cffd6cd958', 4, 'no_image.png', 1, '2026-05-21 10:22:58'),
(7, 'Pelanggan Retail', 'client2', '7172e7d67c68576d4f308337e1aa6d533be7ebc8', 4, 'no_image.png', 1, NULL),
(8, 'Staf Gudang', 'staff', '5d43e3169f06cf2a04a0ee870b5ac2aff3c558ff', 3, 'no_image.png', 1, NULL),
(9, 'test', 'test', '40bd001563085fc35165329ea1ff5c5ecbdbbeef', 4, '110yfees9.png', 1, '2026-06-04 07:14:12');

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE `user_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(150) NOT NULL,
  `group_level` int(11) NOT NULL,
  `group_status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_groups`
--

INSERT INTO `user_groups` (`id`, `group_name`, `group_level`, `group_status`) VALUES
(1, 'Admin', 1, 1),
(2, 'special', 2, 1),
(3, 'User', 3, 1),
(4, 'Pelanggan', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(25,2) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `withdrawals`
--

INSERT INTO `withdrawals` (`id`, `product_id`, `qty`, `price`, `date`) VALUES
(1, 1, 2, 1000.00, '2021-04-04'),
(2, 3, 3, 15.00, '2021-04-04'),
(3, 10, 6, 1932.00, '2021-04-04'),
(4, 6, 2, 830.00, '2021-04-04'),
(5, 12, 5, 50.00, '2021-04-04'),
(6, 13, 21, 399.00, '2021-04-04'),
(7, 7, 5, 35.00, '2021-04-04'),
(8, 9, 2, 110.00, '2021-04-04'),
(9, 14, 2, 1300.00, '2021-04-05'),
(14, 1, 2, 0.00, '2026-05-10'),
(15, 2, 1, 0.00, '2026-05-15'),
(16, 3, 5, 0.00, '2026-05-20'),
(17, 14, 1, 0.00, '2026-05-12'),
(19, 3, 1, 0.00, '2026-05-21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `billings`
--
ALTER TABLE `billings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `due_date` (`due_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_no` (`document_no`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `document_date` (`document_date`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_name` (`name`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `media_id` (`media_id`);

--
-- Indexes for table `product_defects`
--
ALTER TABLE `product_defects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `product_defect_photos`
--
ALTER TABLE `product_defect_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `defect_id` (`defect_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_level` (`user_level`);

--
-- Indexes for table `user_groups`
--
ALTER TABLE `user_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_level` (`group_level`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `billings`
--
ALTER TABLE `billings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `product_defects`
--
ALTER TABLE `product_defects`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_defect_photos`
--
ALTER TABLE `product_defect_photos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1361;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_groups`
--
ALTER TABLE `user_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `FK_products` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_products_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `FK_stock_movements_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_stock_movements_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_stock_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_user` FOREIGN KEY (`user_level`) REFERENCES `user_groups` (`group_level`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `SK` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
