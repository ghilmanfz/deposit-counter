-- phpMyAdmin SQL Dump
-- version 4.2.11
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2021 at 07:57 PM
-- Server version: 5.6.21
-- PHP Version: 5.6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `inventory_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE IF NOT EXISTS `categories` (
`id` int(11) unsigned NOT NULL,
  `name` varchar(60) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Demo Category'),
(3, 'Finished Goods'),
(5, 'Machinery'),
(4, 'Packing Materials'),
(2, 'Raw Materials'),
(8, 'Stationery Items'),
(6, 'Work in Progress'),
(9, 'Client Supplies');

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE IF NOT EXISTS `media` (
`id` int(11) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `file_name`, `file_type`) VALUES
(1, 'dummy-product-01.jpg', 'image/jpeg'),
(2, 'dummy-product-02.png', 'image/png');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE IF NOT EXISTS `products` (
`id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `quantity` varchar(50) DEFAULT NULL,
  `buy_price` decimal(25,2) DEFAULT NULL,
  `sale_price` decimal(25,2) NOT NULL,
  `categorie_id` int(11) unsigned NOT NULL,
  `client_id` int(11) unsigned DEFAULT NULL,
  `media_id` int(11) DEFAULT '0',
  `date` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `quantity`, `buy_price`, `sale_price`, `categorie_id`, `client_id`, `media_id`, `date`) VALUES
(1, 'Demo Product', '48', '100.00', '500.00', 1, NULL, 0, '2021-04-04 16:45:51'),
(2, 'Box Varieties', '12000', '55.00', '130.00', 4, NULL, 0, '2021-04-04 18:44:52'),
(3, 'Wheat', '69', '2.00', '5.00', 2, NULL, 0, '2021-04-04 18:48:53'),
(4, 'Timber', '1200', '780.00', '1069.00', 2, NULL, 0, '2021-04-04 19:03:23'),
(5, 'W1848 Oscillating Floor Drill Press', '26', '299.00', '494.00', 5, NULL, 0, '2021-04-04 19:11:30'),
(6, 'Portable Band Saw XBP02Z', '42', '280.00', '415.00', 5, NULL, 0, '2021-04-04 19:13:35'),
(7, 'Life Breakfast Cereal-3 Pk', '107', '3.00', '7.00', 3, NULL, 0, '2021-04-04 19:15:38'),
(8, 'Chicken of the Sea Sardines W', '110', '13.00', '20.00', 3, NULL, 0, '2021-04-04 19:17:11'),
(9, 'Disney Woody - Action Figure', '67', '29.00', '55.00', 3, NULL, 0, '2021-04-04 19:19:20'),
(10, 'Hasbro Marvel Legends Series Toys', '106', '219.00', '322.00', 3, NULL, 0, '2021-04-04 19:20:28'),
(11, 'Packing Chips', '78', '21.00', '31.00', 4, NULL, 0, '2021-04-04 19:25:22'),
(12, 'Classic Desktop Tape Dispenser 38', '160', '5.00', '10.00', 8, NULL, 0, '2021-04-04 19:48:01'),
(13, 'Small Bubble Cushioning Wrap', '199', '8.00', '19.00', 4, NULL, 0, '2021-04-04 19:49:00'),
(14, 'Client Office Chair', '25', '450.00', '650.00', 9, 6, 1, '2021-04-05 08:00:00'),
(15, 'Client Writing Desk', '18', '700.00', '980.00', 9, 6, 2, '2021-04-05 08:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE IF NOT EXISTS `sales` (
`id` int(11) unsigned NOT NULL,
  `product_id` int(11) unsigned NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(25,2) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `product_id`, `qty`, `price`, `date`) VALUES
