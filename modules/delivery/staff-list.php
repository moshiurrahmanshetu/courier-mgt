<?php
// Courier Management System - Delivery Staff Workload List
// Created: 2026-08-08

$pageTitle = 'Delivery Staff Workload';
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

try {
    // Get Delivery Staff role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = 'Delivery Staff'");
    $stmt->execute();
    $deliveryStaffRole = $stmt->fetch();
    
    if (!$deliveryStaffRole) {
        setFlashMessage('error', 'Delivery Staff role not found.');
        $staffList = [];
    } else {
        // Get all delivery staff users with their workload statistics
        $sql = "SELECT u.*, 
                (SELECT COUNT(*) FROM parcels WHERE delivery_staff_id = u.id AND current_status IN ('picked_up', 'in_transit', 'out_for_delivery') AND is_deleted = 0) as active_assigned,
                (SELECT COUNT(*) FROM parcels WHERE delivery_staff_id = u.id AND current_status = 'delivered' AND is_deleted = 0) as total_delivered,
                (SELECT COUNT(*) FROM parcels WHERE delivery_staff_id = u.id AND current_status = 'failed_delivery' AND is_deleted = 0) as total_failed
                FROM users u
                WHERE u.role_id = :role_id
                ORDER BY u.full_name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['role_id' => $deliveryStaffRole['id']]);
        $staffList = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Staff List Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading delivery staff data.');
    $staffList = [];
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Delivery Staff Workload</h2>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12">
                    <h5 class="card-title">Overview of Delivery Staff Performance</h5>
                    <p class="text-muted">Track active assignments, deliveries, and failed delivery attempts per staff member.</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Staff Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Active Assigned</th>
                            <th>Total Delivered</th>
                            <th>Total Failed</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staffList)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No delivery staff found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($staffList as $staff): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($staff['full_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($staff['username']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($staff['phone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-primary fs-6"><?php echo $staff['active_assigned']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success fs-6"><?php echo $staff['total_delivered']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger fs-6"><?php echo $staff['total_failed']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($staff['is_active'] == 1): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Active Assigned includes parcels in status: Picked Up, In Transit, or Out for Delivery.
                    <br>
                    To add or manage delivery staff users, use the <a href="<?php echo BASE_URL; ?>modules/users/list.php">User Management</a> module.
                </small>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
