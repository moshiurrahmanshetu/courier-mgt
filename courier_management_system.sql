-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2026 at 06:04 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `courier_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text NOT NULL,
  `city_area` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_code`, `full_name`, `phone`, `email`, `address`, `city_area`, `status`, `is_deleted`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CUS-0001', 'atom shetu', '05435345', 'atomicshetu@gmail.com', 'fdsgds\r\ndfg fdsg', 'dfg fsd', 'active', 1, 1, '2026-08-08 15:58:45', '2026-08-08 15:58:59');

-- --------------------------------------------------------

--
-- Table structure for table `parcels`
--

CREATE TABLE `parcels` (
  `id` int(11) NOT NULL,
  `tracking_number` varchar(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `receiver_name` varchar(100) NOT NULL,
  `receiver_phone` varchar(20) NOT NULL,
  `receiver_address` text NOT NULL,
  `parcel_type` varchar(50) NOT NULL,
  `parcel_description` text DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cod_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_staff_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `current_status` enum('pending','picked_up','in_transit','out_for_delivery','delivered','failed_delivery','cancelled') NOT NULL DEFAULT 'pending',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parcel_status_log`
--

CREATE TABLE `parcel_status_log` (
  `id` int(11) NOT NULL,
  `parcel_id` int(11) NOT NULL,
  `status` enum('pending','picked_up','in_transit','out_for_delivery','delivered','failed_delivery','cancelled') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `parcel_id` int(11) NOT NULL,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cod_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('unpaid','partial','paid','cod_pending','cod_collected') NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_note` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `module_key` varchar(50) NOT NULL,
  `module_label` varchar(100) NOT NULL,
  `action` enum('view','create','edit','delete') NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `module_key`, `module_label`, `action`, `permission_key`, `created_at`) VALUES
(1, 'users', 'User Management', 'view', 'users.view', '2026-08-08 12:25:50'),
(2, 'users', 'User Management', 'create', 'users.create', '2026-08-08 12:25:50'),
(3, 'users', 'User Management', 'edit', 'users.edit', '2026-08-08 12:25:50'),
(4, 'users', 'User Management', 'delete', 'users.delete', '2026-08-08 12:25:50'),
(5, 'customers', 'Customer Management', 'view', 'customers.view', '2026-08-08 12:25:50'),
(6, 'customers', 'Customer Management', 'create', 'customers.create', '2026-08-08 12:25:50'),
(7, 'customers', 'Customer Management', 'edit', 'customers.edit', '2026-08-08 12:25:50'),
(8, 'customers', 'Customer Management', 'delete', 'customers.delete', '2026-08-08 12:25:50'),
(9, 'parcels', 'Parcel Management', 'view', 'parcels.view', '2026-08-08 12:25:50'),
(10, 'parcels', 'Parcel Management', 'create', 'parcels.create', '2026-08-08 12:25:50'),
(11, 'parcels', 'Parcel Management', 'edit', 'parcels.edit', '2026-08-08 12:25:50'),
(12, 'parcels', 'Parcel Management', 'delete', 'parcels.delete', '2026-08-08 12:25:50'),
(13, 'delivery', 'Delivery Management', 'view', 'delivery.view', '2026-08-08 12:25:50'),
(14, 'delivery', 'Delivery Management', 'create', 'delivery.create', '2026-08-08 12:25:50'),
(15, 'delivery', 'Delivery Management', 'edit', 'delivery.edit', '2026-08-08 12:25:50'),
(16, 'delivery', 'Delivery Management', 'delete', 'delivery.delete', '2026-08-08 12:25:50'),
(17, 'payments', 'Payment Management', 'view', 'payments.view', '2026-08-08 12:25:50'),
(18, 'payments', 'Payment Management', 'create', 'payments.create', '2026-08-08 12:25:50'),
(19, 'payments', 'Payment Management', 'edit', 'payments.edit', '2026-08-08 12:25:50'),
(20, 'payments', 'Payment Management', 'delete', 'payments.delete', '2026-08-08 12:25:50'),
(21, 'reports', 'Reports', 'view', 'reports.view', '2026-08-08 12:25:50'),
(22, 'reports', 'Reports', 'create', 'reports.create', '2026-08-08 12:25:50'),
(23, 'reports', 'Reports', 'edit', 'reports.edit', '2026-08-08 12:25:50'),
(24, 'reports', 'Reports', 'delete', 'reports.delete', '2026-08-08 12:25:50');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `created_at`) VALUES
(1, 'Admin', 'Full system administrator with all permissions', '2026-08-08 11:42:36'),
(2, 'Staff', 'Regular staff with limited permissions', '2026-08-08 11:42:36'),
(3, 'Delivery Staff', 'Delivery personnel with specific delivery-related permissions', '2026-08-08 11:42:36');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(32, 2, 6, '2026-08-08 12:25:50'),
(33, 2, 8, '2026-08-08 12:25:50'),
(34, 2, 7, '2026-08-08 12:25:50'),
(35, 2, 5, '2026-08-08 12:25:50'),
(36, 2, 13, '2026-08-08 12:25:50'),
(37, 2, 10, '2026-08-08 12:25:50'),
(38, 2, 12, '2026-08-08 12:25:50'),
(39, 2, 11, '2026-08-08 12:25:50'),
(40, 2, 9, '2026-08-08 12:25:50'),
(41, 2, 17, '2026-08-08 12:25:50'),
(47, 3, 15, '2026-08-08 12:25:50'),
(48, 3, 13, '2026-08-08 12:25:50'),
(50, 1, 5, '2026-08-08 12:29:35'),
(51, 1, 6, '2026-08-08 12:29:35'),
(52, 1, 7, '2026-08-08 12:29:35'),
(53, 1, 8, '2026-08-08 12:29:35'),
(54, 1, 13, '2026-08-08 12:29:35'),
(55, 1, 14, '2026-08-08 12:29:35'),
(56, 1, 15, '2026-08-08 12:29:35'),
(57, 1, 16, '2026-08-08 12:29:35'),
(58, 1, 9, '2026-08-08 12:29:35'),
(59, 1, 10, '2026-08-08 12:29:35'),
(60, 1, 11, '2026-08-08 12:29:35'),
(61, 1, 12, '2026-08-08 12:29:35'),
(62, 1, 17, '2026-08-08 12:29:35'),
(63, 1, 18, '2026-08-08 12:29:35'),
(64, 1, 19, '2026-08-08 12:29:35'),
(65, 1, 20, '2026-08-08 12:29:35'),
(66, 1, 21, '2026-08-08 12:29:35'),
(67, 1, 22, '2026-08-08 12:29:35'),
(68, 1, 23, '2026-08-08 12:29:35'),
(69, 1, 24, '2026-08-08 12:29:35'),
(70, 1, 1, '2026-08-08 12:29:35'),
(71, 1, 2, '2026-08-08 12:29:35'),
(72, 1, 3, '2026-08-08 12:29:35'),
(73, 1, 4, '2026-08-08 12:29:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `username`, `password`, `avatar`, `role_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin@courier.com', '1234567890', 'admin', '$2y$10$3GVtIE7ONdHdryLMny0W/exFeII3ZFynVfxqnnAnmgMbs3ocBRKu6', 'user_1_1786189636.png', 1, 1, '2026-08-08 11:42:36', '2026-08-08 11:47:16'),
(2, 'rakib', 'r@gmail.com', '01782313231', 'rakib1', '$2y$10$RJWCxr5yeu7zmCDmz/TvuOU/aBy7SBQMkc7/lbxH9xNY4FI2vC8py', NULL, 3, 1, '2026-08-08 15:19:16', '2026-08-08 15:19:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_deleted` (`is_deleted`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `parcels`
--
ALTER TABLE `parcels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_delivery_staff_id` (`delivery_staff_id`),
  ADD KEY `idx_current_status` (`current_status`),
  ADD KEY `idx_booking_date` (`booking_date`),
  ADD KEY `idx_is_deleted` (`is_deleted`);

--
-- Indexes for table `parcel_status_log`
--
ALTER TABLE `parcel_status_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parcel_id` (`parcel_id`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_parcel_payment` (`parcel_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_payment_date` (`payment_date`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_recorded_at` (`recorded_at`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`),
  ADD KEY `idx_module_key` (`module_key`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `idx_role_id` (`role_id`),
  ADD KEY `idx_permission_id` (`permission_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parcels`
--
ALTER TABLE `parcels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parcel_status_log`
--
ALTER TABLE `parcel_status_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `parcels`
--
ALTER TABLE `parcels`
  ADD CONSTRAINT `parcels_customer_id_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `parcels_delivery_staff_id_fk` FOREIGN KEY (`delivery_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `parcel_status_log`
--
ALTER TABLE `parcel_status_log`
  ADD CONSTRAINT `parcel_status_log_parcel_id_fk` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_parcel_id_fk` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_payment_id_fk` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_permission_id_fk` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_role_id_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
