<?php
// Courier Management System - Customer List
// Created: 2026-08-08

$pageTitle = 'Manage Customers';
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/role_check.php';

// Check if user has permission
if (!hasPermission($pdo, 'customers', 'view')) {
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
$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] == '1';

try {
    // Build base query
    $sql = "SELECT * FROM customers WHERE 1=1";
    $params = [];
    
    // Filter by deleted status
    if ($showDeleted) {
        $sql .= " AND is_deleted = 1";
    } else {
        $sql .= " AND is_deleted = 0";
    }
    
    // Add search conditions
    if ($search) {
        $sql .= " AND (full_name LIKE :search OR phone LIKE :search OR customer_code LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    // Add status filter
    if ($statusFilter) {
        $sql .= " AND status = :status";
        $params['status'] = $statusFilter;
    }
    
    // Get total count for pagination
    $countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get paginated results
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $customers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Customer List Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading customers.');
    $customers = [];
    $totalRecords = 0;
    $totalPages = 0;
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Manage Customers</h2>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="card">
        <div class="card-body">
            <!-- Search and Filter Row -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <form method="GET" class="d-flex">
                        <input type="text" class="form-control me-2" name="search" placeholder="Search by name, phone, or code..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if ($search || $statusFilter || $showDeleted): ?>
                            <a href="<?php echo BASE_URL; ?>modules/customers/list.php" class="btn btn-secondary ms-2">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter" onchange="filterByStatus()">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="showDeletedToggle" 
                               <?php echo $showDeleted ? 'checked' : ''; ?> 
                               onchange="toggleDeletedView()">
                        <label class="form-check-label" for="showDeletedToggle">
                            Show Deleted Customers
                        </label>
                    </div>
                </div>
                
                <div class="col-md-2 text-end">
                    <?php if (hasPermission($pdo, 'customers', 'create')): ?>
                    <a href="<?php echo BASE_URL; ?>modules/customers/create.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Customer
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Customer Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Customer Code</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>City/Area</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <?php echo $showDeleted ? 'No deleted customers found' : 'No customers found'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($customer['customer_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['city_area']); ?></td>
                                    <td>
                                        <?php if ($customer['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <?php if (hasPermission($pdo, 'customers', 'view')): ?>
                                        <a href="<?php echo BASE_URL; ?>modules/customers/view.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission($pdo, 'customers', 'edit')): ?>
                                        <a href="<?php echo BASE_URL; ?>modules/customers/edit.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission($pdo, 'customers', 'delete')): ?>
                                            <?php if ($showDeleted): ?>
                                                <!-- Restore button for deleted customers -->
                                                <form method="POST" action="toggle-status.php" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to restore this customer?');">
                                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                    <input type="hidden" name="action" value="restore">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Restore">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Soft delete button for active customers -->
                                                <form method="POST" action="toggle-status.php" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                    <input type="hidden" name="action" value="soft-delete">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&show_deleted=<?php echo $showDeleted ? '1' : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&show_deleted=<?php echo $showDeleted ? '1' : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&show_deleted=<?php echo $showDeleted ? '1' : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center text-muted">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalRecords); ?> of <?php echo $totalRecords; ?> customers
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function filterByStatus() {
    const statusFilter = document.getElementById('statusFilter').value;
    const searchParams = new URLSearchParams(window.location.search);
    
    if (statusFilter) {
        searchParams.set('status', statusFilter);
    } else {
        searchParams.delete('status');
    }
    
    // Reset to page 1 when filtering
    searchParams.delete('page');
    
    window.location.href = '<?php echo BASE_URL; ?>modules/customers/list.php?' + searchParams.toString();
}

function toggleDeletedView() {
    const showDeleted = document.getElementById('showDeletedToggle').checked;
    const searchParams = new URLSearchParams(window.location.search);
    
    if (showDeleted) {
        searchParams.set('show_deleted', '1');
    } else {
        searchParams.delete('show_deleted');
    }
    
    // Reset to page 1 when toggling
    searchParams.delete('page');
    
    window.location.href = '<?php echo BASE_URL; ?>modules/customers/list.php?' + searchParams.toString();
}
</script>

<?php require_once '../../includes/footer.php'; ?>
