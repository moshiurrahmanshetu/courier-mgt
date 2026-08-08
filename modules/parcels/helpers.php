<?php
// Courier Management System - Parcel Management Helpers
// Created: 2026-08-08

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

/**
 * Generate the next tracking number in sequence
 * Format: TRK-XXXX (4-digit zero-padded number)
 * @param PDO $pdo Database connection
 * @return string Next tracking number (e.g., TRK-0001, TRK-0002)
 */
function generateTrackingNumber($pdo) {
    try {
        // Get the maximum numeric part from existing tracking numbers
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(tracking_number, 5) AS UNSIGNED)) as max_num FROM parcels");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $maxNum = $result['max_num'] ?? 0;
        $nextNum = $maxNum + 1;
        
        // Format as TRK-XXXX with 4-digit zero padding
        return 'TRK-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Tracking Number Generation Error: " . $e->getMessage());
        // Fallback to TRK-0001 if error occurs
        return 'TRK-0001';
    }
}

/**
 * Validate parcel data
 * @param array $data Parcel data to validate
 * @return array Array with 'valid' boolean and 'errors' array
 */
function validateParcelData($data) {
    $errors = [];
    
    // Validate customer_id
    if (empty($data['customer_id'])) {
        $errors[] = 'Customer (sender) is required';
    }
    
    // Validate receiver name
    if (empty($data['receiver_name'])) {
        $errors[] = 'Receiver name is required';
    } elseif (strlen($data['receiver_name']) > 100) {
        $errors[] = 'Receiver name must not exceed 100 characters';
    }
    
    // Validate receiver phone
    if (empty($data['receiver_phone'])) {
        $errors[] = 'Receiver phone is required';
    } elseif (strlen($data['receiver_phone']) > 20) {
        $errors[] = 'Receiver phone must not exceed 20 characters';
    }
    
    // Validate receiver address
    if (empty($data['receiver_address'])) {
        $errors[] = 'Receiver address is required';
    }
    
    // Validate parcel type
    if (empty($data['parcel_type'])) {
        $errors[] = 'Parcel type is required';
    }
    
    // Validate delivery charge
    if (!is_numeric($data['delivery_charge']) || $data['delivery_charge'] < 0) {
        $errors[] = 'Delivery charge must be a valid number >= 0';
    }
    
    // Validate COD amount
    if (!is_numeric($data['cod_amount']) || $data['cod_amount'] < 0) {
        $errors[] = 'COD amount must be a valid number >= 0';
    }
    
    // Validate booking date
    if (empty($data['booking_date'])) {
        $errors[] = 'Booking date is required';
    }
    
    // Validate weight if provided
    if (!empty($data['weight']) && (!is_numeric($data['weight']) || $data['weight'] <= 0)) {
        $errors[] = 'Weight must be a valid number > 0';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Get valid status transitions
 * @param string $currentStatus Current parcel status
 * @return array Array of valid next statuses
 */
function getValidStatusTransitions($currentStatus) {
    $transitions = [
        'pending' => ['picked_up', 'cancelled'],
        'picked_up' => ['in_transit', 'cancelled'],
        'in_transit' => ['out_for_delivery', 'cancelled'],
        'out_for_delivery' => ['delivered', 'failed_delivery'],
        'failed_delivery' => ['out_for_delivery', 'cancelled'],
        'delivered' => [], // Final status
        'cancelled' => [] // Final status
    ];
    
    return $transitions[$currentStatus] ?? [];
}

/**
 * Check if status transition is valid
 * @param string $currentStatus Current status
 * @param string $newStatus Proposed new status
 * @return bool True if transition is valid, false otherwise
 */
function isValidStatusTransition($currentStatus, $newStatus) {
    $validTransitions = getValidStatusTransitions($currentStatus);
    return in_array($newStatus, $validTransitions);
}

/**
 * Get status badge color class
 * @param string $status Parcel status
 * @return string Bootstrap badge color class
 */
function getStatusBadgeClass($status) {
    $badgeClasses = [
        'pending' => 'secondary',
        'picked_up' => 'info',
        'in_transit' => 'primary',
        'out_for_delivery' => 'warning',
        'delivered' => 'success',
        'failed_delivery' => 'danger',
        'cancelled' => 'dark'
    ];
    
    return $badgeClasses[$status] ?? 'secondary';
}
