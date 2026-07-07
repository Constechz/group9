<?php
// Fleet Vehicles Management
// Almighty Driving School Management System
require_once 'db.php';

$message = '';
$message_type = 'success';

// Handle Add/Edit Vehicle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $vehicle_id = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
    $vehicle_registration_no = trim($_POST['vehicle_registration_no']);
    $vehicle_identity_no = trim($_POST['vehicle_identity_no']);
    $vehicle_type = trim($_POST['vehicle_type']);
    $status = $_POST['status'];

    try {
        if ($vehicle_id) {
            $stmt = $pdo->prepare("UPDATE vehicles SET vehicle_registration_no=?, vehicle_identity_no=?, vehicle_type=?, status=? WHERE vehicle_id=?");
            $stmt->execute([$vehicle_registration_no, $vehicle_identity_no, $vehicle_type, $status, $vehicle_id]);
            $message = "Vehicle details updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO vehicles (vehicle_registration_no, vehicle_identity_no, vehicle_type, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$vehicle_registration_no, $vehicle_identity_no, $vehicle_type, $status]);
            $message = "New vehicle registered in fleet successfully!";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle Delete Vehicle
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE vehicle_id=?");
        $stmt->execute([$del_id]);
        $message = "Vehicle removed from fleet successfully.";
    } catch (Exception $e) {
        $message = "Error removing vehicle (it might be linked to existing lesson schedules): " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Fetch all vehicles
$vehicles = [];
try {
    $stmt = $pdo->query("SELECT * FROM vehicles ORDER BY vehicle_id DESC");
    $vehicles = $stmt->fetchAll();
} catch (Exception $e) {}

include 'header.php';
?>

<!-- Message Notification -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <i class="fa-solid <?php echo ($message_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
<?php endif; ?>

<!-- Vehicles Fleet -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-title">School Training Fleet</h3>
        <button onclick="openAddModal()" class="btn btn-primary">
            <i class="fa-solid fa-car-side"></i>
            <span>Add Vehicle</span>
        </button>
    </div>

    <table class="custom-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Registration No.</th>
                <th>Vehicle Identity No (VIN)</th>
                <th>Transmission & Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vehicles)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 32px;">No vehicles registered in fleet yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($vehicles as $veh): ?>
                    <?php 
                    $badge_class = 'badge-success';
                    if ($veh['status'] == 'Maintenance') {
                        $badge_class = 'badge-warning';
                    } elseif ($veh['status'] == 'Out of Service') {
                        $badge_class = 'badge-danger';
                    }
                    ?>
                    <tr>
                        <td><?php echo $veh['vehicle_id']; ?></td>
                        <td><strong style="font-size: 1rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars($veh['vehicle_registration_no']); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($veh['vehicle_identity_no']); ?></code></td>
                        <td><?php echo htmlspecialchars($veh['vehicle_type']); ?></td>
                        <td>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo htmlspecialchars($veh['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($veh)); ?>)" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem; color: var(--primary);" title="Edit Info">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <a href="vehicles.php?delete=<?php echo $veh['vehicle_id']; ?>" onclick="return confirm('Are you sure you want to delete this vehicle from the fleet?')" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem; color: var(--danger);" title="Delete Vehicle">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ==========================================
     ADD / EDIT VEHICLE MODAL
     ========================================== -->
<div class="modal" id="vehicleModal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h4 class="modal-title" id="modalTitle">Register Vehicle</h4>
            <button class="modal-close" onclick="closeModal('vehicleModal')">&times;</button>
        </div>
        <form method="POST" action="vehicles.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="vehicle_id" id="vehicle_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Vehicle Registration Number *</label>
                    <input type="text" name="vehicle_registration_no" id="vehicle_registration_no" class="form-control" placeholder="e.g. AS-1024-24" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Vehicle Identification Number (VIN) *</label>
                    <input type="text" name="vehicle_identity_no" id="vehicle_identity_no" class="form-control" placeholder="17-Character VIN" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Transmission / Vehicle Type *</label>
                    <input type="text" name="vehicle_type" id="vehicle_type" class="form-control" placeholder="e.g. Manual Toyota Yaris, Automatic SUV" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="Active">Active / Available</option>
                        <option value="Maintenance">Under Maintenance</option>
                        <option value="Out of Service">Out of Service</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('vehicleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Register Vehicle';
        document.getElementById('vehicle_id').value = '';
        document.getElementById('vehicle_registration_no').value = '';
        document.getElementById('vehicle_identity_no').value = '';
        document.getElementById('vehicle_type').value = '';
        document.getElementById('status').value = 'Active';
        openModal('vehicleModal');
    }

    function openEditModal(veh) {
        document.getElementById('modalTitle').innerText = 'Edit Vehicle Details';
        document.getElementById('vehicle_id').value = veh.vehicle_id;
        document.getElementById('vehicle_registration_no').value = veh.vehicle_registration_no;
        document.getElementById('vehicle_identity_no').value = veh.vehicle_identity_no;
        document.getElementById('vehicle_type').value = veh.vehicle_type;
        document.getElementById('status').value = veh.status;
        openModal('vehicleModal');
    }
</script>

<?php include 'footer.php'; ?>
