<?php
// Courier Management System - Parcel List
// Created: 2026-08-08

$pageTitle = 'Manage Parcels';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once 'helpers.php';

// Check if user has permission
if (!hasPermission($pdo, 'parcels', 'view')) {
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
$staffFilter = $_GET['staff'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

try {
    // Build base query
    $sql = "SELECT p.*, c.full_name as customer_name, c.customer_code, 
            CONCAT(u.full_name, ' (', u.username, ')') as delivery_staff_name
            FROM parcels p
            LEFT JOIN customers c ON p.customer_id = c.id
            LEFT JOIN users u ON p.delivery_staff_id = u.id
            WHERE p.is_deleted = 0";
    $params = [];
    
    // Add search conditions
    if ($search) {
        $sql .= " AND (p.tracking_number LIKE :search OR p.receiver_name LIKE :search OR p.receiver_phone LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    // Add status filter
    if ($statusFilter) {
        $sql .= " AND p.current_status = :status";
        $params['status'] = $statusFilter;
    }
    
    // Add delivery staff filter
    if ($staffFilter === 'unassigned') {
        $sql .= " AND p.delivery_staff_id IS NULL";
    } elseif ($staffFilter) {
        $sql .= " AND p.delivery_staff_id = :staff_id";
        $params['staff_id'] = $staffFilter;
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
    error_log("Parcel List Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading parcels.');
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
            <h2 class="page-title">Manage Parcels</h2>
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
                            <input type="text" class="form-control me-2" name="search" placeholder="Search by tracking number, receiver..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if ($search || $statusFilter || $staffFilter || $dateFrom || $dateTo): ?>
                                <a href="<?php echo BASE_URL; ?>modules/parcels/list.php" class="btn btn-secondary ms-2">Clear</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
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
                    
                    <div class="col-md-2">
                        <select class="form-select" name="staff">
                            <option value="">All Staff</option>
                            <option value="unassigned" <?php echo $staffFilter === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                            <?php foreach ($deliveryStaff as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>" <?php echo $staffFilter == $staff['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From Date">
                    </div>
                    
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To Date">
                    </div>
                </div>
            </form>
            
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <?php if (hasPermission($pdo, 'parcels', 'create')): ?>
                    <a href="<?php echo BASE_URL; ?>modules/parcels/create.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Book New Parcel
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Parcel Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Sender</th>
                            <th>Receiver</th>
                            <th>Receiver Phone</th>
                            <th>Type</th>
                            <th>COD</th>
                            <th>Status</th>
                            <th>Delivery Staff</th>
                            <th>Booking Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parcels)): ?>
                            <tr>
                                <td colspan="10" class="text-center">No parcels found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parcels as $parcel): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($parcel['tracking_number']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($parcel['customer_code']); ?> - 
                                        <?php echo htmlspecialchars($parcel['customer_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($parcel['receiver_name']); ?></td>
                                    <td><?php echo htmlspecialchars($parcel['receiver_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($parcel['parcel_type']); ?></td>
                                    <td>
                                        <?php if ($parcel['cod_amount'] > 0): ?>
                                            <?php echo number_format($parcel['cod_amount'], 2); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($parcel['current_status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($parcel['delivery_staff_name'] ?? 'Unassigned'); ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($parcel['booking_date'])); ?></td>
                                    <td>
                                        <?php if (hasPermission($pdo, 'parcels', 'view')): ?>
                                        <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $parcel['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission($pdo, 'parcels', 'edit')): ?>
                                        <a href="<?php echo BASE_URL; ?>modules/parcels/update-status.php?id=<?php echo $parcel['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Update Status">
                                            <i class="fas fa-exchange-alt"></i>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&staff=<?php echo urlencode($staffFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&staff=<?php echo urlencode($staffFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&staff=<?php echo urlencode($staffFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center text-muted">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalRecords); ?> of <?php echo $totalRecords; ?> parcels
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>



<?php require_once '../../includes/footer.php'; ?>
