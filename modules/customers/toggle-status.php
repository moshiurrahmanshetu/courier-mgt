<?php
// Courier Management System - Customer Toggle Status (Soft Delete/Restore)
// Created: 2026-08-08

require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/role_check.php';

// Check if user has permission
if (!hasPermission($pdo, 'customers', 'delete')) {
    setFlashMessage('error', 'Access denied. You do not have permission to perform this action.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Invalid request method.');
    header('Location: ' . BASE_URL . 'modules/customers/list.php');
    exit;
}

$customerId = $_POST['customer_id'] ?? 0;
$action = $_POST['action'] ?? '';

if (!$customerId || !in_array($action, ['soft-delete', 'restore'])) {
    setFlashMessage('error', 'Invalid request parameters.');
    header('Location: ' . BASE_URL . 'modules/customers/list.php');
    exit;
}

try {
    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id, is_deleted FROM customers WHERE id = :id");
    $stmt->execute(['id' => $customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        setFlashMessage('error', 'Customer not found.');
        header('Location: ' . BASE_URL . 'modules/customers/list.php');
        exit;
    }
    
    // Determine new is_deleted value
    $newStatus = ($action === 'soft-delete') ? 1 : 0;
    
    // Validate action is appropriate for current state
    if ($action === 'soft-delete' && $customer['is_deleted'] == 1) {
        setFlashMessage('error', 'Customer is already deleted.');
        header('Location: ' . BASE_URL . 'modules/customers/list.php');
        exit;
    }
    
    if ($action === 'restore' && $customer['is_deleted'] == 0) {
        setFlashMessage('error', 'Customer is already active.');
        header('Location: ' . BASE_URL . 'modules/customers/list.php');
        exit;
    }
    
    // Update is_deleted status
    $stmt = $pdo->prepare("UPDATE customers SET is_deleted = :is_deleted WHERE id = :id");
    $stmt->execute(['is_deleted' => $newStatus, 'id' => $customerId]);
    
    $message = ($action === 'soft-delete') ? 'Customer deleted successfully' : 'Customer restored successfully';
    setFlashMessage('success', $message);
    
    header('Location: ' . BASE_URL . 'modules/customers/list.php');
    exit;
} catch (PDOException $e) {
    error_log("Customer Toggle Status Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while updating customer status.');
    header('Location: ' . BASE_URL . 'modules/customers/list.php');
    exit;
}
