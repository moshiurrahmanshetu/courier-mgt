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

// Get Admin/Staff summary stats
$adminStaffStats = null;
if (!hasRole(['Delivery Staff']) && (hasPermission($pdo, 'parcels', 'view') || hasRole(['Admin', 'Staff']))) {
    try {
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM customers WHERE is_deleted = 0) as total_customers,
                (SELECT COUNT(*) FROM parcels WHERE is_deleted = 0) as total_parcels,
                (SELECT COUNT(*) FROM parcels WHERE is_deleted = 0 AND current_status = 'pending') as pending_parcels,
                (SELECT COUNT(*) FROM parcels WHERE is_deleted = 0 AND current_status = 'in_transit') as in_transit,
                (SELECT COUNT(*) FROM parcels WHERE is_deleted = 0 AND current_status = 'out_for_delivery') as out_for_delivery,
                (SELECT COUNT(*) FROM parcels WHERE is_deleted = 0 AND current_status = 'delivered') as delivered,
                (SELECT COUNT(*) FROM parcels WHERE is_deleted = 0 AND current_status = 'failed_delivery') as failed_delivery,
                (SELECT COALESCE(SUM(cod_amount), 0) FROM parcels WHERE is_deleted = 0) as total_cod,
                (SELECT COALESCE(SUM(paid_amount), 0) FROM payments) as total_collected
        ");
        $adminStaffStats = $stmt->fetch();
        
        // Get parcel status distribution for chart
        $stmt = $pdo->query("
            SELECT current_status, COUNT(*) as count 
            FROM parcels 
            WHERE is_deleted = 0 
            GROUP BY current_status
            ORDER BY current_status
        ");
        $parcelStatusData = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Admin/Staff Dashboard Stats Error: " . $e->getMessage());
        $adminStaffStats = null;
        $parcelStatusData = [];
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
    <?php elseif ($adminStaffStats): ?>
    <!-- Admin/Staff Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Total Customers</h5>
                    <p class="card-text stat-number"><?php echo $adminStaffStats['total_customers']; ?></p>
                    <a href="<?php echo BASE_URL; ?>modules/customers/list.php" class="btn btn-sm btn-primary mt-2">View Customers</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Total Parcels</h5>
                    <p class="card-text stat-number"><?php echo $adminStaffStats['total_parcels']; ?></p>
                    <a href="<?php echo BASE_URL; ?>modules/parcels/list.php" class="btn btn-sm btn-info mt-2">View Parcels</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Pending Parcels</h5>
                    <p class="card-text stat-number"><?php echo $adminStaffStats['pending_parcels']; ?></p>
                    <a href="<?php echo BASE_URL; ?>modules/parcels/list.php?status=pending" class="btn btn-sm btn-warning mt-2">View Pending</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Delivered</h5>
                    <p class="card-text stat-number"><?php echo $adminStaffStats['delivered']; ?></p>
                    <a href="<?php echo BASE_URL; ?>modules/parcels/list.php?status=delivered" class="btn btn-sm btn-success mt-2">View Delivered</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">In Transit</h5>
                    <p class="card-text stat-number"><?php echo $adminStaffStats['in_transit']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Out for Delivery</h5>
                    <p class="card-text stat-number"><?php echo $adminStaffStats['out_for_delivery']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Failed Delivery</h5>
                    <p class="card-text stat-number"><?php echo $adminStaffStats['failed_delivery']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Total COD</h5>
                    <p class="card-text stat-number"><?php echo number_format($adminStaffStats['total_cod'], 2); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Total Collected</h5>
                    <p class="card-text stat-number"><?php echo number_format($adminStaffStats['total_collected'], 2); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Parcel Status Distribution Chart -->
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Parcel Status Distribution</h5>
                    <canvas id="parcelStatusChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Placeholder cards for future dashboard stats (other roles) -->
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
    
    <!-- Parcel Status Distribution Chart -->
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Parcel Status Distribution</h5>
                    <canvas id="parcelStatusChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($adminStaffStats && !empty($parcelStatusData)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('parcelStatusChart');
    if (ctx) {
        const statusLabels = <?php echo json_encode(array_column($parcelStatusData, 'current_status')); ?>;
        const statusCounts = <?php echo json_encode(array_column($parcelStatusData, 'count')); ?>;
        const statusColors = ['#6c757d', '#17a2b8', '#0d6efd', '#ffc107', '#198754', '#dc3545', '#343a40'];
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: statusLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' ')),
                datasets: [{
                    label: 'Parcels',
                    data: statusCounts,
                    backgroundColor: statusColors,
                    borderColor: statusColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

<?php if ($adminStaffStats && !empty($parcelStatusData)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('parcelStatusChart');
    if (ctx) {
        const statusLabels = <?php echo json_encode(array_column($parcelStatusData, 'current_status')); ?>;
        const statusCounts = <?php echo json_encode(array_column($parcelStatusData, 'count')); ?>;
        const statusColors = ['#6c757d', '#17a2b8', '#0d6efd', '#ffc107', '#198754', '#dc3545', '#343a40'];
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: statusLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' ')),
                datasets: [{
                    label: 'Parcels',
                    data: statusCounts,
                    backgroundColor: statusColors,
                    borderColor: statusColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>
