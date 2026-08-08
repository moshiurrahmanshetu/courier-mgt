<?php
// Courier Management System - View Parcel
// Created: 2026-08-08

$pageTitle = 'View Parcel';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once 'helpers.php';

// Check if user has permission
$hasAccess = hasPermission($pdo, 'parcels', 'view');

// Allow Delivery Staff to view parcels assigned to them
if (!$hasAccess && hasRole(['Delivery Staff'])) {
    // We'll check after fetching the parcel if it's assigned to this user
    $checkDeliveryStaffAccess = true;
} else {
    $checkDeliveryStaffAccess = false;
}

if (!$hasAccess && !$checkDeliveryStaffAccess) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$parcelId = $_GET['id'] ?? 0;

if (!$parcelId) {
    setFlashMessage('error', 'Invalid parcel ID.');
    header('Location: ' . BASE_URL . 'modules/parcels/list.php');
    exit;
}

// Fetch parcel data with related information
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               c.full_name as customer_name, c.customer_code, c.phone as customer_phone, c.address as customer_address,
               CONCAT(u.full_name, ' (', u.username, ')') as delivery_staff_name
        FROM parcels p
        LEFT JOIN customers c ON p.customer_id = c.id
        LEFT JOIN users u ON p.delivery_staff_id = u.id
        WHERE p.id = :id
    ");
    $stmt->execute(['id' => $parcelId]);
    $parcel = $stmt->fetch();
    
    if (!$parcel) {
        setFlashMessage('error', 'Parcel not found.');
        header('Location: ' . BASE_URL . 'modules/parcels/list.php');
        exit;
    }
    
    // Check Delivery Staff access if that's the access method
    if ($checkDeliveryStaffAccess) {
        if ($parcel['delivery_staff_id'] != $_SESSION['user_id']) {
            setFlashMessage('error', 'Access denied. This parcel is not assigned to you.');
            header('Location: ' . BASE_URL . 'modules/delivery/my-deliveries.php');
            exit;
        }
        $hasAccess = true; // Grant access since it's their parcel
    }
    
    // Fetch status timeline
    $stmt = $pdo->prepare("
        SELECT psl.*, 
               CONCAT(changer.full_name, ' (', changer.username, ')') as changed_by_name
        FROM parcel_status_log psl
        LEFT JOIN users changer ON psl.changed_by = changer.id
        WHERE psl.parcel_id = :parcel_id
        ORDER BY psl.changed_at ASC
    ");
    $stmt->execute(['parcel_id' => $parcelId]);
    $statusHistory = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Fetch Parcel Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading parcel data.');
    header('Location: ' . BASE_URL . 'modules/parcels/list.php');
    exit;
}

$canEdit = hasPermission($pdo, 'parcels', 'edit') && in_array($parcel['current_status'], ['pending', 'picked_up']);

