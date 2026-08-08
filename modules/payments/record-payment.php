<?php
// Courier Management System - Record Payment
// Created: 2026-08-08

$pageTitle = 'Record Payment';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once 'helpers.php';

// Check if user has permission
if (!hasPermission($pdo, 'payments', 'create')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$parcelId = $_GET['parcel_id'] ?? 0;

if (!$parcelId) {
    setFlashMessage('error', 'Invalid parcel ID.');
    header('Location: ' . BASE_URL . 'modules/payments/list.php');
    exit;
}

try {
    // Get or create payment record
    $payment = getOrCreatePaymentRecord($pdo, $parcelId);
    
    // Check if payment is already fully settled
    if (in_array($payment['payment_status'], ['paid', 'cod_collected'])) {
        setFlashMessage('info', 'This payment is already fully settled. Status: ' . formatPaymentStatus($payment['payment_status']));
        header('Location: ' . BASE_URL . 'modules/payments/view.php?id=' . $payment['id']);
        exit;
    }
    
    // Fetch parcel info for display
    $stmt = $pdo->prepare("SELECT tracking_number, receiver_name FROM parcels WHERE id = :id");
    $stmt->execute(['id' => $parcelId]);
    $parcel = $stmt->fetch();
    
    if (!$parcel) {
        setFlashMessage('error', 'Parcel not found.');
        header('Location: ' . BASE_URL . 'modules/payments/list.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Record Payment Init Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'modules/payments/list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? 0;
    $paymentMethod = $_POST['payment_method'] ?? '';
    $transactionNote = trim($_POST['transaction_note'] ?? '');
    
    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        setFlashMessage('error', 'Amount must be a valid number greater than 0.');
    } elseif ($amount > $payment['due_amount']) {
        setFlashMessage('error', 'Amount cannot exceed current due amount of ' . number_format($payment['due_amount'], 2));
    } elseif (empty($paymentMethod)) {
        setFlashMessage('error', 'Payment method is required.');
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert payment transaction
            $insertStmt = $pdo->prepare("
                INSERT INTO payment_transactions (payment_id, amount, payment_method, transaction_note, recorded_by)
                VALUES (:payment_id, :amount, :payment_method, :transaction_note, :recorded_by)
            ");
            $insertStmt->execute([
                'payment_id' => $payment['id'],
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_note' => $transactionNote ?: null,
                'recorded_by' => $_SESSION['user_id']
            ]);
            
            // Recalculate payment status
            $recalculated = recalculatePaymentStatus($pdo, $payment['id']);
            
            if (!$recalculated) {
                throw new Exception('Failed to recalculate payment status');
            }
            
            // Commit transaction
            $pdo->commit();
            
            setFlashMessage('success', 'Payment of ' . number_format($amount, 2) . ' recorded successfully.');
            header('Location: ' . BASE_URL . 'modules/payments/view.php?id=' . $payment['id']);
            exit;
            
        } catch (PDOException $e) {
            // Rollback on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Record Payment Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while recording the payment.');
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Record Payment</h2>
            <a href="<?php echo BASE_URL; ?>modules/payments/view.php?id=<?php echo $payment['id']; ?>" class="btn btn-secondary">Back to Payment Details</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Record Payment for Parcel #<?php echo htmlspecialchars($parcel['tracking_number']); ?></h5>
                    
                    <div class="alert alert-info mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Receiver:</strong> <?php echo htmlspecialchars($parcel['receiver_name']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Current Due:</strong> <?php echo number_format($payment['due_amount'], 2); ?>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Payment Amount *</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                   min="0.01" max="<?php echo $payment['due_amount']; ?>" required>
                            <div class="invalid-feedback">Please enter a valid amount</div>
                            <small class="text-muted">Maximum amount: <?php echo number_format($payment['due_amount'], 2); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method *</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">Select Method</option>
                                <option value="Cash">Cash</option>
                                <option value="Mobile Banking">Mobile Banking</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Card">Card</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select a payment method</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transaction_note" class="form-label">Transaction Note (Optional)</label>
                            <textarea class="form-control" id="transaction_note" name="transaction_note" rows="2" 
                                      placeholder="Optional notes about this transaction"><?php echo htmlspecialchars($_POST['transaction_note'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-secondary">
                                <strong>Payment Summary:</strong><br>
                                <small>
                                    Delivery Charge: <?php echo number_format($payment['delivery_charge'], 2); ?><br>
                                    COD Amount: <?php echo $payment['cod_amount'] > 0 ? number_format($payment['cod_amount'], 2) : 'None'; ?><br>
                                    Total Due: <?php echo number_format($payment['delivery_charge'] + $payment['cod_amount'], 2); ?><br>
                                    Already Paid: <?php echo number_format($payment['paid_amount'], 2); ?><br>
                                    Remaining Due: <?php echo number_format($payment['due_amount'], 2); ?>
                                </small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Record Payment</button>
                        <a href="<?php echo BASE_URL; ?>modules/payments/view.php?id=<?php echo $payment['id']; ?>" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
