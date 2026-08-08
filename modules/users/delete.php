<?php
// Courier Management System - Delete/Deactivate User
// Created: 2026-08-08

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';

// Check if user has permission
if (!hasRole(['Admin'])) {
    setFlashMessage('error', 'Access denied. You do not have permission to perform this action.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$userId = $_GET['id'] ?? 0;

if (!$userId) {
    setFlashMessage('error', 'Invalid user ID.');
    header('Location: ' . BASE_URL . 'modules/users/list.php');
    exit;
}

// Prevent deleting own account
if ($userId == $_SESSION['user_id']) {
    setFlashMessage('error', 'You cannot deactivate your own account.');
    header('Location: ' . BASE_URL . 'modules/users/list.php');
    exit;
}

try {
    // Get current user status
    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setFlashMessage('error', 'User not found.');
        header('Location: ' . BASE_URL . 'modules/users/list.php');
        exit;
    }
    
    // Toggle is_active status
    $newStatus = $user['is_active'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET is_active = :is_active WHERE id = :id");
    $stmt->execute(['is_active' => $newStatus, 'id' => $userId]);
    
    $message = $newStatus ? 'User activated successfully' : 'User deactivated successfully';
    setFlashMessage('success', $message);
    header('Location: ' . BASE_URL . 'modules/users/list.php');
    exit;
} catch (PDOException $e) {
    error_log("Toggle User Status Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while updating user status.');
    header('Location: ' . BASE_URL . 'modules/users/list.php');
    exit;
}
