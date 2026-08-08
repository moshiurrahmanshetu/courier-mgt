<?php
// Courier Management System - Payment List
// Created: 2026-08-08

$pageTitle = 'Manage Payments';
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

// Pagination settings
$perPage = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

try {
    // Build base query
    $sql = "SELECT p.*, 
            parc.tracking_number, 
            c.full_name as customer_name,
            c.customer_code
            FROM payments p
            JOIN parcels parc ON p.parcel_id = parc.id
            JOIN customers c ON parc.customer_id = c.id
            WHERE 1=1";
    $params = [];
    
    // Add search condition
    if ($search) {
        $sql .= " AND parc.tracking_number LIKE :search";
        $params['search'] = "%$search%";
    }
    
    // Add status filter
    if ($statusFilter) {
        $sql .= " AND p.payment_status = :status";
        $params['status'] = $statusFilter;
    }
    
    // Add date range filter
    if ($dateFrom) {
        $sql .= " AND p.payment_date >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND p.payment_date <= :date_to";
        $params['date_to'] = $dateTo;
    }
    
    // Get total count for pagination
    $countSql = str_replace("SELECT p.*, 
            parc.tracking_number, 
            c.full_name as customer_name,
            c.customer_code", "SELECT COUNT(*)", $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get paginated results
    $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $payments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Payment List Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading payments.');
    $payments = [];
    $totalRecords = 0;
    $totalPages = 0;
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Manage Payments</h2>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="card">
        <div class="card-body">
            <!-- Search and Filter Row -->
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex">
                            <input type="text" class="form-control me-2" name="search" placeholder="Search by tracking number..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if ($search || $statusFilter || $dateFrom || $dateTo): ?>
                                <a href="<?php echo BASE_URL; ?>modules/payments/list.php" class="btn btn-secondary ms-2">Clear</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="unpaid" <?php echo $statusFilter === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="partial" <?php echo $statusFilter === 'partial' ? 'selected' : ''; ?>>Partial Payment</option>
                            <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="cod_pending" <?php echo $statusFilter === 'cod_pending' ? 'selected' : ''; ?>>COD Pending</option>
                            <option value="cod_collected" <?php echo $statusFilter === 'cod_collected' ? 'selected' : ''; ?>>COD Collected</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From Date">
                    </div>
                    
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To Date">
                    </div>
                    
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
            
            <!-- Payments Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Sender</th>
                            <th>Delivery Charge</th>
                            <th>COD Amount</th>
                            <th>Paid Amount</th>
                            <th>Due Amount</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No payment records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($payment['tracking_number']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($payment['customer_code']); ?> - 
                                        <?php echo htmlspecialchars($payment['customer_name']); ?>
                                    </td>
                                    <td><?php echo number_format($payment['delivery_charge'], 2); ?></td>
                                    <td><?php echo $payment['cod_amount'] > 0 ? number_format($payment['cod_amount'], 2) : '-'; ?></td>
                                    <td><?php echo number_format($payment['paid_amount'], 2); ?></td>
                                    <td><?php echo number_format($payment['due_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getPaymentStatusBadgeClass($payment['payment_status']); ?>">
                                            <?php echo formatPaymentStatus($payment['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $payment['payment_date'] ? date('M d, Y', strtotime($payment['payment_date'])) : '-'; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>modules/payments/view.php?id=<?php echo $payment['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if (hasPermission($pdo, 'payments', 'create') && !in_array($payment['payment_status'], ['paid', 'cod_collected'])): ?>
                                        <a href="<?php echo BASE_URL; ?>modules/payments/record-payment.php?parcel_id=<?php echo $payment['parcel_id']; ?>" 
                                           class="btn btn-sm btn-success" title="Record Payment">
                                            <i class="fas fa-dollar-sign"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center text-muted">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalRecords); ?> of <?php echo $totalRecords; ?> payment records
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
