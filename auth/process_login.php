<?php
// Courier Management System - Login Process
// Created: 2026-08-08

require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/role_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        header('Location: ' . BASE_URL . 'auth/login.php?error=' . urlencode('Username and password are required'));
        exit;
    }
    
    try {
        // Fetch user by username
        $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        
        if ($user && $user['is_active'] == 1 && password_verify($password, $user['password'])) {
            // Login successful - set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['avatar'] = $user['avatar'];
            $_SESSION['phone'] = $user['phone'];
            
            // Cache user permissions in session
            try {
                $stmt = $pdo->prepare("
                    SELECT p.permission_key 
                    FROM role_permissions rp
                    JOIN permissions p ON rp.permission_id = p.id
                    WHERE rp.role_id = :role_id
                ");
                $stmt->execute(['role_id' => $user['role_id']]);
                $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $_SESSION['permissions'] = $permissions;
            } catch (PDOException $e) {
                // If permissions table doesn't exist yet, set empty array
                error_log("Permission Fetch Error: " . $e->getMessage());
                $_SESSION['permissions'] = [];
            }
            
            // Redirect to dashboard
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit;
        } else {
            // Login failed - generic error message
            header('Location: ' . BASE_URL . 'auth/login.php?error=' . urlencode('Invalid username or password'));
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        header('Location: ' . BASE_URL . 'auth/login.php?error=' . urlencode('An error occurred. Please try again.'));
        exit;
    }
} else {
    // Not a POST request
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
