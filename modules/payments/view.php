<?php
// Courier Management System - View Payment
// Created: 2026-08-08

$pageTitle = 'View Payment Details';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once 'helpers.php';

// Check if user has permission
if (!hasPermission($pdo, 'payments', 'view')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$paymentId = $_GET['id'] ?? 0;

if (!$paymentId) {
    setFlashMessage('error', 'Invalid payment ID.');
    header('Location: ' . BASE_URL . 'modules/payments/list.php');
    exit;
}

// Fetch payment data with related information
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               parc.tracking_number, 
               parc.receiver_name as parcel_receiver_name,
               c.full_name as customer_name, 
               c.customer_code
        FROM payments p
        JOIN parcels parc ON p.parcel_id = parc.id
        JOIN customers c ON parc.customer_id = c.id
        WHERE p.id = :id
    ");
    $stmt->execute(['id' => $paymentId]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        setFlashMessage('error', 'Payment record not found.');
        header('Location: ' . BASE_URL . 'modules/payments/list.php');
        exit;
    }
    
    // Fetch transaction history
    $stmt = $pdo->prepare("
        SELECT pt.*, 
               CONCAT(u.full_name, ' (', u.username, ')') as recorded_by_name
        FROM payment_transactions pt
        LEFT JOIN users u ON pt.recorded_by = u.id
        WHERE pt.payment_id = :payment_id
        ORDER BY pt.recorded_at DESC
    ");
    $stmt->execute(['payment_id' => $paymentId]);
    $transactions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Fetch Payment Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading payment data.');
    header('Location: ' . BASE_URL . 'modules/payments/list.php');
    exit;
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">View Payment Details</h2>
            <a href="<?php echo BASE_URL; ?>modules/payments/list.php" class="btn btn-secondary">Back to Payments</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Payment Summary</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Tracking Number</label>
                            <div class="form-control-plaintext fw-bold fs-5"><?php echo htmlspecialchars($payment['tracking_number']); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Payment Status</label>
                            <div>
                                <span class="badge bg-<?php echo getPaymentStatusBadgeClass($payment['payment_status']); ?> fs-6">
                                    <?php echo formatPaymentStatus($payment['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Customer Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Customer Code</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($payment['customer_code']); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Customer Name</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($payment['customer_name']); ?></div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Receiver Information</h6>
                    <div class="mb-3">
                        <label class="form-label text-muted">Receiver Name</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($payment['parcel_receiver_name']); ?></div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Financial Information</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Delivery Charge</label>
                            <div class="form-control-plaintext fw-bold"><?php echo number_format($payment['delivery_charge'], 2); ?></div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">COD Amount</label>
                            <div class="form-control-plaintext fw-bold"><?php echo $payment['cod_amount'] > 0 ? number_format($payment['cod_amount'], 2) : 'No COD'; ?></div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Total Due</label>
                            <div class="form-control-plaintext fw-bold"><?php echo number_format($payment['delivery_charge'] + $payment['cod_amount'], 2); ?></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Paid Amount</label>
                            <div class="form-control-plaintext fw-bold text-success"><?php echo number_format($payment['paid_amount'], 2); ?></div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Due Amount</label>
                            <div class="form-control-plaintext fw-bold <?php echo $payment['due_amount'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo number_format($payment['due_amount'], 2); ?>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Payment Method</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($payment['payment_method'] ?? '-'); ?></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Payment Date</label>
                            <div class="form-control-plaintext"><?php echo $payment['payment_date'] ? date('M d, Y', strtotime($payment['payment_date'])) : '-'; ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Recorded At</label>
                            <div class="form-control-plaintext"><?php echo date('M d, Y g:i A', strtotime($payment['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <?php if ($payment['notes']): ?>
                    <div class="mb-3">
                        <label class="form-label text-muted">Notes</label>
                        <div class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="mt-4">
                        <?php if (hasPermission($pdo, 'payments', 'create') && !in_array($payment['payment_status'], ['paid', 'cod_collected'])): ?>
                        <a href="<?php echo BASE_URL; ?>modules/payments/record-payment.php?parcel_id=<?php echo $payment['parcel_id']; ?>" class="btn btn-success">
                            <i class="fas fa-dollar-sign"></i> Record Payment
                        </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $payment['parcel_id']; ?>" class="btn btn-info">
                            <i class="fas fa-box"></i> View Parcel
                        </a>
                        
                        <a href="<?php echo BASE_URL; ?>modules/payments/list.php" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Transaction History</h5>
                    
                    <?php if (empty($transactions)): ?>
                        <p class="text-muted">No transactions recorded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Recorded By</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $txn): ?>
                                        <tr>
                                            <td class="fw-bold text-success"><?php echo number_format($txn['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($txn['payment_method'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($txn['recorded_by_name'] ?? 'System'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($txn['recorded_at'])); ?></td>
                                        </tr>
                                        <?php if ($txn['transaction_note']): ?>
                                        <tr>
                                            <td colspan="4" class="small text-muted">
                                                <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($txn['transaction_note']); ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
