-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3309
-- Generation Time: Jan 26, 2026 at 10:47 AM
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
-- Database: `lynxprisegifts`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `products_id` int(11) DEFAULT NULL,
  `full_name` varchar(50) NOT NULL,
  `address` varchar(50) NOT NULL,
  `product_name` varchar(50) NOT NULL,
  `price` decimal(10,0) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,0) NOT NULL,
  `mode_of_payment` tinyint(1) NOT NULL,
  `mode_of_transpo` tinyint(1) NOT NULL,
  `date_of_pickup` datetime DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `time_updates` datetime DEFAULT current_timestamp(),
  `status` enum('pending','completed','cancelled','all_set') NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `products_id`, `full_name`, `address`, `product_name`, `price`, `comment`, `quantity`, `mode_of_payment`, `mode_of_transpo`, `date_of_pickup`, `facebook_link`, `product_image`, `time_updates`, `status`, `phone_number`, `notes`) VALUES
(5, NULL, NULL, 'Karl Jake Robin', 'Brgy. Malitbogay Javier, Leyte', 'Round Bouquet(1 dozen) Red', 748, '', 1, 0, 0, '2026-02-01 00:00:00', NULL, NULL, '2026-01-26 14:48:32', 'pending', NULL, NULL),
(6, NULL, NULL, 'Margielou Campasas', 'Brgy. Bonifacio Javier, Leyte', 'Fuzzy wire Flower Bouquet(pink an tag 248)', 248, '', 1, 0, 0, '2026-02-08 00:00:00', NULL, NULL, '2026-01-26 14:53:44', 'pending', NULL, NULL),
(7, NULL, NULL, 'Kristina Cassandra Tamayo', '', 'Round Bouquet(2 Dozen) red', 1298, 'red an wrapper', 1, 0, 0, '2026-01-31 00:00:00', NULL, NULL, '2026-01-26 15:32:47', 'pending', '09679474099', 'si bitch an nag order'),
(8, NULL, NULL, 'Kristina Cassandra Tamayo', '', 'Round Bouquet(1 Dozen) Pink', 748, '', 1, 0, 0, '2026-01-30 00:00:00', NULL, NULL, '2026-01-26 17:46:49', 'pending', '09679474099', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `productId` int(10) NOT NULL,
  `productName` varchar(20) NOT NULL,
  `productPrice` int(11) NOT NULL,
  `productStock` int(11) NOT NULL,
  `productCategory` tinyint(1) NOT NULL,
  `regDate` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(15) NOT NULL,
  `firstname` varchar(20) NOT NULL,
  `lastname` varchar(20) NOT NULL,
  `email` varchar(30) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` tinyint(1) NOT NULL,
  `reg_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password_hash`, `role`, `reg_date`) VALUES
(1, 'lanelyn', 'macasait', 'lanelynmacsait@gmail.com', '$2y$10$6xJ6rMmxtghUEKzzmuIriuZg5em7/zLhUEsXMuHbc6XnCyQ.UMIYe', 0, '2025-11-27 00:00:00'),
(2, 'kc', 'tamayo', 'kctamayo@gmail.com', '$2y$10$G5IyatcThSLPkMBmT6OUL.bKgdhQ4wqoRLzJdCpTS4WBDpInXP5AW', 0, '0000-00-00 00:00:00'),
(5, 'lan', 'mac', 'lan@gmail.com', '$2y$10$EiWEVygy4kk1GLaVnP.hveZfO0fQBYhp62v6yTRweOY5bLqciWikm', 0, '0000-00-00 00:00:00'),
(6, 'Lanelyn', 'Macasait', 'lanelynmacasait@gmail.com', '$2y$10$0/uxo1NRWVVYNdd1DZn6beABgIerXWrv0rrqX0LGQ2t9Mq16a3umO', 0, '0000-00-00 00:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`productId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `productId` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
