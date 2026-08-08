<?php
// Courier Management System - Payment Management Helpers
// Created: 2026-08-08

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

/**
 * Get or create a payment record for a parcel
 * Lazy creation approach - creates payment record if it doesn't exist
 * @param PDO $pdo Database connection
 * @param int $parcelId Parcel ID
 * @return array Payment record data
 */
function getOrCreatePaymentRecord($pdo, $parcelId) {
    try {
        // First try to get existing payment record
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE parcel_id = :parcel_id");
        $stmt->execute(['parcel_id' => $parcelId]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            return $payment;
        }
        
        // If no payment record exists, fetch parcel data and create one
        $stmt = $pdo->prepare("SELECT delivery_charge, cod_amount FROM parcels WHERE id = :parcel_id");
        $stmt->execute(['parcel_id' => $parcelId]);
        $parcel = $stmt->fetch();
        
        if (!$parcel) {
            throw new Exception('Parcel not found');
        }
        
        $deliveryCharge = $parcel['delivery_charge'];
        $codAmount = $parcel['cod_amount'];
        $totalDue = $deliveryCharge + $codAmount;
        
        // Determine initial payment status
        if ($codAmount > 0) {
            $initialStatus = 'cod_pending';
        } else {
            $initialStatus = 'unpaid';
        }
        
        // Insert new payment record
        $insertStmt = $pdo->prepare("
            INSERT INTO payments (parcel_id, delivery_charge, cod_amount, paid_amount, due_amount, payment_status)
            VALUES (:parcel_id, :delivery_charge, :cod_amount, 0, :due_amount, :payment_status)
        ");
        $insertStmt->execute([
            'parcel_id' => $parcelId,
            'delivery_charge' => $deliveryCharge,
            'cod_amount' => $codAmount,
            'due_amount' => $totalDue,
            'payment_status' => $initialStatus
        ]);
        
        // Fetch the newly created record
        $paymentId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = :id");
        $stmt->execute(['id' => $paymentId]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log("Get/Create Payment Record Error: " . $e->getMessage());
        throw new Exception('Error retrieving or creating payment record');
    }
}

/**
 * Recalculate payment status and amounts after a transaction
 * @param PDO $pdo Database connection
 * @param int $paymentId Payment ID
 * @return bool True if successful, false otherwise
 */
function recalculatePaymentStatus($pdo, $paymentId) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get current payment record
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = :payment_id");
        $stmt->execute(['payment_id' => $paymentId]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            throw new Exception('Payment record not found');
        }
        
        // Sum all transactions for this payment
        $stmt = $pdo->prepare("SELECT SUM(amount) as total_paid FROM payment_transactions WHERE payment_id = :payment_id");
        $stmt->execute(['payment_id' => $paymentId]);
        $result = $stmt->fetch();
        
        $paidAmount = $result['total_paid'] ?? 0;
        $totalDue = $payment['delivery_charge'] + $payment['cod_amount'];
        $dueAmount = $totalDue - $paidAmount;
        
        // Determine payment status
        if ($paidAmount == 0 && $payment['cod_amount'] > 0) {
            $newStatus = 'cod_pending';
        } elseif ($paidAmount == 0 && $payment['cod_amount'] == 0) {
            $newStatus = 'unpaid';
        } elseif ($paidAmount > 0 && $paidAmount < $totalDue) {
            $newStatus = 'partial';
        } elseif ($paidAmount >= $totalDue) {
            if ($payment['cod_amount'] > 0) {
                $newStatus = 'cod_collected';
            } else {
                $newStatus = 'paid';
            }
        } else {
            $newStatus = $payment['payment_status']; // Keep existing status
        }
        
        // Determine payment date (set when fully settled)
        $paymentDate = $payment['payment_date'];
        if ($newStatus === 'paid' || $newStatus === 'cod_collected') {
            $paymentDate = date('Y-m-d');
        }
        
        // Update payment record
        $updateStmt = $pdo->prepare("
            UPDATE payments 
            SET paid_amount = :paid_amount, 
                due_amount = :due_amount, 
                payment_status = :payment_status,
                payment_date = :payment_date
            WHERE id = :payment_id
        ");
        $updateStmt->execute([
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'payment_status' => $newStatus,
            'payment_date' => $paymentDate,
            'payment_id' => $paymentId
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        return true;
        
    } catch (PDOException $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Recalculate Payment Status Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get payment status badge color class
 * @param string $status Payment status
 * @return string Bootstrap badge color class
 */
function getPaymentStatusBadgeClass($status) {
    $badgeClasses = [
        'unpaid' => 'danger',
        'partial' => 'warning',
        'paid' => 'success',
        'cod_pending' => 'info',
        'cod_collected' => 'success'
    ];
    
    return $badgeClasses[$status] ?? 'secondary';
}

/**
 * Format payment status for display
 * @param string $status Payment status
 * @return string Formatted status label
 */
function formatPaymentStatus($status) {
    $labels = [
        'unpaid' => 'Unpaid',
        'partial' => 'Partial Payment',
        'paid' => 'Paid',
        'cod_pending' => 'COD Pending',
        'cod_collected' => 'COD Collected'
    ];
    
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}
