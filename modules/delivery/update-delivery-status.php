<?php
// Courier Management System - Update Delivery Status (Delivery Staff Only)
// Created: 2026-08-08

$pageTitle = 'Update Delivery Status';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once '../parcels/helpers.php';

// Check if user is Delivery Staff
if (!hasRole(['Delivery Staff'])) {
    setFlashMessage('error', 'Access denied. This page is for Delivery Staff only.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$parcelId = $_GET['id'] ?? 0;

if (!$parcelId) {
    setFlashMessage('error', 'Invalid parcel ID.');
    header('Location: ' . BASE_URL . 'modules/delivery/my-deliveries.php');
    exit;
}

// Fetch parcel data
try {
    $stmt = $pdo->prepare("SELECT * FROM parcels WHERE id = :id");
    $stmt->execute(['id' => $parcelId]);
    $parcel = $stmt->fetch();
    
    if (!$parcel) {
        setFlashMessage('error', 'Parcel not found.');
        header('Location: ' . BASE_URL . 'modules/delivery/my-deliveries.php');
        exit;
    }
    
    // SECURITY CHECK: Verify parcel is assigned to this delivery staff
    if ($parcel['delivery_staff_id'] != $_SESSION['user_id']) {
        setFlashMessage('error', 'Access denied. This parcel is not assigned to you.');
        header('Location: ' . BASE_URL . 'modules/delivery/my-deliveries.php');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Fetch Parcel Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading parcel data.');
    header('Location: ' . BASE_URL . 'modules/delivery/my-deliveries.php');
    exit;
}

// Valid transitions for Delivery Staff (narrower than full Phase 4 rules)
$deliveryStaffTransitions = [
    'out_for_delivery' => ['delivered', 'failed_delivery'],
    'failed_delivery' => ['out_for_delivery']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['new_status'] ?? '';
    $note = trim($_POST['note'] ?? '');
    
    // Validate status transition
    if (empty($newStatus)) {
        setFlashMessage('error', 'Please select a new status.');
    } elseif (!isset($deliveryStaffTransitions[$parcel['current_status']]) || !in_array($newStatus, $deliveryStaffTransitions[$parcel['current_status']])) {
        setFlashMessage('error', 'Invalid status transition. Delivery Staff can only: out_for_delivery → delivered/failed_delivery, or failed_delivery → out_for_delivery (re-attempt).');
    } elseif ($newStatus === 'failed_delivery' && empty($note)) {
        setFlashMessage('error', 'Note is required when marking a delivery as failed. Please provide the reason.');
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update parcel status
            $updateStmt = $pdo->prepare("UPDATE parcels SET current_status = :new_status WHERE id = :id");
            $updateStmt->execute(['new_status' => $newStatus, 'id' => $parcelId]);
            
            // Insert status log
            $logStmt = $pdo->prepare("
                INSERT INTO parcel_status_log (parcel_id, status, note, changed_by) 
                VALUES (:parcel_id, :status, :note, :changed_by)
            ");
            $logStmt->execute([
                'parcel_id' => $parcelId,
                'status' => $newStatus,
                'note' => $note ?: null,
                'changed_by' => $_SESSION['user_id']
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            setFlashMessage('success', 'Parcel status updated successfully to: ' . ucfirst(str_replace('_', ' ', $newStatus)));
            header('Location: ' . BASE_URL . 'modules/delivery/my-deliveries.php');
            exit;
            
        } catch (PDOException $e) {
            // Rollback on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Update Delivery Status Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while updating parcel status.');
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Update Delivery Status</h2>
            <a href="<?php echo BASE_URL; ?>modules/delivery/my-deliveries.php" class="btn btn-secondary">Back to My Deliveries</a>
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
                    
                    <?php if (!isset($deliveryStaffTransitions[$parcel['current_status']])): ?>
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-exclamation-triangle"></i> 
                        This parcel is not currently available for delivery status update.<br>
                        Only parcels in "Out for Delivery" or "Failed Delivery" status can be updated by Delivery Staff.
                    </div>
                    <a href="<?php echo BASE_URL; ?>modules/delivery/my-deliveries.php" class="btn btn-secondary">Back to My Deliveries</a>
                    <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="new_status" class="form-label">New Status *</label>
                            <select class="form-select" id="new_status" name="new_status" required>
                                <option value="">Select New Status</option>
                                <?php foreach ($deliveryStaffTransitions[$parcel['current_status']] as $transition): ?>
                                    <option value="<?php echo $transition; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $transition)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a valid status</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="note" class="form-label">Note <?php echo $parcel['current_status'] === 'out_for_delivery' ? '(Optional)' : ' *'; ?></label>
                            <textarea class="form-control" id="note" name="note" rows="3" 
                                      placeholder="<?php echo $parcel['current_status'] === 'out_for_delivery' ? 'Optional delivery notes' : 'Reason for failed delivery (required)'; ?>"
                                      <?php echo $parcel['current_status'] === 'failed_delivery' ? 'required' : ''; ?>><?php echo htmlspecialchars($_POST['note'] ?? ''); ?></textarea>
                            <small class="text-muted">
                                <?php if ($parcel['current_status'] === 'out_for_delivery'): ?>
                                    Optional delivery notes or special instructions.
                                <?php else: ?>
                                    Required: Please provide the reason for failed delivery (e.g., customer not available, wrong address, refused delivery).
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-secondary">
                                <strong>Allowed Transitions:</strong><br>
                                <small>
                                    <?php foreach ($deliveryStaffTransitions[$parcel['current_status']] as $transition): ?>
                                    <?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?> → <?php echo ucfirst(str_replace('_', ' ', $transition)); ?><br>
                                    <?php endforeach; ?>
                                </small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Status</button>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $parcel['id']; ?>" class="btn btn-info">View Details</a>
                        <a href="<?php echo BASE_URL; ?>modules/delivery/my-deliveries.php" class="btn btn-secondary">Cancel</a>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
