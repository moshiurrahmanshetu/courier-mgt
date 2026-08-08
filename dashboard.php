<?php
// Courier Management System - Dashboard
// Created: 2026-08-08

$pageTitle = 'Dashboard';
require_once 'includes/auth_check.php';
require_once 'includes/role_check.php';
require_once 'config/db.php';
require_once 'includes/header.php';

// Get Delivery Staff summary if user is Delivery Staff
$deliveryStaffSummary = null;
if (hasRole(['Delivery Staff'])) {
    try {
        $userId = $_SESSION['user_id'];
        $today = date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM parcels WHERE delivery_staff_id = :user_id AND is_deleted = 0 AND current_status IN ('picked_up', 'in_transit', 'out_for_delivery')) as pending_count,
                (SELECT COUNT(*) FROM parcels WHERE delivery_staff_id = :user_id AND is_deleted = 0 AND current_status = 'delivered' AND DATE(updated_at) = :today) as delivered_today_count,
                (SELECT COUNT(*) FROM parcels WHERE delivery_staff_id = :user_id AND is_deleted = 0 AND current_status = 'failed_delivery') as failed_count
        ");
        $stmt->execute(['user_id' => $userId, 'today' => $today]);
        $deliveryStaffSummary = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Dashboard Summary Error: " . $e->getMessage());
        $deliveryStaffSummary = ['pending_count' => 0, 'delivered_today_count' => 0, 'failed_count' => 0];
    }
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo htmlspecialchars($_SESSION['role_name']); ?>)</h2>
            <p class="text-muted">This is your dashboard. Use the sidebar to navigate through the system.</p>
        </div>
    </div>
    
    <?php if ($deliveryStaffSummary): ?>
    <!-- Delivery Staff Summary Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Pending Delivery</h5>
                    <p class="card-text stat-number"><?php echo $deliveryStaffSummary['pending_count']; ?></p>
                    <a href="<?php echo BASE_URL; ?>modules/delivery/my-deliveries.php?tab=pending" class="btn btn-sm btn-primary mt-2">View Pending</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Delivered Today</h5>
                    <p class="card-text stat-number"><?php echo $deliveryStaffSummary['delivered_today_count']; ?></p>
                    <a href="<?php echo BASE_URL; ?>modules/delivery/my-deliveries.php?tab=delivered" class="btn btn-sm btn-success mt-2">View Delivered</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Failed Delivery</h5>
                    <p class="card-text stat-number"><?php echo $deliveryStaffSummary['failed_count']; ?></p>
                    <a href="<?php echo BASE_URL; ?>modules/delivery/my-deliveries.php?tab=failed" class="btn btn-sm btn-danger mt-2">View Failed</a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Placeholder cards for future dashboard stats (Admin/Staff) -->
    <div class="row">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <p class="card-text stat-number">-</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Pending Deliveries</h5>
                    <p class="card-text stat-number">-</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Completed Today</h5>
                    <p class="card-text stat-number">-</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Active Users</h5>
                    <p class="card-text stat-number">-</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Additional placeholder content for future modules -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Activity</h5>
                    <p class="text-muted">Recent activity log will appear here in future phases.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