// Delivery Staff can update status for their assigned parcels if in out_for_delivery or failed_delivery
$canUpdateDeliveryStatus = hasRole(['Delivery Staff']) && 
                            $parcel['delivery_staff_id'] == $_SESSION['user_id'] && 
                            in_array($parcel['current_status'], ['out_for_delivery', 'failed_delivery']);

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">View Parcel Details</h2>
            <a href="<?php echo BASE_URL; ?>modules/parcels/list.php" class="btn btn-secondary">Back to Parcels</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Parcel Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Tracking Number</label>
                            <div class="form-control-plaintext fw-bold fs-5"><?php echo htmlspecialchars($parcel['tracking_number']); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Current Status</label>
                            <div>
                                <span class="badge bg-<?php echo getStatusBadgeClass($parcel['current_status']); ?> fs-6">
                                    <?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Sender Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Customer Code</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($parcel['customer_code']); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Customer Name</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($parcel['customer_name']); ?></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Customer Phone</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($parcel['customer_phone'] ?? '-'); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Customer Address</label>
                            <div class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($parcel['customer_address'] ?? '-')); ?></div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Receiver Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Receiver Name</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($parcel['receiver_name']); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Receiver Phone</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($parcel['receiver_phone']); ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Receiver Address</label>
                        <div class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($parcel['receiver_address'])); ?></div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Parcel Details</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Parcel Type</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($parcel['parcel_type']); ?></div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Weight</label>
                            <div class="form-control-plaintext"><?php echo $parcel['weight'] ? number_format($parcel['weight'], 2) . ' KG' : '-'; ?></div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Description</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($parcel['parcel_description'] ?? '-'); ?></div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Financial Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Delivery Charge</label>
                            <div class="form-control-plaintext fw-bold"><?php echo number_format($parcel['delivery_charge'], 2); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">COD Amount</label>
                            <div class="form-control-plaintext fw-bold"><?php echo $parcel['cod_amount'] > 0 ? number_format($parcel['cod_amount'], 2) : 'No COD'; ?></div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Delivery Information</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Delivery Staff</label>
                            <div class="form-control-plaintext"><?php echo htmlspecialchars($parcel['delivery_staff_name'] ?? 'Unassigned'); ?></div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Booking Date</label>
                            <div class="form-control-plaintext"><?php echo date('M d, Y', strtotime($parcel['booking_date'])); ?></div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Expected Delivery</label>
                            <div class="form-control-plaintext"><?php echo $parcel['expected_delivery_date'] ? date('M d, Y', strtotime($parcel['expected_delivery_date'])) : 'Not specified'; ?></div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">System Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Created At</label>
                            <div class="form-control-plaintext"><?php echo date('M d, Y g:i A', strtotime($parcel['created_at'])); ?></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Last Updated</label>
                            <div class="form-control-plaintext">
                                <?php 
                                    if ($parcel['updated_at'] == $parcel['created_at']) {
                                        echo 'Never updated';
                                    } else {
                                        echo date('M d, Y g:i A', strtotime($parcel['updated_at']));
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <?php if ($canEdit): ?>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/edit.php?id=<?php echo $parcel['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Parcel
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($pdo, 'parcels', 'edit')): ?>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/update-status.php?id=<?php echo $parcel['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-exchange-alt"></i> Update Status
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($canUpdateDeliveryStatus): ?>
                        <a href="<?php echo BASE_URL; ?>modules/delivery/update-delivery-status.php?id=<?php echo $parcel['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-exchange-alt"></i> Update Delivery Status
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($checkDeliveryStaffAccess): ?>
                        <a href="<?php echo BASE_URL; ?>modules/delivery/my-deliveries.php" class="btn btn-secondary">Back to My Deliveries</a>
                        <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/list.php" class="btn btn-secondary">Back to List</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Status Timeline</h5>
                    
                    <?php if (empty($statusHistory)): ?>
                        <p class="text-muted">No status history available.</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($statusHistory as $index => $log): ?>
                            <div class="timeline-item <?php echo $index === count($statusHistory) - 1 ? 'active' : ''; ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="timeline-status">
                                        <span class="badge bg-<?php echo getStatusBadgeClass($log['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $log['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="timeline-time">
                                        <?php echo date('M d, Y g:i A', strtotime($log['changed_at'])); ?>
                                    </div>
                                    <?php if ($log['note']): ?>
                                    <div class="timeline-note">
                                        <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($log['note']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="timeline-user">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($log['changed_by_name'] ?? 'System'); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title mb-4">Quick Actions</h5>
                    
                    <div class="d-grid gap-2">
                        <?php if ($canEdit): ?>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/edit.php?id=<?php echo $parcel['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Parcel
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($pdo, 'parcels', 'edit')): ?>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/update-status.php?id=<?php echo $parcel['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-exchange-alt"></i> Update Status
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($canUpdateDeliveryStatus): ?>
                        <a href="<?php echo BASE_URL; ?>modules/delivery/update-delivery-status.php?id=<?php echo $parcel['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-exchange-alt"></i> Update Delivery Status
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($checkDeliveryStaffAccess): ?>
                        <a href="<?php echo BASE_URL; ?>modules/delivery/my-deliveries.php" class="btn btn-secondary">
                            <i class="fas fa-list"></i> Back to My Deliveries
                        </a>
                        <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/list.php" class="btn btn-secondary">
                            <i class="fas fa-list"></i> Back to List
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
    border-left: 2px solid #dee2e6;
    margin-left: 15px;
}

.timeline-item.active {
    border-left-color: var(--accent-color);
}

.timeline-marker {
    position: absolute;
    left: -36px;
    top: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: var(--accent-color);
}

.timeline-item.active .timeline-marker {
    background-color: var(--accent-hover);
}

.timeline-content {
    margin-left: 5px;
}

.timeline-status {
    margin-bottom: 5px;
}

.timeline-time {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 3px;
}

.timeline-note {
    font-size: 0.9rem;
    color: var(--text-dark);
    margin-bottom: 3px;
    padding: 5px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.timeline-user {
    font-size: 0.85rem;
    color: var(--text-muted);
}
</style>

<?php require_once '../../includes/footer.php'; ?>