(1, 1, 2, '1000.00', '2021-04-04'),
(2, 3, 3, '15.00', '2021-04-04'),
(3, 10, 6, '1932.00', '2021-04-04'),
(4, 6, 2, '830.00', '2021-04-04'),
(5, 12, 5, '50.00', '2021-04-04'),
(6, 13, 21, '399.00', '2021-04-04'),
(7, 7, 5, '35.00', '2021-04-04'),
(8, 9, 2, '110.00', '2021-04-04'),
(9, 14, 2, '1300.00', '2021-04-05'),
(10, 15, 1, '980.00', '2021-04-05');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE IF NOT EXISTS `stock_movements` (
`id` int(11) unsigned NOT NULL,
  `product_id` int(11) unsigned NOT NULL,
  `client_id` int(11) unsigned DEFAULT NULL,
  `movement_type` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `quantity_before` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `client_id`, `movement_type`, `quantity`, `quantity_before`, `quantity_after`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`)
SELECT
  p.id,
  p.id,
  p.client_id,
  'in',
  CAST(p.quantity AS UNSIGNED),
  0,
  CAST(p.quantity AS UNSIGNED),
  'migration',
  p.id,
  'Opening stock migrated from legacy inventory',
  NULL,
  p.date
FROM `products` p;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
`id` int(11) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_level` int(11) NOT NULL,
  `image` varchar(255) DEFAULT 'no_image.jpg',
  `status` int(1) NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `user_level`, `image`, `status`, `last_login`) VALUES
(1, 'Harry Denn', 'admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', 1, 'no_image.png', 1, '2021-04-04 19:45:52'),
(2, 'John Walker', 'special', 'ba36b97a41e7faf742ab09bf88405ac04f99599a', 2, 'no_image.png', 1, '2021-04-04 19:53:26'),
(3, 'Christopher', 'user', '12dea96fec20593566ab75692c9949596833adc9', 3, 'no_image.png', 1, '2021-04-04 19:54:46'),
(4, 'Natie Williams', 'natie', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 3, 'no_image.png', 1, NULL),
(5, 'Kevin', 'kevin', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 3, 'no_image.png', 1, '2021-04-04 19:54:29'),
(6, 'Client Demo', 'client', 'd2a04d71301a8915217dd5faf81d12cffd6cd958', 4, 'no_image.png', 1, NULL),
(7, 'Client Retail', 'client2', '7172e7d67c68576d4f308337e1aa6d533be7ebc8', 4, 'no_image.png', 1, NULL),
(8, 'Warehouse Staff', 'staff', '5d43e3169f06cf2a04a0ee870b5ac2aff3c558ff', 3, 'no_image.png', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE IF NOT EXISTS `user_groups` (
`id` int(11) NOT NULL,
  `group_name` varchar(150) NOT NULL,
  `group_level` int(11) NOT NULL,
  `group_status` int(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_groups`
--

INSERT INTO `user_groups` (`id`, `group_name`, `group_level`, `group_status`) VALUES
(1, 'Admin', 1, 1),
(2, 'special', 2, 1),
(3, 'User', 3, 1),
(4, 'Client', 4, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
 ADD PRIMARY KEY (`id`), ADD KEY `idx_products_name` (`name`), ADD KEY `categorie_id` (`categorie_id`), ADD KEY `client_id` (`client_id`), ADD KEY `media_id` (`media_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
 ADD PRIMARY KEY (`id`), ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
 ADD PRIMARY KEY (`id`), ADD KEY `product_id` (`product_id`), ADD KEY `client_id` (`client_id`), ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
 ADD PRIMARY KEY (`id`), ADD KEY `user_level` (`user_level`);

--
-- Indexes for table `user_groups`
--
ALTER TABLE `user_groups`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `group_level` (`group_level`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `user_groups`
--
ALTER TABLE `user_groups`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
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
-- Constraints for table `sales`
--
ALTER TABLE `sales`
ADD CONSTRAINT `SK` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
ADD CONSTRAINT `FK_stock_movements_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `FK_stock_movements_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `FK_stock_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
ADD CONSTRAINT `FK_user` FOREIGN KEY (`user_level`) REFERENCES `user_groups` (`group_level`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
