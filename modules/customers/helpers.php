<?php
// Courier Management System - Customer Management Helpers
// Created: 2026-08-08

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

/**
 * Generate the next customer code in sequence
 * Format: CUS-XXXX (4-digit zero-padded number)
 * @param PDO $pdo Database connection
 * @return string Next customer code (e.g., CUS-0001, CUS-0002)
 */
function generateCustomerCode($pdo) {
    try {
        // Get the maximum numeric part from existing customer codes
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(customer_code, 5) AS UNSIGNED)) as max_num FROM customers");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $maxNum = $result['max_num'] ?? 0;
        $nextNum = $maxNum + 1;
        
        // Format as CUS-XXXX with 4-digit zero padding
        return 'CUS-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Customer Code Generation Error: " . $e->getMessage());
        // Fallback to CUS-0001 if error occurs
        return 'CUS-0001';
    }
}

/**
 * Validate customer data
 * @param array $data Customer data to validate
 * @return array Array with 'valid' boolean and 'errors' array
 */
function validateCustomerData($data) {
    $errors = [];
    
    // Validate full name
    if (empty($data['full_name'])) {
        $errors[] = 'Full name is required';
    } elseif (strlen($data['full_name']) > 100) {
        $errors[] = 'Full name must not exceed 100 characters';
    }
    
    // Validate phone
    if (empty($data['phone'])) {
        $errors[] = 'Phone is required';
    } elseif (strlen($data['phone']) > 20) {
        $errors[] = 'Phone must not exceed 20 characters';
    }
    
    // Validate email (optional but must be valid if provided)
    if (!empty($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif (strlen($data['email']) > 100) {
            $errors[] = 'Email must not exceed 100 characters';
        }
    }
    
    // Validate address
    if (empty($data['address'])) {
        $errors[] = 'Address is required';
    }
    
    // Validate city/area
    if (empty($data['city_area'])) {
        $errors[] = 'City/Area is required';
    } elseif (strlen($data['city_area']) > 100) {
        $errors[] = 'City/Area must not exceed 100 characters';
    }
    
    // Validate status
    if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
        $errors[] = 'Invalid status value';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
