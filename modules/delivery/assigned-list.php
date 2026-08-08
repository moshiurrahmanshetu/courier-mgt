<?php
// Courier Management System - All Assigned Deliveries List
// Created: 2026-08-08

$pageTitle = 'All Assigned Deliveries';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once '../parcels/helpers.php';

// Check if user has permission (Admin/Staff only)
if (!hasPermission($pdo, 'delivery', 'view')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Pagination settings
$perPage = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Filter parameters
$staffFilter = $_GET['staff'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

try {
    // Build base query for assigned parcels only
    $sql = "SELECT p.*, 
            c.full_name as customer_name, c.customer_code,
            CONCAT(u.full_name, ' (', u.username, ')') as delivery_staff_name
            FROM parcels p
            LEFT JOIN customers c ON p.customer_id = c.id
            LEFT JOIN users u ON p.delivery_staff_id = u.id
            WHERE p.delivery_staff_id IS NOT NULL AND p.is_deleted = 0";
    $params = [];
    
    // Add staff filter
    if ($staffFilter) {
        $sql .= " AND p.delivery_staff_id = :staff_id";
        $params['staff_id'] = $staffFilter;
    }
    
    // Add status filter
    if ($statusFilter) {
        $sql .= " AND p.current_status = :status";
        $params['status'] = $statusFilter;
    }
    
    // Add date range filter
    if ($dateFrom) {
        $sql .= " AND p.booking_date >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND p.booking_date <= :date_to";
        $params['date_to'] = $dateTo;
    }
    
    // Get total count for pagination
    $countSql = str_replace("SELECT p.*, c.full_name as customer_name, c.customer_code,
            CONCAT(u.full_name, ' (', u.username, ')') as delivery_staff_name", "SELECT COUNT(*)", $sql);
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
    
    // Get delivery staff for filter dropdown
    $staffStmt = $pdo->query("
        SELECT u.id, u.full_name, u.username 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE r.role_name = 'Delivery Staff' AND u.is_active = 1
        ORDER BY u.full_name
    ");
    $deliveryStaff = $staffStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Assigned List Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading assigned parcels.');
    $parcels = [];
    $totalRecords = 0;
    $totalPages = 0;
    $deliveryStaff = [];
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">All Assigned Deliveries</h2>
            <a href="<?php echo BASE_URL; ?>modules/delivery/staff-list.php" class="btn btn-secondary">Staff Workload</a>
            <a href="<?php echo BASE_URL; ?>modules/delivery/assign.php" class="btn btn-success">Assign Parcels</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="card">
        <div class="card-body">
            <!-- Filter Row -->
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <select class="form-select" name="staff">
                            <option value="">All Delivery Staff</option>
                            <?php foreach ($deliveryStaff as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>" <?php echo $staffFilter == $staff['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="picked_up" <?php echo $statusFilter === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                            <option value="in_transit" <?php echo $statusFilter === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                            <option value="out_for_delivery" <?php echo $statusFilter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="failed_delivery" <?php echo $statusFilter === 'failed_delivery' ? 'selected' : ''; ?>>Failed Delivery</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From Date">
                    </div>
                    
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To Date">
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                        <?php if ($staffFilter || $statusFilter || $dateFrom || $dateTo): ?>
                            <a href="<?php echo BASE_URL; ?>modules/delivery/assigned-list.php" class="btn btn-secondary mt-2 d-block">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            
            <!-- Assigned Parcels Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Receiver Name</th>
                            <th>Receiver Phone</th>
                            <th>Delivery Staff</th>
                            <th>Status</th>
                            <th>Booking Date</th>
                            <th>Expected Delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parcels)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No assigned parcels found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parcels as $parcel): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($parcel['tracking_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($parcel['receiver_name']); ?></td>
                                    <td><?php echo htmlspecialchars($parcel['receiver_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($parcel['delivery_staff_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($parcel['current_status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($parcel['booking_date'])); ?></td>
                                    <td><?php echo $parcel['expected_delivery_date'] ? date('M d, Y', strtotime($parcel['expected_delivery_date'])) : '-'; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $parcel['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&staff=<?php echo urlencode($staffFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&staff=<?php echo urlencode($staffFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&staff=<?php echo urlencode($staffFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center text-muted">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalRecords); ?> of <?php echo $totalRecords; ?> assigned parcels
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
