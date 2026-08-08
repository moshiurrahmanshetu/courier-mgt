<?php
// Courier Management System - Parcel Report
// Created: 2026-08-08

$pageTitle = 'Parcel Report';
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
$statusFilter = $_GET['status'] ?? '';

try {
    // Build base query
    $sql = "SELECT p.*, 
            c.full_name as customer_name, 
            c.customer_code
            FROM parcels p
            LEFT JOIN customers c ON p.customer_id = c.id
            WHERE p.is_deleted = 0";
    $params = [];
    
    // Add date range filter
    if ($dateFrom) {
        $sql .= " AND p.booking_date >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND p.booking_date <= :date_to";
        $params['date_to'] = $dateTo;
    }
    
    // Add status filter
    if ($statusFilter) {
        $sql .= " AND p.current_status = :status";
        $params['status'] = $statusFilter;
    }
    
    // Get total count for pagination
    $countSql = str_replace("SELECT p.*, 
            c.full_name as customer_name, 
            c.customer_code", "SELECT COUNT(*)", $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get paginated results
    $sql .= " ORDER BY p.booking_date DESC, p.id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $parcels = $stmt->fetchAll();
    
    // Get summary stats
    $summarySql = str_replace("SELECT p.*, 
            c.full_name as customer_name, 
            c.customer_code", "SELECT 
            COUNT(*) as total,
            COALESCE(SUM(p.delivery_charge), 0) as total_delivery_charge,
            COALESCE(SUM(p.cod_amount), 0) as total_cod", $sql);
    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();
    
} catch (PDOException $e) {
    error_log("Parcel Report Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    setFlashMessage('error', 'An error occurred while loading the report.');
    $parcels = [];
    $totalRecords = 0;
    $totalPages = 0;
    $summary = ['total' => 0, 'total_delivery_charge' => 0, 'total_cod' => 0];
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvData = [];
    $csvHeaders = ['Tracking Number', 'Sender', 'Receiver Name', 'Parcel Type', 'Status', 'Delivery Charge', 'COD Amount', 'Booking Date'];
    
    foreach ($parcels as $parcel) {
        $csvData[] = [
            $parcel['tracking_number'],
            $parcel['customer_code'] . ' - ' . $parcel['customer_name'],
            $parcel['receiver_name'],
            $parcel['parcel_type'],
            ucfirst(str_replace('_', ' ', $parcel['current_status'])),
            $parcel['delivery_charge'],
            $parcel['cod_amount'],
            formatDateForCSV($parcel['booking_date'])
        ];
    }
    
    exportToCSV($csvData, $csvHeaders, 'parcel_report_' . date('Ymd') . '.csv');
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Parcel Report</h2>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="card">
        <div class="card-body">
            <!-- Filter Bar -->
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From Date">
                    </div>
                    
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To Date">
                    </div>
                    
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="picked_up" <?php echo $statusFilter === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                            <option value="in_transit" <?php echo $statusFilter === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                            <option value="out_for_delivery" <?php echo $statusFilter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="failed_delivery" <?php echo $statusFilter === 'failed_delivery' ? 'selected' : ''; ?>>Failed Delivery</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                        <?php if ($dateFrom || $dateTo || $statusFilter): ?>
                            <a href="<?php echo BASE_URL; ?>modules/reports/parcel-report.php" class="btn btn-secondary mt-2 d-block">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            
            <!-- Summary Line -->
            <div class="row mb-3">
                <div class="col-12">
                    <small class="text-muted">
                        <strong>Summary:</strong> 
                        Total Records: <?php echo $summary['total']; ?> | 
                        Total Delivery Charge: <?php echo number_format($summary['total_delivery_charge'], 2); ?> | 
                        Total COD: <?php echo number_format($summary['total_cod'], 2); ?>
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
                            <th>Sender</th>
                            <th>Receiver Name</th>
                            <th>Parcel Type</th>
                            <th>Status</th>
                            <th>Delivery Charge</th>
                            <th>COD Amount</th>
                            <th>Booking Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parcels)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parcels as $parcel): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($parcel['tracking_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($parcel['customer_code'] . ' - ' . $parcel['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($parcel['receiver_name']); ?></td>
                                    <td><?php echo htmlspecialchars($parcel['parcel_type']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?></td>
                                    <td><?php echo number_format($parcel['delivery_charge'], 2); ?></td>
                                    <td><?php echo $parcel['cod_amount'] > 0 ? number_format($parcel['cod_amount'], 2) : '-'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($parcel['booking_date'])); ?></td>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&status=<?php echo urlencode($statusFilter); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&status=<?php echo urlencode($statusFilter); ?>">Next</a>
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
