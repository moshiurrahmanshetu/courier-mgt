<?php
// Courier Management System - Update Parcel Status
// Created: 2026-08-08

$pageTitle = 'Update Parcel Status';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once 'helpers.php';

// Check if user has permission
if (!hasPermission($pdo, 'parcels', 'edit')) {
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

// Fetch parcel data
try {
    $stmt = $pdo->prepare("SELECT * FROM parcels WHERE id = :id");
    $stmt->execute(['id' => $parcelId]);
    $parcel = $stmt->fetch();
    
    if (!$parcel) {
        setFlashMessage('error', 'Parcel not found.');
        header('Location: ' . BASE_URL . 'modules/parcels/list.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Fetch Parcel Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading parcel data.');
    header('Location: ' . BASE_URL . 'modules/parcels/list.php');
    exit;
}

// Get valid transitions for current status
$validTransitions = getValidStatusTransitions($parcel['current_status']);
$finalStatuses = ['delivered', 'cancelled'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['new_status'] ?? '';
    $deliveryStaffId = $_POST['delivery_staff_id'] ?? '';
    $note = trim($_POST['note'] ?? '');
    
    // Validate status transition
    if (empty($newStatus)) {
        setFlashMessage('error', 'Please select a new status.');
    } elseif (!isValidStatusTransition($parcel['current_status'], $newStatus)) {
        setFlashMessage('error', 'Invalid status transition. Cannot change from "' . ucfirst(str_replace('_', ' ', $parcel['current_status'])) . '" to "' . ucfirst(str_replace('_', ' ', $newStatus)) . '".');
    } elseif (in_array($parcel['current_status'], $finalStatuses)) {
        setFlashMessage('error', 'Cannot change status from "' . ucfirst(str_replace('_', ' ', $parcel['current_status'])) . '" - this is a final status.');
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update parcel status
            $updateSql = "UPDATE parcels SET current_status = :new_status";
            $params = ['new_status' => $newStatus, 'id' => $parcelId];
            
            // Update delivery staff if provided and allowed
            if ($deliveryStaffId && !in_array($parcel['current_status'], $finalStatuses)) {
                $updateSql .= ", delivery_staff_id = :delivery_staff_id";
                $params['delivery_staff_id'] = $deliveryStaffId;
            }
            
            $updateSql .= " WHERE id = :id";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute($params);
            
            // Insert status log
            $logSql = "INSERT INTO parcel_status_log (parcel_id, status, note, changed_by) 
                      VALUES (:parcel_id, :status, :note, :changed_by)";
            $logStmt = $pdo->prepare($logSql);
            $logStmt->execute([
                'parcel_id' => $parcelId,
                'status' => $newStatus,
                'note' => $note ?: null,
                'changed_by' => $_SESSION['user_id']
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            setFlashMessage('success', 'Parcel status updated successfully to: ' . ucfirst(str_replace('_', ' ', $newStatus)));
            header('Location: ' . BASE_URL . 'modules/parcels/view.php?id=' . $parcelId);
            exit;
            
        } catch (PDOException $e) {
            // Rollback on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Update Status Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while updating parcel status.');
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Update Parcel Status</h2>
            <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $parcel['id']; ?>" class="btn btn-secondary">Back to Parcel</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Update Status: <?php echo htmlspecialchars($parcel['tracking_number']); ?></h5>
                    
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle"></i> 
                        Current Status: <strong><?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?></strong>
                    </div>
                    
                    <?php if (in_array($parcel['current_status'], $finalStatuses)): ?>
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-exclamation-triangle"></i> 
                        This parcel has reached a final status and cannot be updated further.
                    </div>
                    <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $parcel['id']; ?>" class="btn btn-secondary">View Details</a>
                    <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="new_status" class="form-label">New Status *</label>
                            <select class="form-select" id="new_status" name="new_status" required>
                                <option value="">Select New Status</option>
                                <?php foreach ($validTransitions as $transition): ?>
                                    <option value="<?php echo $transition; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $transition)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a valid status</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="delivery_staff_id" class="form-label">Assign Delivery Staff</label>
                            <select class="form-select" id="delivery_staff_id" name="delivery_staff_id">
                                <option value="">-- Select Delivery Staff (Optional) --</option>
                                <?php foreach ($deliveryStaff as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>" <?php echo $parcel['delivery_staff_id'] == $staff['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($staff['full_name'] . ' (' . $staff['username'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Can be assigned at any time except for final statuses</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="note" class="form-label">Note (Optional)</label>
                            <textarea class="form-control" id="note" name="note" rows="2" placeholder="e.g., Reason for failed delivery, Delivery instructions"><?php echo htmlspecialchars($_POST['note'] ?? ''); ?></textarea>
                            <small class="text-muted">Particularly useful for failed delivery or special instructions</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-secondary">
                                <strong>Valid Status Transitions:</strong><br>
                                <small>
                                    <?php foreach ($validTransitions as $transition): ?>
                                    <?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?> → <?php echo ucfirst(str_replace('_', ' ', $transition)); ?><br>
                                    <?php endforeach; ?>
                                </small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Status</button>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $parcel['id']; ?>" class="btn btn-secondary">Cancel</a>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
