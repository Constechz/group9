<?php
// Registrations & Enrollments Manager
// Almighty Driving School Management System
require_once 'db.php';

$message = '';
$message_type = 'success';

// Handle Registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $student_id = intval($_POST['student_id']);
    $manager_id = intval($_POST['manager_id']);
    $course_name = trim($_POST['course_name']);
    $total_cost = floatval($_POST['total_cost']);
    $deposit = floatval($_POST['deposit']);
    $registration_date = $_POST['registration_date'];
    $commencement_date = !empty($_POST['commencement_date']) ? $_POST['commencement_date'] : null;
    $completion_date = !empty($_POST['completion_date']) ? $_POST['completion_date'] : null;
    
    // Balance calculation: total_cost - deposit
    $balance = $total_cost - $deposit;
    if ($balance < 0) {
        $balance = 0;
    }

    try {
        $pdo->beginTransaction();
        
        // INSERT Registration
        $stmt = $pdo->prepare("INSERT INTO registration (registration_date, commencement_date, completion_date, course_name, total_cost, deposit, balance, student_id, manager_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$registration_date, $commencement_date, $completion_date, $course_name, $total_cost, $deposit, $balance, $student_id, $manager_id]);
        $registration_id = $pdo->lastInsertId();
        
        // If there was a deposit, record a Payment record automatically!
        if ($deposit > 0) {
            $stmt_pay = $pdo->prepare("INSERT INTO payment (payment_date, amount, payment_type, balance_after_payment, registration_id) VALUES (?, ?, ?, ?, ?)");
            $stmt_pay->execute([$registration_date, $deposit, 'Cash', $balance, $registration_id]);
        }
        
        $pdo->commit();
        $message = "Student enrolled in course successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle Update Dates / Finish Course
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_dates') {
    $registration_id = intval($_POST['registration_id']);
    $commencement_date = !empty($_POST['commencement_date']) ? $_POST['commencement_date'] : null;
    $completion_date = !empty($_POST['completion_date']) ? $_POST['completion_date'] : null;
    
    try {
        $stmt = $pdo->prepare("UPDATE registration SET commencement_date=?, completion_date=? WHERE registration_id=?");
        $stmt->execute([$commencement_date, $completion_date, $registration_id]);
        $message = "Enrollment dates updated successfully!";
    } catch (Exception $e) {
        $message = "Error updating dates: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Fetch all registrations
$registrations = [];
try {
    $stmt = $pdo->query("
        SELECT r.*, s.first_name, s.surname, s.telephone, m.manager_name 
        FROM registration r 
        JOIN student s ON r.student_id = s.student_id 
        JOIN manager m ON r.manager_id = m.manager_id 
        ORDER BY r.registration_id DESC
    ");
    $registrations = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch students for dropdown
$students_dropdown = [];
try {
    $stmt = $pdo->query("SELECT student_id, first_name, surname, national_id FROM student ORDER BY surname, first_name");
    $students_dropdown = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch managers for dropdown
$managers_dropdown = [];
try {
    $stmt = $pdo->query("SELECT manager_id, manager_name FROM manager ORDER BY manager_name");
    $managers_dropdown = $stmt->fetchAll();
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

<!-- Registrations Grid -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-title">Course Enrollments</h3>
        <button onclick="openModal('enrollModal')" class="btn btn-primary">
            <i class="fa-solid fa-id-card"></i>
            <span>New Enrollment</span>
        </button>
    </div>

    <table class="custom-table">
        <thead>
            <tr>
                <th>Reg ID</th>
                <th>Student</th>
                <th>Course details</th>
                <th>Financial Summary</th>
                <th>Commencement</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registrations)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 32px;">No course enrollments logged yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($registrations as $reg): ?>
                    <tr>
                        <td><code>#REG-<?php echo $reg['registration_id']; ?></code></td>
                        <td>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['surname']); ?></div>
                            <div style="font-size: 0.82rem; color: var(--text-secondary);"><?php echo htmlspecialchars($reg['telephone']); ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($reg['course_name']); ?></div>
                            <div style="font-size: 0.82rem; color: var(--text-secondary);">Approved by: <?php echo htmlspecialchars($reg['manager_name']); ?> on <?php echo date('M d, Y', strtotime($reg['registration_date'])); ?></div>
                        </td>
                        <td>
                            <div>Total: <strong>GH¢<?php echo number_format($reg['total_cost'], 2); ?></strong></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">Paid: GH¢<?php echo number_format($reg['total_cost'] - $reg['balance'], 2); ?></div>
                            <div style="font-size: 0.85rem; color: <?php echo ($reg['balance'] > 0) ? 'var(--warning)' : 'var(--success)'; ?>; font-weight: 700;">Balance: GH¢<?php echo number_format($reg['balance'], 2); ?></div>
                        </td>
                        <td>
                            <?php if ($reg['commencement_date']): ?>
                                <div style="font-size: 0.85rem;"><span class="badge badge-success">Commenced</span></div>
                                <div style="font-size: 0.82rem; color: var(--text-secondary); margin-top: 2px;">Start: <?php echo date('M d, Y', strtotime($reg['commencement_date'])); ?></div>
                                <?php if ($reg['completion_date']): ?>
                                    <div style="font-size: 0.82rem; color: var(--success); margin-top: 2px; font-weight: 600;">Ended: <?php echo date('M d, Y', strtotime($reg['completion_date'])); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending Commencement</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="openDateModal(<?php echo htmlspecialchars(json_encode($reg)); ?>)" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem;" title="Update Dates / Complete">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </button>
                                <?php if ($reg['balance'] > 0): ?>
                                    <a href="payments.php?action=new&reg_id=<?php echo $reg['registration_id']; ?>" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem; color: var(--success);" title="Log Payment">
                                        <i class="fa-solid fa-money-bill-wave"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ==========================================
     NEW ENROLLMENT MODAL
     ========================================== -->
<div class="modal" id="enrollModal">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">Enroll Student in Course</h4>
            <button class="modal-close" onclick="closeModal('enrollModal')">&times;</button>
        </div>
        <form method="POST" action="registrations.php">
            <input type="hidden" name="action" value="save">
            
            <div class="modal-body">
                <!-- Row 1 Select Student & Manager -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Student *</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students_dropdown as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>">
                                    <?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name'] . ' (' . $student['national_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Approving Manager *</label>
                        <select name="manager_id" class="form-control" required>
                            <?php foreach ($managers_dropdown as $mgr): ?>
                                <option value="<?php echo $mgr['manager_id']; ?>" <?php echo ($mgr['manager_id'] == 1) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mgr['manager_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Row 2 Course details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Course Name *</label>
                        <select name="course_name" id="course_select" class="form-control" onchange="updateCourseCost()" required>
                            <option value="Beginner Driving Course" data-cost="1200.00">Beginner Driving Course (GH¢1,200)</option>
                            <option value="Refresher Driving Course" data-cost="700.00">Refresher Driving Course (GH¢700)</option>
                            <option value="Defensive Driving Course" data-cost="1500.00">Defensive Driving Course (GH¢1,500)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Cost (GH¢) *</label>
                        <input type="number" step="0.01" name="total_cost" id="total_cost" class="form-control" value="1200.00" required>
                    </div>
                </div>

                <!-- Row 3 Deposit and Dates -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Deposit Received (GH¢) *</label>
                        <input type="number" step="0.01" name="deposit" class="form-control" value="0.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Registration Date *</label>
                        <input type="date" name="registration_date" class="form-control" value="<?php echo date('Y-md'); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Commencement Date</label>
                        <input type="date" name="commencement_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expected Completion Date</label>
                        <input type="date" name="completion_date" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('enrollModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Complete Enrollment</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     UPDATE DATES MODAL
     ========================================== -->
<div class="modal" id="dateModal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h4 class="modal-title">Update Enrollment Progress</h4>
            <button class="modal-close" onclick="closeModal('dateModal')">&times;</button>
        </div>
        <form method="POST" action="registrations.php">
            <input type="hidden" name="action" value="update_dates">
            <input type="hidden" name="registration_id" id="date_reg_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Commencement Date</label>
                    <input type="date" name="commencement_date" id="date_commence" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Completion Date</label>
                    <input type="date" name="completion_date" id="date_complete" class="form-control">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('dateModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateCourseCost() {
        const select = document.getElementById('course_select');
        const costInput = document.getElementById('total_cost');
        const selectedOption = select.options[select.selectedIndex];
        costInput.value = selectedOption.getAttribute('data-cost');
    }

    function openDateModal(reg) {
        document.getElementById('date_reg_id').value = reg.registration_id;
        document.getElementById('date_commence').value = reg.commencement_date || '';
        document.getElementById('date_complete').value = reg.completion_date || '';
        openModal('dateModal');
    }
</script>

<?php include 'footer.php'; ?>
