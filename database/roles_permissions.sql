-- Courier Management System - Dynamic Role & Permission Management
-- Database: courier_management_system
-- Module: roles_permissions
-- Date: 2026-08-08

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create permissions table
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_key` varchar(50) NOT NULL,
  `module_label` varchar(100) NOT NULL,
  `action` enum('view','create','edit','delete') NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_key` (`permission_key`),
  KEY `idx_module_key` (`module_key`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create role_permissions table
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_role_id_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_permission_id_fk` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data for permissions table (6 modules x 4 actions = 24 permissions)
INSERT INTO `permissions` (`module_key`, `module_label`, `action`, `permission_key`) VALUES
-- User Management
('users', 'User Management', 'view', 'users.view'),
('users', 'User Management', 'create', 'users.create'),
('users', 'User Management', 'edit', 'users.edit'),
('users', 'User Management', 'delete', 'users.delete'),
-- Customer Management
('customers', 'Customer Management', 'view', 'customers.view'),
('customers', 'Customer Management', 'create', 'customers.create'),
('customers', 'Customer Management', 'edit', 'customers.edit'),
('customers', 'Customer Management', 'delete', 'customers.delete'),
-- Parcel Management
('parcels', 'Parcel Management', 'view', 'parcels.view'),
('parcels', 'Parcel Management', 'create', 'parcels.create'),
('parcels', 'Parcel Management', 'edit', 'parcels.edit'),
('parcels', 'Parcel Management', 'delete', 'parcels.delete'),
-- Delivery Management
('delivery', 'Delivery Management', 'view', 'delivery.view'),
('delivery', 'Delivery Management', 'create', 'delivery.create'),
('delivery', 'Delivery Management', 'edit', 'delivery.edit'),
('delivery', 'Delivery Management', 'delete', 'delivery.delete'),
-- Payment Management
('payments', 'Payment Management', 'view', 'payments.view'),
('payments', 'Payment Management', 'create', 'payments.create'),
('payments', 'Payment Management', 'edit', 'payments.edit'),
('payments', 'Payment Management', 'delete', 'payments.delete'),
-- Reports
('reports', 'Reports', 'view', 'reports.view'),
('reports', 'Reports', 'create', 'reports.create'),
('reports', 'Reports', 'edit', 'reports.edit'),
('reports', 'Reports', 'delete', 'reports.delete');

-- Seed data for role_permissions
-- Admin role gets ALL 24 permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Staff role gets: customers.* (4), parcels.* (4), delivery.view, payments.view (10 total)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` 
WHERE permission_key IN (
    'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
    'parcels.view', 'parcels.create', 'parcels.edit', 'parcels.delete',
    'delivery.view',
    'payments.view'
);

-- Delivery Staff role gets: delivery.view, delivery.edit (2 total)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` 
WHERE permission_key IN ('delivery.view', 'delivery.edit');

COMMIT;
