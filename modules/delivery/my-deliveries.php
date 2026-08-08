<?php
// Courier Management System - My Deliveries (Delivery Staff Dashboard)
// Created: 2026-08-08

$pageTitle = 'My Deliveries';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once '../parcels/helpers.php';

// Check if user is Delivery Staff - redirect Admin/Staff to assigned-list.php
if (!hasRole(['Delivery Staff'])) {
    setFlashMessage('info', 'Admin/Staff users should use the "All Deliveries" page to view all assigned parcels.');
    header('Location: ' . BASE_URL . 'modules/delivery/assigned-list.php');
    exit;
}

$userId = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'pending'; // Default to pending tab

// Build query based on tab
$today = date('Y-m-d');

try {
    $sql = "SELECT p.*, c.full_name as customer_name
            FROM parcels p
            LEFT JOIN customers c ON p.customer_id = c.id
            WHERE p.delivery_staff_id = :user_id AND p.is_deleted = 0";
    $params = ['user_id' => $userId];
    
    switch ($tab) {
        case 'today':
            $sql .= " AND (p.expected_delivery_date = :today OR p.booking_date = :today)";
            $params['today'] = $today;
            break;
        case 'pending':
            $sql .= " AND p.current_status IN ('picked_up', 'in_transit', 'out_for_delivery')";
            break;
        case 'delivered':
            $sql .= " AND p.current_status = 'delivered'";
            break;
        case 'failed':
            $sql .= " AND p.current_status = 'failed_delivery'";
            break;
        default:
            $sql .= " AND p.current_status IN ('picked_up', 'in_transit', 'out_for_delivery')";
            break;
    }
    
    $sql .= " ORDER BY p.booking_date DESC, p.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $parcels = $stmt->fetchAll();
    
    // Get counts for tabs
    $countSql = "SELECT 
                (SELECT COUNT(*) FROM parcels WHERE delivery_staff_id = :user_id AND is_deleted = 0 AND (expected_delivery_date = :today OR booking_date = :today)) as today_count,
                (SELECT COUNT(*) FROM parcels WHERE delivery_staff_id = :user_id AND is_deleted = 0 AND current_status IN ('picked_up', 'in_transit', 'out_for_delivery')) as pending_count,
                (SELECT COUNT(*) FROM parcels WHERE delivery_staff_id = :user_id AND is_deleted = 0 AND current_status = 'delivered') as delivered_count,
                (SELECT COUNT(*) FROM parcels WHERE delivery_staff_id = :user_id AND is_deleted = 0 AND current_status = 'failed_delivery') as failed_count";
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute(['user_id' => $userId, 'today' => $today]);
    $counts = $countStmt->fetch();
    
} catch (PDOException $e) {
    error_log("My Deliveries Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading your deliveries.');
    $parcels = [];
    $counts = ['today_count' => 0, 'pending_count' => 0, 'delivered_count' => 0, 'failed_count' => 0];
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">My Deliveries</h2>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'today' ? 'active' : ''; ?>" 
               href="?tab=today">
                Today's Assigned <span class="badge bg-secondary"><?php echo $counts['today_count']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'pending' ? 'active' : ''; ?>" 
               href="?tab=pending">
                Pending Delivery <span class="badge bg-warning"><?php echo $counts['pending_count']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'delivered' ? 'active' : ''; ?>" 
               href="?tab=delivered">
                Delivered <span class="badge bg-success"><?php echo $counts['delivered_count']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'failed' ? 'active' : ''; ?>" 
               href="?tab=failed">
                Failed Delivery <span class="badge bg-danger"><?php echo $counts['failed_count']; ?></span>
            </a>
        </li>
    </ul>
    
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12">
                    <h5 class="card-title">
                        <?php
                        $tabTitles = [
                            'today' => "Today's Assigned Parcels",
                            'pending' => 'Pending Delivery',
                            'delivered' => 'Delivered Parcels',
                            'failed' => 'Failed Deliveries'
                        ];
                        echo $tabTitles[$tab] ?? 'My Deliveries';
                        ?>
                    </h5>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Receiver Name</th>
                            <th>Receiver Phone</th>
                            <th>Receiver Address</th>
                            <th>COD Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parcels)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No parcels found in this category</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parcels as $parcel): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($parcel['tracking_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($parcel['receiver_name']); ?></td>
                                    <td><?php echo htmlspecialchars($parcel['receiver_phone']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($parcel['receiver_address'], 0, 40)) . (strlen($parcel['receiver_address']) > 40 ? '...' : ''); ?></td>
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
                                        <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $parcel['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if (in_array($parcel['current_status'], ['out_for_delivery', 'failed_delivery'])): ?>
                                        <a href="<?php echo BASE_URL; ?>modules/delivery/update-delivery-status.php?id=<?php echo $parcel['id']; ?>" 
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
            
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Showing parcels assigned to you. Use "Update Status" to mark deliveries as complete or failed.
                </small>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
