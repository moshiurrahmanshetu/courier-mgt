<?php
// Courier Management System - View Customer
// Created: 2026-08-08

$pageTitle = 'View Customer';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once '../parcels/helpers.php';

// Check if user has permission
if (!hasPermission($pdo, 'customers', 'view')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$customerId = $_GET['id'] ?? 0;

if (!$customerId) {
    setFlashMessage('error', 'Invalid customer ID.');
    header('Location: ' . BASE_URL . 'modules/customers/list.php');
    exit;
}

// Fetch customer data
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->execute(['id' => $customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        setFlashMessage('error', 'Customer not found.');
        header('Location: ' . BASE_URL . 'modules/customers/list.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Fetch Customer Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading customer data.');
    header('Location: ' . BASE_URL . 'modules/customers/list.php');
    exit;
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">View Customer Details</h2>
            <a href="<?php echo BASE_URL; ?>modules/customers/list.php" class="btn btn-secondary">Back to Customers</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Customer Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Customer Code</label>
                            <div class="form-control-plaintext fw-bold"><?php echo htmlspecialchars($customer['customer_code']); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <div>
                                <?php if ($customer['status'] === 'active'): ?>
                                    <span class="badge bg-success fs-6">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary fs-6">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Full Name</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Phone</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($customer['phone']); ?></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Email</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($customer['email'] ?? '-'); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">City/Area</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($customer['city_area']); ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Address</label>
                        <div class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($customer['address'])); ?></div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Created Date</label>
                            <div class="form-control-plaintext"><?php echo date('M d, Y g:i A', strtotime($customer['created_at'])); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Last Updated</label>
                            <div class="form-control-plaintext">
                                <?php 
                                    if ($customer['updated_at'] == $customer['created_at']) {
                                        echo 'Never updated';
                                    } else {
                                        echo date('M d, Y g:i A', strtotime($customer['updated_at']));
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Created By</label>
                        <div class="form-control-plaintext">User ID: <?php echo $customer['created_by'] ?? 'N/A'; ?></div>
                    </div>
                    
                    <div class="mt-4">
                        <?php if (hasPermission($pdo, 'customers', 'edit')): ?>
                        <a href="<?php echo BASE_URL; ?>modules/customers/edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Customer
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>modules/customers/list.php" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Quick Actions</h5>
                    
                    <div class="d-grid gap-2">
                        <?php if (hasPermission($pdo, 'customers', 'edit')): ?>
                        <a href="<?php echo BASE_URL; ?>modules/customers/edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Customer
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($pdo, 'customers', 'delete')): ?>
                            <?php if ($customer['is_deleted'] == 0): ?>
                                <form method="POST" action="toggle-status.php" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                    <input type="hidden" name="action" value="soft-delete">
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash"></i> Delete Customer
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="toggle-status.php" onsubmit="return confirm('Are you sure you want to restore this customer?');">
                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                    <input type="hidden" name="action" value="restore">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-undo"></i> Restore Customer
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Parcel History -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title mb-4">Parcel History</h5>
                    
                    <?php
                    try {
                        $parcelStmt = $pdo->prepare("
                            SELECT p.*, 
                                   CONCAT(u.full_name, ' (', u.username, ')') as delivery_staff_name
                            FROM parcels p
                            LEFT JOIN users u ON p.delivery_staff_id = u.id
                            WHERE p.customer_id = :customer_id AND p.is_deleted = 0
                            ORDER BY p.booking_date DESC, p.id DESC
                            LIMIT 10
                        ");
                        $parcelStmt->execute(['customer_id' => $customer['id']]);
                        $customerParcels = $parcelStmt->fetchAll();
                        
                        if (empty($customerParcels)): ?>
                            <p class="text-muted">
                                <i class="fas fa-box"></i> No parcels found for this customer.
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tracking #</th>
                                            <th>Status</th>
                                            <th>Booking Date</th>
                                            <th>COD Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customerParcels as $parcel): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $parcel['id']; ?>">
                                                    <?php echo htmlspecialchars($parcel['tracking_number']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusBadgeClass($parcel['current_status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($parcel['booking_date'])); ?></td>
                                            <td>
                                                <?php if ($parcel['cod_amount'] > 0): ?>
                                                    <?php echo number_format($parcel['cod_amount'], 2); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">Showing latest 10 parcels</small>
                        <?php endif;
                    } catch (PDOException $e) {
                        error_log("Fetch Customer Parcels Error: " . $e->getMessage());
                        echo '<p class="text-danger">Error loading parcel history.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
