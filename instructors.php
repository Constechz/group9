<?php
// Instructors Team Management
// Almighty Driving School Management System
require_once 'db.php';

$message = '';
$message_type = 'success';

// Handle Add/Edit Instructor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $instructor_id = !empty($_POST['instructor_id']) ? intval($_POST['instructor_id']) : null;
    $instructor_name = trim($_POST['instructor_name']);
    $instructor_dob = !empty($_POST['instructor_dob']) ? $_POST['instructor_dob'] : null;
    $national_id = trim($_POST['national_id']);
    $license_number = trim($_POST['license_number']);
    $license_type = trim($_POST['license_type']);
    $telephone = trim($_POST['telephone']);
    $email = trim($_POST['email']);

    try {
        if ($instructor_id) {
            $stmt = $pdo->prepare("UPDATE instructor SET instructor_name=?, instructor_dob=?, national_id=?, license_number=?, license_type=?, telephone=?, email=? WHERE instructor_id=?");
            $stmt->execute([$instructor_name, $instructor_dob, $national_id, $license_number, $license_type, $telephone, $email, $instructor_id]);
            $message = "Instructor profile updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO instructor (instructor_name, instructor_dob, national_id, license_number, license_type, telephone, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$instructor_name, $instructor_dob, $national_id, $license_number, $license_type, $telephone, $email]);
            $message = "New instructor registered successfully!";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle Delete Instructor
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM instructor WHERE instructor_id=?");
        $stmt->execute([$del_id]);
        $message = "Instructor profile deleted successfully!";
    } catch (Exception $e) {
        $message = "Error deleting instructor: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Fetch all instructors
$instructors = [];
try {
    $stmt = $pdo->query("SELECT * FROM instructor ORDER BY instructor_id DESC");
    $instructors = $stmt->fetchAll();
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

<!-- Instructors Registry -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-title">Instructors Directory</h3>
        <button onclick="openAddModal()" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i>
            <span>Add Instructor</span>
        </button>
    </div>

    <table class="custom-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Instructor Name</th>
                <th>Contact Info</th>
                <th>License Type & No</th>
                <th>National ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($instructors)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 32px;">No instructor records registered yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($instructors as $inst): ?>
                    <tr>
                        <td><?php echo $inst['instructor_id']; ?></td>
                        <td>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($inst['instructor_name']); ?></div>
                            <div style="font-size: 0.82rem; color: var(--text-secondary);">DOB: <?php echo $inst['instructor_dob'] ? date('M d, Y', strtotime($inst['instructor_dob'])) : 'N/A'; ?></div>
                        </td>
                        <td>
                            <div><i class="fa-solid fa-phone" style="font-size: 0.8rem; color: var(--text-muted);"></i> <?php echo htmlspecialchars($inst['telephone']); ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fa-solid fa-envelope" style="font-size: 0.8rem; color: var(--text-muted);"></i> <?php echo htmlspecialchars($inst['email']); ?></div>
                        </td>
                        <td>
                            <span class="badge badge-info"><?php echo htmlspecialchars($inst['license_type']); ?></span>
                            <div style="font-size: 0.82rem; color: var(--text-secondary); margin-top: 4px;">No: <?php echo htmlspecialchars($inst['license_number']); ?></div>
                        </td>
                        <td><code><?php echo htmlspecialchars($inst['national_id']); ?></code></td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($inst)); ?>)" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem; color: var(--primary);" title="Edit Profile">
                                    <i class="fa-solid fa-user-pen"></i>
                                </button>
                                <a href="instructors.php?delete=<?php echo $inst['instructor_id']; ?>" onclick="return confirm('Are you sure you want to delete this instructor? This will affect any scheduled classes assigned to them.')" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem; color: var(--danger);" title="Delete Profile">
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
     ADD / EDIT INSTRUCTOR MODAL
     ========================================== -->
<div class="modal" id="instructorModal">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title" id="modalTitle">Register Instructor</h4>
            <button class="modal-close" onclick="closeModal('instructorModal')">&times;</button>
        </div>
        <form method="POST" action="instructors.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="instructor_id" id="instructor_id">
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="instructor_name" id="instructor_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="instructor_dob" id="instructor_dob" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Telephone *</label>
                        <input type="text" name="telephone" id="telephone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">National ID (Ghana Card) *</label>
                        <input type="text" name="national_id" id="national_id" class="form-control" placeholder="GHA-XXXXXXXXX-X" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Driving License Number *</label>
                        <input type="text" name="license_number" id="license_number" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Driving License Category (Class) *</label>
                    <select name="license_type" id="license_type" class="form-control" required>
                        <option value="Class B">Class B (Sedan/SUV)</option>
                        <option value="Class C">Class C (Light Van)</option>
                        <option value="Class D">Class D (Bus)</option>
                        <option value="Class E">Class E (Heavy Cargo)</option>
                        <option value="Class F">Class F (Trailer/Articulated)</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('instructorModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Instructor</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Register Instructor';
        document.getElementById('instructor_id').value = '';
        document.getElementById('instructor_name').value = '';
        document.getElementById('instructor_dob').value = '';
        document.getElementById('telephone').value = '';
        document.getElementById('email').value = '';
        document.getElementById('national_id').value = '';
        document.getElementById('license_number').value = '';
        document.getElementById('license_type').value = 'Class B';
        openModal('instructorModal');
    }

    function openEditModal(inst) {
        document.getElementById('modalTitle').innerText = 'Edit Instructor Profile';
        document.getElementById('instructor_id').value = inst.instructor_id;
        document.getElementById('instructor_name').value = inst.instructor_name;
        document.getElementById('instructor_dob').value = inst.instructor_dob || '';
        document.getElementById('telephone').value = inst.telephone;
        document.getElementById('email').value = inst.email;
        document.getElementById('national_id').value = inst.national_id;
        document.getElementById('license_number').value = inst.license_number;
        document.getElementById('license_type').value = inst.license_type;
        openModal('instructorModal');
    }
</script>

<?php include 'footer.php'; ?>
