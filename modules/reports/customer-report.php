<?php
// Courier Management System - Customer Report
// Created: 2026-08-08

$pageTitle = 'Customer Report';
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
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM parcels WHERE customer_id = c.id AND is_deleted = 0) as total_parcels
            FROM customers c
            WHERE 1=1";
    $params = [];
    
    // Add date range filter
    if ($dateFrom) {
        $sql .= " AND DATE(c.created_at) >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND DATE(c.created_at) <= :date_to";
        $params['date_to'] = $dateTo;
    }
    
    // Add status filter
    if ($statusFilter) {
        $sql .= " AND c.status = :status";
        $params['status'] = $statusFilter;
    }
    
    // Get total count for pagination
    $countSql = str_replace("SELECT c.*, 
            (SELECT COUNT(*) FROM parcels WHERE customer_id = c.id AND is_deleted = 0) as total_parcels", "SELECT COUNT(*)", $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get paginated results
    $sql .= " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $customers = $stmt->fetchAll();
    
    // Get summary stats
    $summarySql = str_replace("SELECT c.*, 
            (SELECT COUNT(*) FROM parcels WHERE customer_id = c.id AND is_deleted = 0) as total_parcels", "SELECT COUNT(*) as total", $sql);
    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();
    
} catch (PDOException $e) {
    error_log("Customer Report Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    setFlashMessage('error', 'An error occurred while loading the report.');
    $customers = [];
    $totalRecords = 0;
    $totalPages = 0;
    $summary = ['total' => 0];
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvData = [];
    $csvHeaders = ['Customer Code', 'Name', 'Phone', 'City/Area', 'Total Parcels Booked', 'Status', 'Created Date'];
    
    foreach ($customers as $customer) {
        $csvData[] = [
            $customer['customer_code'],
            $customer['full_name'],
            $customer['phone'],
            $customer['city_area'],
            $customer['total_parcels'],
            ucfirst($customer['status']),
            formatDateForCSV($customer['created_at'])
        ];
    }
    
    exportToCSV($csvData, $csvHeaders, 'customer_report_' . date('Ymd') . '.csv');
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Customer Report</h2>
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
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-2">
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                        <?php if ($dateFrom || $dateTo || $statusFilter): ?>
                            <a href="<?php echo BASE_URL; ?>modules/reports/customer-report.php" class="btn btn-secondary mt-2 d-block">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            
            <!-- Summary Line -->
            <div class="row mb-3">
                <div class="col-12">
                    <small class="text-muted">
                        <strong>Summary:</strong> 
                        Total Customers: <?php echo $summary['total']; ?>
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
                            <th>Customer Code</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>City/Area</th>
                            <th>Total Parcels Booked</th>
                            <th>Status</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($customer['customer_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['city_area']); ?></td>
                                    <td><?php echo $customer['total_parcels']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $customer['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($customer['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
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
