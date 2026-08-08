<?php
// Courier Management System - Assign Parcels to Delivery Staff
// Created: 2026-08-08

$pageTitle = 'Assign Parcels to Delivery Staff';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once '../parcels/helpers.php';

// Check if user has permission (Admin/Staff only)
if (!hasPermission($pdo, 'delivery', 'edit')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Get delivery staff for dropdown
try {
    $staffStmt = $pdo->query("
        SELECT u.id, u.full_name, u.username 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE r.role_name = 'Delivery Staff' AND u.is_active = 1
        ORDER BY u.full_name
    ");
    $deliveryStaff = $staffStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch Staff Error: " . $e->getMessage());
    $deliveryStaff = [];
}

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parcelId = $_POST['parcel_id'] ?? 0;
    $staffId = $_POST['staff_id'] ?? 0;
    
    if (!$parcelId || !$staffId) {
        setFlashMessage('error', 'Please select a parcel and delivery staff.');
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Get parcel current status and current staff
            $stmt = $pdo->prepare("SELECT * FROM parcels WHERE id = :id");
            $stmt->execute(['id' => $parcelId]);
            $parcel = $stmt->fetch();
            
            if (!$parcel) {
                throw new Exception('Parcel not found.');
            }
            
            // Get staff name for log
            $staffStmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :id");
            $staffStmt->execute(['id' => $staffId]);
            $staff = $staffStmt->fetch();
            
            if (!$staff) {
                throw new Exception('Delivery staff not found.');
            }
            
            // Update parcel delivery staff
            $updateStmt = $pdo->prepare("UPDATE parcels SET delivery_staff_id = :staff_id WHERE id = :id");
            $updateStmt->execute(['staff_id' => $staffId, 'id' => $parcelId]);
            
            // Insert status log with assignment note
            $logStmt = $pdo->prepare("
                INSERT INTO parcel_status_log (parcel_id, status, note, changed_by) 
                VALUES (:parcel_id, :status, :note, :changed_by)
            ");
            $logStmt->execute([
                'parcel_id' => $parcelId,
                'status' => $parcel['current_status'],
                'note' => 'Assigned to ' . $staff['full_name'],
                'changed_by' => $_SESSION['user_id']
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            setFlashMessage('success', 'Parcel assigned successfully to ' . htmlspecialchars($staff['full_name']));
            header('Location: ' . BASE_URL . 'modules/delivery/assign.php');
            exit;
            
        } catch (Exception $e) {
            // Rollback on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Assign Parcel Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while assigning the parcel: ' . $e->getMessage());
        }
    }
}

// Get parcels ready for assignment (picked_up or in_transit status)
try {
    $sql = "SELECT p.*, 
            CONCAT(u.full_name, ' (', u.username, ')') as delivery_staff_name
            FROM parcels p
            LEFT JOIN users u ON p.delivery_staff_id = u.id
            WHERE p.current_status IN ('picked_up', 'in_transit') 
            AND p.is_deleted = 0
            ORDER BY p.booking_date DESC, p.id DESC";
    
    $stmt = $pdo->query($sql);
    $parcels = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch Parcels Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading parcels.');
    $parcels = [];
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Assign Parcels to Delivery Staff</h2>
            <a href="<?php echo BASE_URL; ?>modules/delivery/assigned-list.php" class="btn btn-secondary">Back to All Deliveries</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12">
                    <h5 class="card-title">Parcels Ready for Assignment</h5>
                    <p class="text-muted">Assign parcels in "Picked Up" or "In Transit" status to delivery staff.</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Receiver Name</th>
                            <th>Receiver Address</th>
                            <th>Current Status</th>
                            <th>Currently Assigned</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parcels)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No parcels ready for assignment</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parcels as $parcel): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($parcel['tracking_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($parcel['receiver_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($parcel['receiver_address'], 0, 50)) . (strlen($parcel['receiver_address']) > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($parcel['current_status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($parcel['delivery_staff_name'] ?? 'Unassigned'); ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="parcel_id" value="<?php echo $parcel['id']; ?>">
                                            <select class="form-select form-select-sm me-2" name="staff_id" required style="width: 200px;">
                                                <option value="">Select Staff</option>
                                                <?php foreach ($deliveryStaff as $staff): ?>
                                                    <option value="<?php echo $staff['id']; ?>" <?php echo $parcel['delivery_staff_id'] == $staff['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($staff['full_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Assign</button>
                                        </form>
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
                    Assignment is independent of status change. The timeline will reflect the assignment event.
                    <br>
                    To change parcel status, use the <a href="<?php echo BASE_URL; ?>modules/parcels/update-status.php?id=<?php echo $parcel['id'] ?? ''; ?>">Update Status</a> feature.
                </small>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
