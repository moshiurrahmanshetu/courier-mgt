<?php
// Courier Management System - Payment Report
// Created: 2026-08-08

$pageTitle = 'Payment Report';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once 'helpers.php';

// Check if user has permission
if (!hasPermission($pdo, 'reports', 'view')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Pagination settings
$perPage = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Filter parameters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$methodFilter = $_GET['payment_method'] ?? '';

try {
    // Build base query
    $sql = "SELECT 
            pt.amount,
            pt.payment_method,
            pt.transaction_note,
            pt.recorded_at,
            CONCAT(u.full_name, ' (', u.username, ')') as recorded_by_name,
            p.tracking_number
            FROM payment_transactions pt
            JOIN payments pay ON pt.payment_id = pay.id
            JOIN parcels p ON pay.parcel_id = p.id
            LEFT JOIN users u ON pt.recorded_by = u.id
            WHERE 1=1";
    $params = [];
    
    // Add date range filter
    if ($dateFrom) {
        $sql .= " AND DATE(pt.recorded_at) >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND DATE(pt.recorded_at) <= :date_to";
        $params['date_to'] = $dateTo;
    }
    
    // Add payment method filter
    if ($methodFilter) {
        $sql .= " AND pt.payment_method = :payment_method";
        $params['payment_method'] = $methodFilter;
    }
    
    // Get total count for pagination
    $countSql = str_replace("SELECT 
            pt.amount,
            pt.payment_method,
            pt.transaction_note,
            pt.recorded_at,
            CONCAT(u.full_name, ' (', u.username, ')') as recorded_by_name,
            p.tracking_number", "SELECT COUNT(*)", $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get paginated results
    $sql .= " ORDER BY pt.recorded_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $transactions = $stmt->fetchAll();
    
    // Get summary stats
    $summarySql = str_replace("SELECT 
            pt.amount,
            pt.payment_method,
            pt.transaction_note,
            pt.recorded_at,
            CONCAT(u.full_name, ' (', u.username, ')') as recorded_by_name,
            p.tracking_number", "SELECT 
            COALESCE(SUM(pt.amount), 0) as total_collected,
            COUNT(*) as total_transactions", $sql);
    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();
    
    // Get breakdown by payment method
    $breakdownSql = "SELECT 
            pt.payment_method,
            COUNT(*) as count,
            COALESCE(SUM(pt.amount), 0) as total
            FROM payment_transactions pt
            JOIN payments pay ON pt.payment_id = pay.id
            WHERE 1=1";
    $breakdownParams = [];
    
    if ($dateFrom) {
        $breakdownSql .= " AND DATE(pt.recorded_at) >= :date_from";
        $breakdownParams['date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $breakdownSql .= " AND DATE(pt.recorded_at) <= :date_to";
        $breakdownParams['date_to'] = $dateTo;
    }
    
    if ($methodFilter) {
        $breakdownSql .= " AND pt.payment_method = :payment_method";
        $breakdownParams['payment_method'] = $methodFilter;
    }
    
    $breakdownSql .= " GROUP BY pt.payment_method ORDER BY count DESC";
    $breakdownStmt = $pdo->prepare($breakdownSql);
    $breakdownStmt->execute($breakdownParams);
    $breakdown = $breakdownStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Payment Report Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    setFlashMessage('error', 'An error occurred while loading the report.');
    $transactions = [];
    $totalRecords = 0;
    $totalPages = 0;
    $summary = ['total_collected' => 0, 'total_transactions' => 0];
    $breakdown = [];
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvData = [];
    $csvHeaders = ['Tracking Number', 'Amount', 'Method', 'Recorded By', 'Recorded At', 'Note'];
    
    foreach ($transactions as $txn) {
        $csvData[] = [
            $txn['tracking_number'],
            $txn['amount'],
            $txn['payment_method'] ?? 'N/A',
            $txn['recorded_by_name'] ?? 'System',
            formatDateTimeForCSV($txn['recorded_at']),
            $txn['transaction_note'] ?? ''
        ];
    }
    
    exportToCSV($csvData, $csvHeaders, 'payment_report_' . date('Ymd') . '.csv');
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Payment Report</h2>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="card">
        <div class="card-body">
            <!-- Filter Bar -->
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From Date">
                    </div>
                    
                    <div class="col-md-4">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To Date">
                    </div>
                    
                    <div class="col-md-4">
                        <select class="form-select" name="payment_method">
                            <option value="">All Methods</option>
                            <option value="Cash" <?php echo $methodFilter === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="Mobile Banking" <?php echo $methodFilter === 'Mobile Banking' ? 'selected' : ''; ?>>Mobile Banking</option>
                            <option value="Bank Transfer" <?php echo $methodFilter === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="Card" <?php echo $methodFilter === 'Card' ? 'selected' : ''; ?>>Card</option>
                            <option value="Other" <?php echo $methodFilter === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-2">
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                        <?php if ($dateFrom || $dateTo || $methodFilter): ?>
                            <a href="<?php echo BASE_URL; ?>modules/reports/payment-report.php" class="btn btn-secondary mt-2 d-block">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            
            <!-- Summary Line -->
            <div class="row mb-3">
                <div class="col-12">
                    <small class="text-muted">
                        <strong>Summary:</strong> 
                        Total Amount Collected: <?php echo number_format($summary['total_collected'], 2); ?> | 
                        Total Transactions: <?php echo $summary['total_transactions']; ?>
                        <?php if (!empty($breakdown)): ?>
                            | Breakdown: 
                            <?php 
                            $breakdownTexts = [];
                            foreach ($breakdown as $item) {
                                $breakdownTexts[] = htmlspecialchars($item['payment_method'] ?? 'N/A') . ': ' . $item['count'];
                            }
                            echo implode(' | ', $breakdownTexts);
                            ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
            
            <!-- Export Button -->
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <a href="<?php echo $_SERVER['REQUEST_URI']; ?>&export=csv" class="btn btn-success">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </a>
                </div>
            </div>
            
            <!-- Results Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Recorded By</th>
                            <th>Recorded At</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($txn['tracking_number']); ?></strong></td>
                                    <td class="fw-bold text-success"><?php echo number_format($txn['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($txn['payment_method'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($txn['recorded_by_name'] ?? 'System'); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($txn['recorded_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($txn['transaction_note'] ?? '-'); ?></td>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&payment_method=<?php echo urlencode($methodFilter); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&payment_method=<?php echo urlencode($methodFilter); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&payment_method=<?php echo urlencode($methodFilter); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center text-muted">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalRecords); ?> of <?php echo $totalRecords; ?> records
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
