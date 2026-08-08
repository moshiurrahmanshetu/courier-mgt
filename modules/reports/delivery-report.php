<?php
// Courier Management System - Delivery Report
// Created: 2026-08-08

$pageTitle = 'Delivery Report';
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
$staffFilter = $_GET['staff_id'] ?? '';

try {
    // Get delivery staff list for filter dropdown
    $staffStmt = $pdo->query("SELECT id, full_name FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Delivery Staff') AND is_active = 1 ORDER BY full_name");
    $staffList = $staffStmt->fetchAll();
    
    // Build base query - join parcels with status log for delivery outcomes
    $sql = "SELECT 
            p.tracking_number,
            p.receiver_name,
            CONCAT(u.full_name, ' (', u.username, ')') as delivery_staff_name,
            psl.status as delivery_status,
            psl.changed_at as delivery_date,
            psl.note as delivery_note
            FROM parcel_status_log psl
            JOIN parcels p ON psl.parcel_id = p.id
            LEFT JOIN users u ON p.delivery_staff_id = u.id
            WHERE psl.status IN ('delivered', 'failed_delivery')";
    $params = [];
    
    // Add date range filter
    if ($dateFrom) {
        $sql .= " AND DATE(psl.changed_at) >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND DATE(psl.changed_at) <= :date_to";
        $params['date_to'] = $dateTo;
    }
    
    // Add status filter
    if ($statusFilter) {
        $sql .= " AND psl.status = :status";
        $params['status'] = $statusFilter;
    }
    
    // Add staff filter
    if ($staffFilter) {
        $sql .= " AND p.delivery_staff_id = :staff_id";
        $params['staff_id'] = $staffFilter;
    }
    
    // Get total count for pagination
    $countSql = str_replace("SELECT 
            p.tracking_number,
            p.receiver_name,
            CONCAT(u.full_name, ' (', u.username, ')') as delivery_staff_name,
            psl.status as delivery_status,
            psl.changed_at as delivery_date,
            psl.note as delivery_note", "SELECT COUNT(*)", $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get paginated results
    $sql .= " ORDER BY psl.changed_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $deliveries = $stmt->fetchAll();
    
    // Get summary stats
    $summarySql = str_replace("SELECT 
            p.tracking_number,
            p.receiver_name,
            CONCAT(u.full_name, ' (', u.username, ')') as delivery_staff_name,
            psl.status as delivery_status,
            psl.changed_at as delivery_date,
            psl.note as delivery_note", "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN psl.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN psl.status = 'failed_delivery' THEN 1 ELSE 0 END) as failed_count", $sql);
    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();
    
    // Calculate success rate
    $totalDeliveries = $summary['delivered_count'] + $summary['failed_count'];
    $successRate = $totalDeliveries > 0 ? round(($summary['delivered_count'] / $totalDeliveries) * 100, 1) : 0;
    
} catch (PDOException $e) {
    error_log("Delivery Report Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    setFlashMessage('error', 'An error occurred while loading the report.');
    $deliveries = [];
    $staffList = [];
    $totalRecords = 0;
    $totalPages = 0;
    $summary = ['total' => 0, 'delivered_count' => 0, 'failed_count' => 0];
    $successRate = 0;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvData = [];
    $csvHeaders = ['Tracking Number', 'Receiver Name', 'Delivery Staff', 'Status', 'Date', 'Note'];
    
    foreach ($deliveries as $delivery) {
        $csvData[] = [
            $delivery['tracking_number'],
            $delivery['receiver_name'],
            $delivery['delivery_staff_name'] ?? 'Unassigned',
            ucfirst(str_replace('_', ' ', $delivery['delivery_status'])),
            formatDateTimeForCSV($delivery['delivery_date']),
            $delivery['delivery_note'] ?? ''
        ];
    }
    
    exportToCSV($csvData, $csvHeaders, 'delivery_report_' . date('Ymd') . '.csv');
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Delivery Report</h2>
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
                            <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="failed_delivery" <?php echo $statusFilter === 'failed_delivery' ? 'selected' : ''; ?>>Failed Delivery</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <select class="form-select" name="staff_id">
                            <option value="">All Staff</option>
                            <?php foreach ($staffList as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>" <?php echo $staffFilter == $staff['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-2">
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                        <?php if ($dateFrom || $dateTo || $statusFilter || $staffFilter): ?>
                            <a href="<?php echo BASE_URL; ?>modules/reports/delivery-report.php" class="btn btn-secondary mt-2 d-block">Clear</a>
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
                        Delivered: <?php echo $summary['delivered_count']; ?> | 
                        Failed: <?php echo $summary['failed_count']; ?> | 
                        Success Rate: <?php echo $successRate; ?>%
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
                            <th>Receiver Name</th>
                            <th>Delivery Staff</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deliveries)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($deliveries as $delivery): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($delivery['tracking_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($delivery['receiver_name']); ?></td>
                                    <td><?php echo htmlspecialchars($delivery['delivery_staff_name'] ?? 'Unassigned'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $delivery['delivery_status'] === 'delivered' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $delivery['delivery_status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($delivery['delivery_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($delivery['delivery_note'] ?? '-'); ?></td>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&status=<?php echo urlencode($statusFilter); ?>&staff_id=<?php echo urlencode($staffFilter); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&status=<?php echo urlencode($statusFilter); ?>&staff_id=<?php echo urlencode($staffFilter); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&status=<?php echo urlencode($statusFilter); ?>&staff_id=<?php echo urlencode($staffFilter); ?>">Next</a>
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
