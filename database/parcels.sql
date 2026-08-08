-- Courier Management System - Parcel/Shipment Management
-- Database: courier_management_system
-- Module: parcels
-- Date: 2026-08-08

-- IMPORTANT: Import order for fresh database setup:
-- 1. auth_users.sql
-- 2. customers.sql
-- 3. roles_permissions.sql
-- 4. parcels.sql (this file)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create parcels table
CREATE TABLE IF NOT EXISTS `parcels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_number` (`tracking_number`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_delivery_staff_id` (`delivery_staff_id`),
  KEY `idx_current_status` (`current_status`),
  KEY `idx_booking_date` (`booking_date`),
  KEY `idx_is_deleted` (`is_deleted`),
  CONSTRAINT `parcels_customer_id_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `parcels_delivery_staff_id_fk` FOREIGN KEY (`delivery_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create parcel_status_log table
CREATE TABLE IF NOT EXISTS `parcel_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parcel_id` int(11) NOT NULL,
  `status` enum('pending','picked_up','in_transit','out_for_delivery','delivered','failed_delivery','cancelled') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_parcel_id` (`parcel_id`),
  KEY `idx_changed_at` (`changed_at`),
  CONSTRAINT `parcel_status_log_parcel_id_fk` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
